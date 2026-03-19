<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: profile.php', true, 303);
  exit;
}

// -----------------------------
// INPUT VALIDATION
// -----------------------------
$fullName = trim((string)($_POST['full_name'] ?? ''));
$idNumber = trim((string)($_POST['id_number'] ?? ''));
$phoneNumber = trim((string)($_POST['phone_number'] ?? ''));
$lineUserId = trim((string)($_POST['line_user_id'] ?? ''));

if ($fullName === '' || $idNumber === '' || $phoneNumber === '') {
  // เบื้องต้น redirect กลับ (สามารถทำ error UI เพิ่มภายหลัง)
  header('Location: profile.php', true, 303);
  exit;
}

// จำกัดความยาวแบบปลอดภัย
$fullName = mb_substr($fullName, 0, 255);
$idNumber = mb_substr($idNumber, 0, 100);
$phoneNumber = mb_substr($phoneNumber, 0, 50);
$lineUserId = $lineUserId === '' ? null : mb_substr($lineUserId, 0, 255);

// -----------------------------
// UPSERT INTO med_students
// -----------------------------
/**
 * ตารางจาก e_Borrow.sql:
 * med_students(
 *   id PK,
 *   full_name,
 *   student_personnel_id,
 *   phone_number,
 *   ...อื่นๆ
 * )
 *
 * เราจะ map:
 * - full_name -> full_name
 * - id_number (จากฟอร์ม) -> student_personnel_id
 * - phone_number -> phone_number
 *
 * หมายเหตุ: ใน SQL dump ไม่มี UNIQUE ของ student_personnel_id
 * ดังนั้นเราจะ "หา record ล่าสุด" ด้วย student_personnel_id ก่อน แล้ว update ถ้ามี
 */

try {
  $pdo = db();

  $pdo->beginTransaction();

  $selectSql = "
    SELECT id
    FROM med_students
    WHERE student_personnel_id = :student_personnel_id
    ORDER BY id DESC
    LIMIT 1
  ";
  $stmt = $pdo->prepare($selectSql);
  $stmt->execute([':student_personnel_id' => $idNumber]);
  $existing = $stmt->fetch();

  if ($existing && isset($existing['id'])) {
    $studentId = (int)$existing['id'];

    $updateSql = "
      UPDATE med_students
      SET full_name = :full_name,
          phone_number = :phone_number,
          line_user_id = :line_user_id
      WHERE id = :id
      LIMIT 1
    ";
    $stmt2 = $pdo->prepare($updateSql);
    $stmt2->execute([
      ':full_name' => $fullName,
      ':phone_number' => $phoneNumber,
      ':line_user_id' => $lineUserId,
      ':id' => $studentId,
    ]);
  } else {
    $insertSql = "
      INSERT INTO med_students (line_user_id, full_name, status, student_personnel_id, phone_number)
      VALUES (:line_user_id, :full_name, :status, :student_personnel_id, :phone_number)
    ";
    $stmt3 = $pdo->prepare($insertSql);
    $stmt3->execute([
      ':line_user_id' => $lineUserId,
      ':full_name' => $fullName,
      ':status' => 'student',
      ':student_personnel_id' => $idNumber,
      ':phone_number' => $phoneNumber,
    ]);

    $studentId = (int)$pdo->lastInsertId();
  }

  $pdo->commit();

  // เก็บใน session เพื่อใช้ตอน submit booking
  $_SESSION['evax_student_id'] = $studentId;
  $_SESSION['evax_full_name'] = $fullName;
  $_SESSION['evax_id_number'] = $idNumber;
  $_SESSION['evax_phone_number'] = $phoneNumber;
  $_SESSION['evax_line_user_id'] = $lineUserId;

  header('Location: booking_date.php');
  exit;
} catch (PDOException $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  // ชั่วคราว: แสดง error เพื่อ debug ตามที่ขอ
  die($e->getMessage());
}

