<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camera_id = $_POST['camera_id'] ?? '';
    $camera_name = $_POST['camera_name'] ?? '';
    $location = $_POST['location'] ?? '';

    // ตัวอย่างการบันทึกลง MySQL (ถ้าใช้ Supabase REST API ให้ปรับตามนั้น)
    $stmt = $conn->prepare("INSERT INTO camera (camera_id, camera_name, location) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('sss', $camera_id, $camera_name, $location);
        if ($stmt->execute()) {
            $message = '<span style="color:green;">เพิ่มกล้องสำเร็จ</span>';
        } else {
            $message = '<span style="color:red;">เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</span>';
        }
        $stmt->close();
    } else {
        $message = '<span style="color:red;">เกิดข้อผิดพลาดในการเตรียมคำสั่ง</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มกล้อง</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <div class="profile-box">
        <h1>เพิ่มกล้องใหม่</h1>
        <?php if ($message) echo '<div class="info">' . $message . '</div>'; ?>
        <form action="add_camera.php" method="post">
            <div class="info">
                <label for="camera_id">รหัสกล้อง:</label>
                <input type="text" id="camera_id" name="camera_id" required>
            </div>
            <div class="info">
                <label for="camera_name">ชื่อกล้อง:</label>
                <input type="text" id="camera_name" name="camera_name" required>
            </div>
            <div class="info">
                <label for="location">ตำแหน่ง:</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div>
                <button type="submit">บันทึก</button>
                <button type="button" onclick="window.location.href='profile.php'">ย้อนกลับ</button>
            </div>
        </form>
    </div>
</body>
</html> 