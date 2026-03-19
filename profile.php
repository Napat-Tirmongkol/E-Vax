<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/footer.php';

// -----------------------------
// AJAX API: get_user by line_user_id
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['ajax_action'] ?? '') === 'get_user') {
  header('Content-Type: application/json; charset=utf-8');

  $lineUserId = trim((string)($_POST['line_user_id'] ?? ''));
  if ($lineUserId === '' || mb_strlen($lineUserId) > 255) {
    echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
    exit;
  }

  try {
    $pdo = db();
    $sql = "
      SELECT full_name, student_personnel_id, phone_number
      FROM med_students
      WHERE line_user_id = :line_user_id
      LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':line_user_id' => $lineUserId]);
    $row = $stmt->fetch();

    if (!$row) {
      echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
      exit;
    }

    echo json_encode([
      'success' => true,
      'full_name' => (string)($row['full_name'] ?? ''),
      'student_id' => (string)($row['student_personnel_id'] ?? ''),
      'phone' => (string)($row['phone_number'] ?? ''),
    ], JSON_UNESCAPED_UNICODE);
    exit;
  } catch (PDOException $e) {
    // ชั่วคราว: ส่ง error กลับเพื่อ debug (ปรับเป็น log ได้ภายหลัง)
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/**
 * หน้า Profile (แปลงจาก ProfilePage.tsx)
 *
 * จุดที่ต้องทำจริงกับระบบ:
 * - action ของฟอร์มควรชี้ไปที่สคริปต์บันทึกข้อมูล (เช่น save_profile.php)
 * - ควร validate / sanitize เพิ่มเติม และจัดการ session/login ตามระบบคุณ
 */

render_header('Patient Profile');
?>

<div class="p-5 flex flex-col h-full animate-in fade-in slide-in-from-right-4 duration-500">
  <!--
    หมายเหตุ: ย้าย footer buttons ให้อยู่ใน form เดียวกัน
    เพื่อให้การ submit ทำงานได้แน่นอนทุก browser
  -->
  <form id="profileForm" class="flex-1 flex flex-col" method="post" action="save_profile.php" autocomplete="on">
    <div class="flex-1 space-y-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Patient Profile</h2>
        <p class="text-sm text-gray-500 mt-1">Please enter your personal details below.</p>
      </div>

      <!--
        จุดที่ต้องสร้างสคริปต์บันทึกข้อมูล:
        - ไฟล์แนะนำ: save_profile.php (รับ POST แล้ว insert/update ตารางผู้ป่วย/ผู้รับวัคซีน)
        - ป้องกัน SQL Injection: ใช้ PDO prepared statements
        - อาจผูกกับตารางผู้ใช้เดิม เช่น med_students หรือสร้างตารางใหม่สำหรับ E-Vax
      -->
      <div class="space-y-5">
      <div class="space-y-1.5">
        <label class="text-sm font-semibold text-gray-700" for="full_name">Full Name</label>
        <input
          id="full_name"
          name="full_name"
          type="text"
          required
          placeholder="e.g. Somchai Jaidee"
          class="w-full p-4 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0052CC] focus:border-transparent outline-none transition-all placeholder:text-gray-400"
        />
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-semibold text-gray-700" for="id_number">ID / Passport Number</label>
        <input
          id="id_number"
          name="id_number"
          type="text"
          required
          placeholder="Enter 13-digit ID or Passport"
          class="w-full p-4 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0052CC] focus:border-transparent outline-none transition-all placeholder:text-gray-400"
        />
      </div>

      <div class="space-y-1.5">
        <label class="text-sm font-semibold text-gray-700" for="phone_number">Phone Number</label>
        <input
          id="phone_number"
          name="phone_number"
          type="tel"
          required
          placeholder="08X-XXX-XXXX"
          class="w-full p-4 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#0052CC] focus:border-transparent outline-none transition-all placeholder:text-gray-400"
        />
      </div>
      </div>
    </div>

    <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 flex gap-3 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
      <!-- ปรับลิงก์ Back ให้ตรงกับ flow -->
      <a
        href="consent.php"
        class="px-6 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors active:scale-[0.98] text-center"
      >
        Back
      </a>

      <button
        type="submit"
        class="flex-1 bg-[#0052CC] hover:bg-blue-700 text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
      >
        Save &amp; Continue
      </button>
    </div>
  </form>
</div>

<?php render_footer(); ?>

<script>
  (async function () {
    try {
      if (!window.liff) return;
      await liff.init({ liffId: '2008476166-yRYxeEJF' });

      if (!liff.isLoggedIn()) return;

      const profile = await liff.getProfile();
      const form = document.getElementById('profileForm');
      const fullNameInput = document.querySelector('input[name="full_name"]');
      const idNumberInput = document.querySelector('input[name="id_number"]');
      const phoneInput = document.querySelector('input[name="phone_number"]');

      if (form && profile && profile.userId) {
        let hidden = form.querySelector('input[name="line_user_id"]');
        if (!hidden) {
          hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = 'line_user_id';
          form.appendChild(hidden);
        }
        hidden.value = profile.userId;

        // ดึงข้อมูลเดิมจากฐานข้อมูล (full_name, student_personnel_id, phone_number)
        const body = new URLSearchParams();
        body.set('ajax_action', 'get_user');
        body.set('line_user_id', profile.userId);

        const res = await fetch('profile.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: body.toString(),
          credentials: 'same-origin'
        });

        if (res.ok) {
          const data = await res.json();
          if (data && data.success === true) {
            // พบข้อมูลใน DB: เติมครบทั้ง 3 ช่อง แล้วล็อกแก้ไม่ได้
            if (fullNameInput && data.full_name) {
              fullNameInput.value = data.full_name;
              fullNameInput.readOnly = true;
              fullNameInput.classList.add('bg-gray-50');
            }
            if (idNumberInput && data.student_id) {
              idNumberInput.value = data.student_id;
              idNumberInput.readOnly = true;
              idNumberInput.classList.add('bg-gray-50');
            }
            if (phoneInput && data.phone) {
              phoneInput.value = data.phone;
              phoneInput.readOnly = true;
              phoneInput.classList.add('bg-gray-50');
            }
          } else {
            // ไม่พบข้อมูล: เติมชื่อจาก LIFF ให้ผู้ใช้ใหม่
            if (fullNameInput && profile && profile.displayName) {
              fullNameInput.value = profile.displayName;
            }
          }
        }
      }
    } catch (e) {
      console.error(e);
    }
  })();
</script>

