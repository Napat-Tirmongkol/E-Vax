<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/footer.php';

session_start();

// -----------------------------
// INPUT: date from booking_date.php
// -----------------------------
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$day = isset($_GET['day']) ? (int)$_GET['day'] : (int)date('j');

if ($year < 2000 || $year > 2100) $year = (int)date('Y');
if ($month < 1 || $month > 12) $month = (int)date('n');
if ($day < 1 || $day > 31) $day = (int)date('j');

$dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
$dateLabel = date('j F Y', strtotime($dateStr));

// -----------------------------
// FETCH TIME SLOTS + REMAINING
// -----------------------------
$slots = [];
$dbError = null;

/**
 * ต้องมีตาราง:
 * - vac_time_slots (ts): id, slot_date, start_time, end_time, max_capacity
 * - vac_appointments (ap): id, slot_id, status
 *
 * หมายเหตุ: ถ้าชื่อคอลัมน์คุณไม่ตรง ให้ปรับ SQL ด้านล่าง
 */

try {
  $pdo = db();

  $sql = "
    SELECT
      ts.id,
      ts.start_time,
      ts.end_time,
      ts.max_capacity,
      COALESCE(booked.booked_count, 0) AS booked_count,
      GREATEST(ts.max_capacity - COALESCE(booked.booked_count, 0), 0) AS remaining
    FROM vac_time_slots ts
    LEFT JOIN (
      SELECT ap.slot_id, COUNT(*) AS booked_count
      FROM vac_appointments ap
      WHERE (ap.status = 'confirmed' OR ap.status = 'booked')
      GROUP BY ap.slot_id
    ) booked ON booked.slot_id = ts.id
    WHERE ts.slot_date = :slot_date
    ORDER BY ts.start_time ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':slot_date' => $dateStr]);
  $slots = $stmt->fetchAll();
} catch (Throwable $e) {
  $dbError = $e->getMessage();
  $slots = [];
}

render_header('Select Time');
?>

<div class="p-5 flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-500">
  <div class="flex-1">
    <div class="text-center mb-6 bg-white p-4 rounded-2xl border border-blue-100 shadow-sm relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-[#0052CC]"></div>
      <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Selected Date</p>
      <h2 class="text-xl font-bold text-[#0052CC]"><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></h2>
    </div>

    <div class="flex items-center gap-2 mb-5">
      <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
        <!-- Clock icon -->
        <svg viewBox="0 0 24 24" class="w-5 h-5 text-[#0052CC]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 6v6l4 2"></path>
        </svg>
      </div>
      <div>
        <h2 class="text-xl font-bold text-gray-900">Select Time</h2>
        <p class="text-xs text-gray-500">Choose an available slot</p>
      </div>
    </div>

    <?php if ($dbError !== null): ?>
      <div class="mb-4 p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-xs text-yellow-800">
        <div class="font-bold mb-1">Database not ready for time slots</div>
        <div class="opacity-90">Please create `vac_time_slots` and `vac_appointments` (or adjust column names). No slots can be shown now.</div>
      </div>
    <?php endif; ?>

    <form method="post" action="submit_booking.php">
      <input type="hidden" name="slot_date" value="<?= htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') ?>" />

      <div class="space-y-3.5">
        <?php if (count($slots) === 0): ?>
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-sm text-gray-600">
            No time slots available for this date.
          </div>
        <?php else: ?>
          <?php foreach ($slots as $slot): ?>
            <?php
              $slotId = (int)$slot['id'];
              $start = (string)$slot['start_time'];
              $end = (string)$slot['end_time'];
              $remaining = (int)$slot['remaining'];
              $max = (int)$slot['max_capacity'];
              $isFull = ($remaining <= 0);

              $timeLabel = substr($start, 0, 5) . ' - ' . substr($end, 0, 5);
              $cardClasses =
                "w-full text-left p-5 rounded-2xl border-2 transition-all flex items-center justify-between group " .
                ($isFull
                  ? "bg-gray-50 border-gray-100 opacity-60 cursor-not-allowed"
                  : "bg-white border-transparent shadow-sm hover:border-blue-200 hover:shadow-md");

              $titleClasses = "font-bold text-lg mb-0.5 " . ($isFull ? "text-gray-400" : "text-gray-800");

              $pillClasses =
                "flex items-center gap-1.5 text-xs font-bold px-3.5 py-1.5 rounded-full border " .
                ($isFull ? "bg-gray-100 text-gray-500 border-gray-200" : "bg-green-50 text-green-700 border-green-200");
            ?>

            <label class="<?= htmlspecialchars($cardClasses, ENT_QUOTES, 'UTF-8') ?>">
              <div>
                <h3 class="<?= htmlspecialchars($titleClasses, ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?>
                </h3>
                <?php if (!$isFull): ?>
                  <p class="text-xs text-gray-500 flex items-center gap-1">
                    <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="M12 6v6l4 2"></path>
                    </svg>
                    Duration: 1 Hour
                  </p>
                <?php endif; ?>
              </div>

              <div class="flex items-center gap-3">
                <div class="<?= htmlspecialchars($pillClasses, ENT_QUOTES, 'UTF-8') ?>">
                  <?php if ($isFull): ?>
                    Fully Booked
                  <?php else: ?>
                    <!-- Users icon -->
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                      <circle cx="9" cy="7" r="4"></circle>
                      <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                      <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <?= (int)$remaining ?> left
                  <?php endif; ?>
                </div>

                <input
                  type="radio"
                  name="slot_id"
                  value="<?= (int)$slotId ?>"
                  class="w-5 h-5 text-[#0052CC] focus:ring-[#0052CC]"
                  <?= $isFull ? 'disabled' : '' ?>
                  required
                />
              </div>
            </label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex gap-3 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <a
          href="booking_date.php?year=<?= (int)$year ?>&month=<?= (int)$month ?>&selected_date=<?= (int)$day ?>"
          class="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors active:scale-[0.98] text-center"
        >
          Back
        </a>

        <button
          type="submit"
          class="flex-1 flex items-center justify-center gap-2 bg-[#0052CC] hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
          <?= (count($slots) === 0 || $dbError !== null) ? 'disabled' : '' ?>
        >
          Confirm Time
          <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14"></path>
            <path d="M12 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </form>
  </div>
</div>

<?php render_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // หาฟอร์มในหน้าเว็บ (ดักการกด Submit)
    const bookingForm = document.querySelector('form');
    
    if (bookingForm) {
      bookingForm.addEventListener('submit', function(e) {
        e.preventDefault(); // หยุดการส่งฟอร์มทันทีเพื่อโชว์ Popup ก่อน

        Swal.fire({
          title: 'ยืนยันการจองเวลา?',
          text: 'กรุณาตรวจสอบวันและเวลาให้ถูกต้องก่อนกดยืนยัน',
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#0052CC',
          cancelButtonColor: '#6B7280', // สีเทาสำหรับปุ่มยกเลิก
          confirmButtonText: 'ยืนยันการจอง',
          cancelButtonText: 'ยกเลิก',
          reverseButtons: true, // สลับให้ปุ่มยืนยันอยู่ด้านขวา (เหมือน iOS)
          customClass: {
            title: 'font-prompt',
            popup: 'font-prompt rounded-2xl',
            confirmButton: 'font-prompt rounded-xl px-6 py-3',
            cancelButton: 'font-prompt rounded-xl px-6 py-3'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            // ถ้ากดยืนยัน ให้ส่งข้อมูลฟอร์มจริงๆ
            bookingForm.submit(); 
          }
        });
      });
    }
  });
</script>
