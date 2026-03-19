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
    $pictureUrl = $_POST['picture_url'] ?? '';

    if ($lineId) {
        $_SESSION['line_user_id'] = $lineId;
        try {
            $pdo = db();
            // ตรวจสอบว่ามี User นี้หรือยัง ถ้าไม่มีให้ Insert ถ้ามีแล้วอาจจะ Update Profile เล็กน้อย
            $stmt = $pdo->prepare("SELECT id, student_personnel_id FROM med_students WHERE line_user_id = :line_id LIMIT 1");
            $stmt->execute([':line_id' => $lineId]);
            $user = $stmt->fetch();

            if (!$user) {
                $stmtInsert = $pdo->prepare("INSERT INTO med_students (line_user_id, full_name, status) VALUES (:line_id, :name, 'student')");
                $stmtInsert->execute([':line_id' => $lineId, ':name' => $displayName]);
                echo json_encode(['status' => 'new']);
            } else {
                // ถ้ามีข้อมูลครบ (เคยกรอก Profile แล้ว) อาจจะข้ามไปหน้า My Bookings เลยก็ได้
                $isComplete = !empty($user['student_personnel_id']);
                echo json_encode(['status' => 'exists', 'is_complete' => $isComplete]);
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
    <p class="mt-4 text-gray-500 font-prompt">กำลังเตรียมความพร้อม...</p>
</div>

<script>
    async function initLiff() {
        try {
            // 1. Initialize LIFF
            await liff.init({ liffId: '2009544277-h9uspZgS' }); 
            
            if (!liff.isLoggedIn()) {
                // ระบุ URL แบบเต็มเพื่อป้องกัน Error 'replace'
                liff.login({ redirectUri: 'https://healthycampus.rsu.ac.th/e-vax/index.php' });
                return;
            } else {
                const profile = await liff.getProfile();
                const formData = new FormData();
                formData.append('action', 'init_user');
                formData.append('line_id', profile.userId);
                formData.append('display_name', profile.displayName);
                formData.append('picture_url', profile.pictureUrl || '');

                // 2. เรียกไฟล์ตัวเองผ่าน Fetch (ใช้ ./ เพื่อความชัวร์)
                const res = await fetch('./index.php', { 
                    method: 'POST', 
                    body: formData 
                });

                if (!res.ok) throw new Error('ไม่สามารถติดต่อ Server ได้ (Status: ' + res.status + ')');

                const data = await res.json();
                
                if (data.is_complete) {
                    window.location.replace('my_bookings.php');
                } else {
                    window.location.replace('consent.php');
                }
            }
        } catch (err) {
            console.error('LIFF Error:', err);
            // ถ้า Error ยังขึ้น 'replace' ให้ลองล้าง Cache ในแอป LINE ดูครับ
        }
    }
    initLiff();
</script>
<?php require_once __DIR__ . '/footer.php'; render_footer(); ?>