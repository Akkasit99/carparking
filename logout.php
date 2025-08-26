<?php
session_start();

// ล้างข้อมูล Session ทั้งหมด
session_unset();
session_destroy();

// ลบ Cookie ถ้ามี
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect ไปหน้า login พร้อมข้อความ
header('Location: index.php?logout=success');
exit();
?>
