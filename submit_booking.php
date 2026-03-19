<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: booking_date.php', true, 303);
  exit;
}

// ต้องมีโปรไฟล์ก่อน (ได้จาก save_profile.php)
$studentId = isset($_SESSION['evax_student_id']) ? (int)$_SESSION['evax_student_id'] : 0;
if ($studentId <= 0) {
  header('Location: profile.php', true, 303);
  exit;
}

$slotId = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
$slotDate = trim((string)($_POST['slot_date'] ?? ''));

if ($slotId <= 0 || $slotDate === '') {
  header('Location: booking_date.php', true, 303);
  exit;
}

// จำกัดรูปแบบวันที่
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $slotDate)) {
  header('Location: booking_date.php', true, 303);
  exit;
}

/**
 * สมมติ schema (คุณจะเพิ่ม):
 * - vac_time_slots: id, slot_date, start_time, end_time, max_capacity
 * - vac_appointments: id, slot_id, student_id, status, created_at
 *
 * หมายเหตุ: ถ้าชื่อคอลัมน์/ตารางไม่ตรง ให้แก้ SQL ด้านล่างให้ตรงกับ DB จริง
 */

try {
  $pdo = db();
  $pdo->beginTransaction();

  // lock slot row เพื่อกัน race condition
  $lockSql = "
    SELECT id, slot_date, start_time, end_time, max_capacity
    FROM vac_time_slots
    WHERE id = :id AND slot_date = :slot_date
    FOR UPDATE
  ";
  $stmt = $pdo->prepare($lockSql);
  $stmt->execute([':id' => $slotId, ':slot_date' => $slotDate]);
  $slot = $stmt->fetch();

  if (!$slot) {
    $pdo->rollBack();
    header('Location: booking_time.php?day=' . (int)substr($slotDate, 8, 2) . '&month=' . (int)substr($slotDate, 5, 2) . '&year=' . (int)substr($slotDate, 0, 4), true, 303);
    exit;
  }

  $maxCapacity = (int)$slot['max_capacity'];

  // count booked appointments for this slot (lock via same transaction + slot lock)
  $countSql = "
    SELECT COUNT(*) AS booked_count
    FROM vac_appointments
    WHERE slot_id = :slot_id
      AND (status = 'confirmed' OR status = 'booked')
  ";
  $stmt2 = $pdo->prepare($countSql);
  $stmt2->execute([':slot_id' => $slotId]);
  $bookedCount = (int)($stmt2->fetch()['booked_count'] ?? 0);

  if ($bookedCount >= $maxCapacity) {
    $pdo->rollBack();
    header('Location: booking_time.php?day=' . (int)substr($slotDate, 8, 2) . '&month=' . (int)substr($slotDate, 5, 2) . '&year=' . (int)substr($slotDate, 0, 4), true, 303);
    exit;
  }

  // insert appointment
  $insertSql = "
    INSERT INTO vac_appointments (slot_id, student_id, status, created_at)
    VALUES (:slot_id, :student_id, :status, NOW())
  ";
  $stmt3 = $pdo->prepare($insertSql);
  $stmt3->execute([
    ':slot_id' => $slotId,
    ':student_id' => $studentId,
    ':status' => 'confirmed',
  ]);

  $appointmentId = (int)$pdo->lastInsertId();

  $pdo->commit();

  // เก็บรายละเอียดสำหรับหน้า success
  $_SESSION['evax_last_booking'] = [
    'appointment_id' => $appointmentId,
    'slot_id' => $slotId,
    'slot_date' => $slotDate,
    'start_time' => (string)$slot['start_time'],
    'end_time' => (string)$slot['end_time'],
  ];

  header('Location: success.php?id=' . $appointmentId, true, 303);
  exit;
} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  header('Location: booking_date.php', true, 303);
  exit;
}

