<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ฟอร์มเพิ่มกล้อง
    if (isset($_POST['camera_id'], $_POST['camera_name'], $_POST['location'])) {
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

    // ฟอร์มลบกล้อง
    if (isset($_POST['delete_camera'])) {
        $delete_id = $_POST['delete_id'];
        if ($delete_id) {
            $url = $supabase_url . "/rest/v1/camera?camera_id=eq." . urlencode($delete_id);
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
        } else {
            $error = 'กรุณาเลือก ID กล้องที่ต้องการลบ';
        }
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

$cameras = getAllCameras($supabase_url, $supabase_key);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการกล้อง</title>
    <link rel="stylesheet" href="css/add_admin.css">
</head>

<body>
    <div class="container">
        <h1>จัดการกล้อง</h1>

        <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

        <!-- ฟอร์มเพิ่มกล้อง -->
        <h2>เพิ่มกล้อง</h2>
        <form method="post" autocomplete="off">
            <label for="camera_id">รหัสกล้อง:</label>
            <input type="text" id="camera_id" name="camera_id" required>
            <label for="camera_name">ชื่อกล้อง:</label>
            <input type="text" id="camera_name" name="camera_name" required>
            <label for="location">ตำแหน่ง:</label>
            <input type="text" id="location" name="location" required>
            <button type="submit" class="btn-submit">บันทึก</button>
        </form>

        <!-- ฟอร์มลบกล้อง -->
        <h2>ลบกล้อง</h2>
        <form method="post" autocomplete="off" onsubmit="return confirm('ยืนยันการลบกล้องนี้?');">
            <label for="delete_id">เลือก ID กล้อง:</label>
            <select id="delete_id" name="delete_id" required>
                <option value="">-- เลือกกล้อง --</option>
                <?php
                foreach ($cameras as $cam) {
                    echo '<option value="'.htmlspecialchars($cam['camera_id']).'">'.
                        htmlspecialchars($cam['camera_id']).' - '.htmlspecialchars($cam['camera_name']).'</option>';
                }
                ?>
            </select>
            <button type="submit" name="delete_camera" class="btn-submit">ลบกล้อง</button>
        </form>

        <div class="button-container">
            <button class="btn-back" onclick="window.location.href='profile.php'">ย้อนกลับ</button>
        </div>
    </div>
</body>
</html>
