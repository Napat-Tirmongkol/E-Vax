<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';
session_start();

// API ส่วนตัวสำหรับบันทึก LINE Profile เบื้องต้น
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'init_user') {
    header('Content-Type: application/json');
    $lineId = $_POST['line_id'] ?? '';
    $displayName = $_POST['display_name'] ?? '';

    if ($lineId) {
        $_SESSION['line_user_id'] = $lineId;
        try {
            $pdo = db();
            // 1. ค้นหา User
            $stmt = $pdo->prepare("SELECT id, student_personnel_id FROM med_students WHERE line_user_id = :line_id LIMIT 1");
            $stmt->execute([':line_id' => $lineId]);
            $user = $stmt->fetch();

            if (!$user) {
                // กรณี User ใหม่
                $stmtInsert = $pdo->prepare("INSERT INTO med_students (line_user_id, full_name, status) VALUES (:line_id, :name, 'student')");
                $stmtInsert->execute([':line_id' => $lineId, ':name' => $displayName]);
                echo json_encode(['status' => 'new', 'is_complete' => false, 'has_booking' => false]);
            } else {
                // 2. เช็คว่ากรอกโปรไฟล์หรือยัง
                $isComplete = !empty($user['student_personnel_id']);
                
                // 3. เพิ่มการเช็ค: มีประวัติการจองที่ยังไม่ยกเลิกหรือไม่
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM vac_appointments WHERE student_id = :sid AND status IN ('confirmed', 'booked')");
                $stmtCheck->execute([':sid' => $user['id']]);
                $hasBooking = (int)$stmtCheck->fetchColumn() > 0;

                echo json_encode([
                    'status' => 'exists', 
                    'is_complete' => $isComplete,
                    'has_booking' => $hasBooking
                ]);
            }
            exit;
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}
render_header('Initializing...');
?>

<div class="flex flex-col items-center justify-center min-h-screen bg-[#f4f7fa]">
    <div class="w-16 h-16 border-4 border-[#0052CC] border-t-transparent rounded-full animate-spin"></div>
    <p class="mt-4 text-gray-500 font-prompt">กำลังตรวจสอบสถานะ...</p>
</div>

<script>
    // ในหน้า index.php (ส่วน JavaScript)
async function initLiff() {
    try {
        await liff.init({ liffId: '2008476166-yRYxeEJF' }); 
        
        if (!liff.isLoggedIn()) {
            liff.login();
        } else {
            const profile = await liff.getProfile();
            
            // ส่งข้อมูลไปบันทึกที่ Server ตามปกติ
            const formData = new FormData();
            formData.append('action', 'init_user');
            formData.append('line_id', profile.userId);
            formData.append('display_name', profile.displayName);
            
            const res = await fetch('./index.php', { method: 'POST', body: formData });
            const data = await res.json();

            // --- ส่วนที่เพิ่มเข้ามา: แยกทางไปตามพารามิเตอร์ URL ---
            const urlParams = new URLSearchParams(window.location.search);
            const targetApp = urlParams.get('app'); // รับค่า ?app=eborrow เป็นต้น

            if (targetApp === 'eborrow') {
                // ถ้ามาจากระบบยืมของ ให้ส่งไปหน้าของ e_Borrow
                window.location.replace('../e-borrow/home.php'); 
            } else {
                // ถ้าไม่มีพารามิเตอร์ หรือเป็น evax ให้ทำตาม Logic เดิม
                if (data.has_booking) {
                    window.location.replace('my_bookings.php');
                } else if (data.is_complete) {
                    window.location.replace('booking_date.php');
                } else {
                    window.location.replace('consent.php');
                }
            }
        }
    } catch (err) {
        console.error(err);
    }
}
    initLiff();
</script>
<?php require_once __DIR__ . '/footer.php'; render_footer(); ?>