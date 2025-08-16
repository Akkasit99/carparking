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

// ฟังก์ชันลบกล้อง
if (isset($_POST['delete_camera'])) {
    $delete_id = $_POST['delete_id'];
    $url = $supabase_url . "/rest/v1/camera?id=eq." . urlencode($delete_id);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key"
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code === 204) {
        $success = 'ลบกล้องสำเร็จ!';
    } else {
        $error = 'เกิดข้อผิดพลาดในการลบ: ' . htmlspecialchars($response);
    }
}

// ฟังก์ชันดึงข้อมูลกล้องทั้งหมด
function getAllCameras($supabase_url, $supabase_key) {
    $url = $supabase_url . "/rest/v1/camera?select=*";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key"
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    return [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มกล้อง</title>
    <link rel="stylesheet" href="css/add_admin.css">
    <style>
        .button-container {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
        }
        

        

    </style>
    <script>
        // ป้องกันการถามยืนยันเมื่อรีเฟรชหน้าเว็บ
        window.addEventListener('beforeunload', function(e) {
            delete e['returnValue'];
        });
        
        // จัดการการรีเฟรชด้วย Ctrl+R
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                window.location.reload();
            }
        });
        

    </script>
</head>
<body>
    <div class="container">
        <h1>เพิ่มกล้อง</h1>
        
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
        
        <div class="button-container">
            <button class="btn-back" onclick="window.location.href='profile.php'">ย้อนกลับ</button>
        </div>
    </div>
    

</body>
</html>
