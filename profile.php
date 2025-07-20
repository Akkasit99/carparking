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
require_once 'db_connect.php';

// ดึงข้อมูลผู้ใช้จาก Supabase REST API
$username = $_SESSION['user'];
$url = $supabase_url . "/rest/v1/users?username=eq." . urlencode($username);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $supabase_key",
    "Authorization: Bearer $supabase_key",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);
$userData = null;
$data = json_decode($response, true);
if (is_array($data) && count($data) > 0) {
    $userData = $data[0];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ข้อมูลผู้ดูแลระบบ</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <div class="profile-box">
        <h1>ข้อมูลผู้ใช้</h1>
        <?php if ($userData): ?>
            <div class="info">ชื่อผู้ใช้: <b><?php echo htmlspecialchars($userData['username']); ?></b></div>
            <div class="info">ชื่อลานจอดรถ: <b><?php echo htmlspecialchars($userData['parking_name']); ?></b></div>
            <div class="info">สถานะ: <b><?php echo htmlspecialchars($userData['position']); ?></b></div>
        <?php else: ?>
            <div class="info" style="color:red;">ไม่พบข้อมูลผู้ใช้</div>
        <?php endif; ?>
        <div>
            <button class="btn-back" onclick="window.location.href='dashboard.php'">ย้อนกลับ</button>
        </div>
        
    </div>
</body>
</html>
