<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camera_id = trim($_POST['camera_id']);
    $camera_name = trim($_POST['camera_name']);
    $location = trim($_POST['location']);
    if ($camera_id && $camera_name && $location) {
        $url = $supabase_url . "/rest/v1/camera";
        $data = [
            "camera_id" => $camera_id,
            "camera_name" => $camera_name,
            "location" => $location
        ];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
            "Content-Type: application/json",
            "Prefer: return=representation"
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code === 201) {
            $success = 'เพิ่มกล้องสำเร็จ!';
        } else {
            $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($response);
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มกล้อง</title>
    <link rel="stylesheet" href="css/add_admin.css">
</head>
<body>
    <h1>เพิ่มกล้องใหม่</h1>
    <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
    <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <label for="camera_id">รหัสกล้อง:</label>
        <input type="text" id="camera_id" name="camera_id" required>
        <label for="camera_name">ชื่อกล้อง:</label>
        <input type="text" id="camera_name" name="camera_name" required>
        <label for="location">ตำแหน่ง:</label>
        <input type="text" id="location" name="location" required>
        <button type="submit" class="btn-submit">บันทึก</button>
    </form>
    <br>
    <button class="btn-back" onclick="window.location.href='profile.php'">ย้อนกลับ</button>
</body>
</html> 