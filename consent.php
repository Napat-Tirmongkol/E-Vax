<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php'; // เพิ่ม config เพื่อให้ต่อ DB ได้
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/footer.php';

session_start();

// -----------------------------
// AJAX API: เช็คประวัติการจองจาก LINE ID
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['ajax_action'] ?? '') === 'check_booking') {
  header('Content-Type: application/json; charset=utf-8');
  $lineUserId = trim((string)($_POST['line_user_id'] ?? ''));
  
  if ($lineUserId === '') {
    echo json_encode(['has_booking' => false]);
    exit;
  }

  try {
    $pdo = db();
    // 1. หารหัสนักศึกษาจาก line_user_id
    $stmt = $pdo->prepare("SELECT id FROM med_students WHERE line_user_id = :line_user_id LIMIT 1");
    $stmt->execute([':line_user_id' => $lineUserId]);
    $user = $stmt->fetch();

    if ($user && isset($user['id'])) {
      $studentId = (int)$user['id'];
      
      // 2. เช็คว่ามีคิวที่ยังไม่ถูกยกเลิกหรือไม่
      $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM vac_appointments 
        WHERE student_id = :student_id AND status IN ('confirmed', 'booked')
      ");
      $stmtCheck->execute([':student_id' => $studentId]);
      $hasBooking = (int)$stmtCheck->fetchColumn() > 0;

      if ($hasBooking) {
        // ดึง session ไว้ให้หน้า my_bookings ใช้ต่อ
        $_SESSION['evax_student_id'] = $studentId;
        echo json_encode(['has_booking' => true]);
        exit;
      }
    }
    echo json_encode(['has_booking' => false]);
    exit;
  } catch (PDOException $e) {
    echo json_encode(['has_booking' => false, 'error' => $e->getMessage()]);
    exit;
  }
}

// -----------------------------
// INPUT / STATE (ฟอร์มปกติ)
// -----------------------------
$agreed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
  $agreed = isset($_POST['agreed']) && $_POST['agreed'] === '1';
  if ($agreed) {
    header('Location: profile.php', true, 303);
    exit;
  }
}

render_header('Terms & Conditions');
?>

<div class="p-5 flex flex-col h-full animate-in fade-in slide-in-from-bottom-4 duration-500">
  <div class="flex-1">
    <div class="flex items-center gap-2 mb-4 text-[#0052CC]">
      <!-- Shield icon -->
      <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z"></path>
        <path d="M12 8v4"></path>
        <path d="M12 16h.01"></path>
      </svg>
      <h2 class="text-xl font-bold text-gray-800">Terms &amp; Conditions</h2>
    </div>

  <div class="prose prose-sm text-gray-600 mb-6 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm max-h-[50vh] overflow-y-auto">
  <h3 class="text-lg font-bold text-gray-900 mb-3">ข้อตกลงและเงื่อนไขการใช้บริการ</h3>
  <p class="mb-3">ยินดีต้อนรับเข้าสู่ระบบจองคิวรับวัคซีน (E-Vax) กรุณาอ่านและทำความเข้าใจเงื่อนไขด้านล่างนี้ก่อนกดยอมรับ</p>
  
  <h4 class="font-bold text-gray-800 mt-4 mb-2">1. ข้อมูลส่วนบุคคลที่เราจัดเก็บ</h4>
  <ul class="list-disc pl-5 mb-3 space-y-1">
    <li>ชื่อ-นามสกุล</li>
    <li>รหัสนักศึกษา / รหัสประจำตัวบุคลากร</li>
    <li>หมายเลขโทรศัพท์ติดต่อ</li>
    <li>ข้อมูลบัญชี LINE (LINE User ID)</li>
  </ul>

  <h4 class="font-bold text-gray-800 mt-4 mb-2">2. วัตถุประสงค์ในการเก็บและใช้ข้อมูล</h4>
  <ul class="list-disc pl-5 mb-3 space-y-1">
    <li>เพื่อตรวจสอบสิทธิ์และยืนยันตัวตนในการเข้ารับบริการ</li>
    <li>เพื่อบริหารจัดการคิวและวันเวลานัดหมาย</li>
    <li>เพื่อส่งข้อความแจ้งเตือนผ่านแอปพลิเคชัน LINE</li>
  </ul>

  <h4 class="font-bold text-gray-800 mt-4 mb-2">3. การรักษาความปลอดภัย (PDPA)</h4>
  <p class="mb-3">ข้อมูลของท่านจะถูกเก็บรักษาอย่างปลอดภัยตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล (PDPA) จะไม่มีการนำไปเผยแพร่หรือขายต่อ และเข้าถึงได้เฉพาะบุคลากรทางการแพทย์และผู้ดูแลระบบเท่านั้น</p>

  <h4 class="font-bold text-gray-800 mt-4 mb-2">4. สิทธิของผู้ใช้งาน</h4>
  <p>ท่านมีสิทธิ์ในการตรวจสอบ และยกเลิกการนัดหมายได้ด้วยตนเองผ่านระบบ หรือติดต่อเจ้าหน้าที่เพื่อขอลบข้อมูล</p>
</div>
    </div>

    <form id="consentForm" method="post">
      <label class="flex items-start gap-3 p-4 bg-white rounded-2xl border border-gray-100 shadow-sm cursor-pointer hover:bg-gray-50 transition-colors">
        <input
          type="checkbox"
          name="agreed"
          value="1"
          required
          class="mt-1 w-5 h-5 rounded border-gray-300 text-[#0052CC] focus:ring-[#0052CC] cursor-pointer"
          <?= $agreed ? 'checked' : '' ?>
        />
        <span class="text-sm text-gray-700 font-medium leading-tight">I have read, understood, and agree to the terms and conditions and privacy policy.</span>
      </label>
    </form>
  </div>

  <div class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-4 bg-white border-t border-gray-100 z-20 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
    <button
      type="submit"
      form="consentForm"
      id="continueBtn"
      class="w-full bg-[#0052CC] hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition-all shadow-sm active:scale-[0.98]"
      disabled
    >
      I Agree, Continue
    </button>
  </div>
</div>

<?php render_footer(); ?>

<script>
  (async function () {
    try {
      if (!window.liff) return;
      await liff.init({ liffId: '2008476166-yRYxeEJF' }); 

      // 1. ถ้ายังไม่ล็อกอิน ให้บังคับเด้งไปล็อกอิน LINE
      if (!liff.isLoggedIn()) {
        liff.login({ redirectUri: window.location.href });
        return;
      }

      // 2. เมื่อล็อกอินแล้ว ดึงข้อมูลโปรไฟล์
      const profile = await liff.getProfile();
      if (profile && profile.userId) {
        
        // 3. ยิง AJAX ไปถาม PHP ด้านบนว่ามีคิวหรือยัง
        const body = new URLSearchParams();
        body.set('ajax_action', 'check_booking');
        body.set('line_user_id', profile.userId);

        const res = await fetch('consent.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: body.toString(),
          credentials: 'same-origin'
        });

        if (res.ok) {
          const data = await res.json();
          // 4. ถ้ามีคิวแล้ว ให้เปลี่ยนหน้าไป My Bookings ทันที
          if (data && data.has_booking === true) {
            window.location.replace('my_bookings.php');
            return; // หยุดการทำงานส่วนอื่น
          }
        }
      }
      
    } catch (e) {
      console.error('LIFF Init Error:', e);
    }
  })();

  // สคริปต์สำหรับจัดการปุ่ม I Agree
  document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.querySelector('input[name="agreed"]');
    const continueBtn = document.getElementById('continueBtn');
    
    if (checkbox && continueBtn) {
      checkbox.addEventListener('change', (e) => {
        continueBtn.disabled = !e.target.checked;
      });
    }
  });
</script>

