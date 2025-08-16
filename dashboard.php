<?php
session_start();
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>alert('ออกจากระบบเรียบร้อยแล้ว'); window.location.href = 'index.php';</script>";
    exit();
}
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ข้อมูลลานจอดรถ</title>
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

  <h1>ข้อมูลลานจอดรถ</h1>

  <button class="btn" onclick="window.location.href='entrance.php'">ข้อมูลรถทางเข้า</button>
  <button class="btn" onclick="window.location.href='exit.php'">ข้อมูลรถทางออก</button>
  <button class="btn" onclick="window.location.href='parking_lot.php'">ข้อมูลรถที่เข้าลานจอด</button>
  <button class="btn btn-yellow" onclick="window.location.href='add_admin.php'">เพิ่มผู้ดูแลระบบ</button>
  <button class="btn btn-red" onclick="window.location.href='dashboard.php?logout=1'">ออกจากระบบ</button>
  <button class="btn profile-btn" onclick="window.location.href='profile.php'">ข้อมูลผู้ดูแลระบบ</button>
  

</body>
</html>
