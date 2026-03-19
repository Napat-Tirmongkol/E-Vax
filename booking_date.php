<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/footer.php';

// -----------------------------
// INPUT (month/year)
// -----------------------------
// React mock: March 2026
$year = isset($_GET['year']) ? (int)$_GET['year'] : 2026;
$month = isset($_GET['month']) ? (int)$_GET['month'] : 3;
if ($year < 2000 || $year > 2100) $year = (int)date('Y');
if ($month < 1 || $month > 12) $month = (int)date('n');

$monthName = date('F', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$daysInMonth = (int)cal_days_in_month(CAL_GREGORIAN, $month, $year);

// 0 = Sunday ... 6 = Saturday (เพื่อให้เหมือน React weekDays Sun..Sat)
$startDayOfWeek = (int)date('w', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// วันที่ที่ผู้ใช้เลือก (แทน useState)
$selectedDate = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_date'])) {
  $selectedDate = (int)$_POST['selected_date'];
} elseif (isset($_GET['selected_date'])) {
  $selectedDate = (int)$_GET['selected_date'];
} else {
  $selectedDate = 12; // default ตาม React mock
}

if ($selectedDate !== null && ($selectedDate < 1 || $selectedDate > $daysInMonth)) {
  $selectedDate = null;
}

// -----------------------------
// DENSITY CALC FROM DATABASE
// -----------------------------
/**
 * ต้องมีตาราง (คุณบอกว่าจะเพิ่ม):
 *
 * - vac_time_slots: เก็บ slot ต่อวัน (โควต้าต่อ slot)
 *   ตัวอย่าง schema ที่นิยม:
 *     id (PK)
 *     slot_date DATE
 *     start_time TIME
 *     end_time TIME
 *     max_capacity INT
 *
 * - vac_appointments: เก็บรายการนัด/การจอง
 *   ตัวอย่าง schema ที่นิยม:
 *     id (PK)
 *     slot_id (FK -> vac_time_slots.id)
 *     appointment_date DATE (หรือดึงจาก slot_date)
 *     status ENUM('pending','confirmed','cancelled') หรือ similar
 *
 * หมายเหตุ: ถ้าชื่อคอลัมน์ของคุณไม่ตรง ให้แก้ SQL ด้านล่างให้ตรงกับ DB จริง
 */

// map ผลลัพธ์ต่อวัน: [day => ['total' => int, 'booked' => int, 'remaining' => int]]
$dailyStats = [];
$dbError = null;

try {
  $pdo = db();

  // รวมโควต้ารายวันจาก vac_time_slots
  // *** แก้ชื่อคอลัมน์ slot_date/max_capacity ให้ตรง schema ของคุณ ***
  $sqlTotal = "
    SELECT
      DAY(ts.slot_date) AS day_num,
      COALESCE(SUM(ts.max_capacity), 0) AS total_capacity
    FROM vac_time_slots ts
    WHERE ts.slot_date >= :startDate AND ts.slot_date < :endDate
    GROUP BY DAY(ts.slot_date)
  ";

  $startDate = sprintf('%04d-%02d-01', $year, $month);
  $endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));

  $stmt = $pdo->prepare($sqlTotal);
  $stmt->execute([
    ':startDate' => $startDate,
    ':endDate' => $endDate,
  ]);

  foreach ($stmt->fetchAll() as $row) {
    $day = (int)$row['day_num'];
    $dailyStats[$day] = [
      'total' => (int)$row['total_capacity'],
      'booked' => 0,
      'remaining' => (int)$row['total_capacity'],
    ];
  }

  // นับจำนวนจอง (confirmed) ต่อวันจาก vac_appointments join vac_time_slots
  // *** แก้ชื่อคอลัมน์ status/slot_id ให้ตรง schema ของคุณ ***
  $sqlBooked = "
    SELECT
      DAY(ts.slot_date) AS day_num,
      COUNT(*) AS booked_count
    FROM vac_appointments ap
    INNER JOIN vac_time_slots ts ON ts.id = ap.slot_id
    WHERE ts.slot_date >= :startDate AND ts.slot_date < :endDate
      AND (ap.status = 'confirmed' OR ap.status = 'booked')
    GROUP BY DAY(ts.slot_date)
  ";

  $stmt2 = $pdo->prepare($sqlBooked);
  $stmt2->execute([
    ':startDate' => $startDate,
    ':endDate' => $endDate,
  ]);

  foreach ($stmt2->fetchAll() as $row) {
    $day = (int)$row['day_num'];
    $booked = (int)$row['booked_count'];

    if (!isset($dailyStats[$day])) {
      $dailyStats[$day] = ['total' => 0, 'booked' => 0, 'remaining' => 0];
    }
    $dailyStats[$day]['booked'] = $booked;
    $dailyStats[$day]['remaining'] = max(0, $dailyStats[$day]['total'] - $booked);
  }
} catch (Throwable $e) {
  // ถ้าตารางยังไม่ถูกสร้าง หรือ query ไม่ตรง schema จะไม่ให้หน้า crash
  $dbError = $e->getMessage();
  $dailyStats = [];
}

// -----------------------------
// DENSITY RULES (UI dot colors)
// -----------------------------
// สีเหมือน React: high=แดง, medium=เหลือง, low=เขียว, disabled=เทา/ซ่อน dot
function density_color(string $density): string {
  return match ($density) {
    'high' => 'bg-red-500',
    'medium' => 'bg-yellow-400',
    'low' => 'bg-green-500',
    default => 'bg-gray-200',
  };
}

function density_for_day(
  int $year,
  int $month,
  int $day,
  array $dailyStats,
  ?string $dbError
): string {
  $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
  $today = date('Y-m-d');

  // ปิดวันที่ผ่านมาแล้ว
  if ($dateStr < $today) return 'disabled';

  // ถ้า DB error หรือไม่มี slot ในวันนั้น ให้ disabled
  if ($dbError !== null) return 'disabled';
  $total = $dailyStats[$day]['total'] ?? 0;
  $remaining = $dailyStats[$day]['remaining'] ?? 0;
  if ($total <= 0) return 'disabled';

  $ratio = $remaining / $total; // 0..1

  // กำหนดเกณฑ์:
  // - เขียว (ว่างมาก): เหลือ > 50%
  // - เหลือง (ปานกลาง): เหลือ 10%..50%
  // - แดง (เต็ม/เกือบเต็ม): เหลือ < 10%
  if ($ratio > 0.5) return 'low';
  if ($ratio >= 0.1) return 'medium';
  return 'high';
}

render_header('Select Date');
?>

<div class="p-5 flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-500">
  <div class="flex-1">
    <div class="flex items-center gap-2 mb-5">
      <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
        <!-- Calendar Icon -->
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#0052CC]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="4" width="18" height="18" rx="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      </div>
      <div>
        <h2 class="text-xl font-bold text-gray-900">Select Date</h2>
        <p class="text-xs text-gray-500">Choose your vaccination day</p>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
      <div class="flex items-center justify-between mb-5">
        <?php
          $prev = strtotime(sprintf('%04d-%02d-01', $year, $month) . ' -1 month');
          $next = strtotime(sprintf('%04d-%02d-01', $year, $month) . ' +1 month');
        ?>
        <a
          href="booking_date.php?year=<?= (int)date('Y', $prev) ?>&month=<?= (int)date('n', $prev) ?>"
          class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 transition-colors"
          aria-label="Previous month"
        >
          <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"></path>
          </svg>
        </a>

        <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($monthName . ' ' . $year, ENT_QUOTES, 'UTF-8') ?></h3>

        <a
          href="booking_date.php?year=<?= (int)date('Y', $next) ?>&month=<?= (int)date('n', $next) ?>"
          class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 transition-colors"
          aria-label="Next month"
        >
          <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 18l6-6-6-6"></path>
          </svg>
        </a>
      </div>

      <?php if ($dbError !== null): ?>
        <!-- ถ้า schema ยังไม่พร้อม จะขึ้นข้อความนี้ (ปรับ/ลบได้) -->
        <div class="mb-4 p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-xs text-yellow-800">
          <div class="font-bold mb-1">Database not ready for calendar density</div>
          <div class="opacity-90">Please create `vac_time_slots` and `vac_appointments` (or adjust column names in this page). Calendar days are currently disabled.</div>
        </div>
      <?php endif; ?>

      <form method="post" class="grid grid-cols-7 gap-y-4 gap-x-2 mb-2">
        <?php foreach ($weekDays as $d): ?>
          <div class="text-center text-xs font-bold text-gray-400 uppercase tracking-wider"><?= htmlspecialchars($d, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
          <div class="h-10"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
          <?php
            $density = density_for_day($year, $month, $day, $dailyStats, $dbError);
            $isSelected = ($selectedDate === $day);
            $isDisabled = ($density === 'disabled');

            $numberClasses =
              "w-9 h-9 flex items-center justify-center rounded-full text-sm transition-all " .
              ($isSelected
                ? "bg-[#0052CC] text-white font-bold shadow-md"
                : ($isDisabled ? "text-gray-300" : "text-gray-700 font-medium group-hover:bg-blue-50"));

            $dotClasses =
              "w-1.5 h-1.5 rounded-full mt-1 absolute bottom-0 " .
              density_color($density) . " " .
              (($isSelected && $density !== 'disabled') ? "opacity-100" : "opacity-80") . " " .
              ($isDisabled ? "hidden" : "");
          ?>

          <button
            type="submit"
            name="selected_date"
            value="<?= (int)$day ?>"
            <?= $isDisabled ? 'disabled' : '' ?>
            class="relative flex flex-col items-center justify-center h-12 w-full group"
          >
            <div class="<?= htmlspecialchars($numberClasses, ENT_QUOTES, 'UTF-8') ?>">
              <?= (int)$day ?>
            </div>
            <div class="<?= htmlspecialchars($dotClasses, ENT_QUOTES, 'UTF-8') ?>"></div>
          </button>
        <?php endfor; ?>
      </form>
    </div>

    <div class="flex items-center justify-between text-xs font-semibold text-gray-600 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-green-500 shadow-sm"></span>
        Available
      </div>
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-yellow-400 shadow-sm"></span>
        Medium
      </div>
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-red-500 shadow-sm"></span>
        Full
      </div>
    </div>
  </div>

  <?php
    // ปุ่ม Next: disabled ถ้าไม่ได้เลือก หรือวันนั้นเต็ม (density = high)
    $disableNext = ($selectedDate === null) ||
      (density_for_day($year, $month, (int)$selectedDate, $dailyStats, $dbError) === 'high');

    /**
     * จุดที่ควรทำก่อนไปหน้าถัดไป (สำคัญมาก):
     * - ตรวจสอบซ้ำว่า slot ของวันนั้นยังมีคิวจริง (กัน race condition)
     * - อาจสร้าง booking draft / reserve slot ใน transaction
     */
  ?>

  <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex gap-3 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
    <a
      href="profile.php"
      class="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors active:scale-[0.98] text-center"
    >
      Back
    </a>

    <?php if ($disableNext): ?>
      <button
        type="button"
        disabled
        class="flex-1 bg-gray-300 text-gray-500 cursor-not-allowed font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
      >
        Next Step
      </button>
    <?php else: ?>
      <a
        href="booking_time.php?year=<?= (int)$year ?>&month=<?= (int)$month ?>&day=<?= (int)$selectedDate ?>"
        class="flex-1 bg-[#0052CC] hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98] text-center"
      >
        Next Step
      </a>
    <?php endif; ?>
  </div>
</div>

<?php render_footer(); ?>

