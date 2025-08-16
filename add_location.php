<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}

require_once 'db_connect.php';

// ดึงข้อมูล camera จาก database
$url = $supabase_url . "/rest/v1/camera?select=*";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $supabase_key",
    "Authorization: Bearer $supabase_key",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);
$cameras = json_decode($response, true);

$success = $error = '';

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_name = trim($_POST['location_name']);
    $camera_id = trim($_POST['camera_id']);
    
    if (!empty($location_name) && !empty($camera_id)) {
        // ลองใช้วิธี REST API อีกครั้งแต่เพิ่ม header พิเศษ
        $data = [
            'location_name' => $location_name,
            'camera_id' => $camera_id
        ];
        
        $url = $supabase_url . "/rest/v1/locations";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
            "Content-Type: application/json",
            "Prefer: return=minimal",
            "X-Client-Info: supabase-php/1.0"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Debug: แสดงข้อมูลที่ส่งและ response
        if ($httpCode === 201) {
            $success = "เพิ่มสถานที่สำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาดในการเพิ่มสถานที่ (HTTP Code: $httpCode)";
            // แสดงข้อมูล debug
            echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
            echo "<strong>Debug Info:</strong><br>";
            echo "Data sent: " . json_encode($data) . "<br>";
            echo "Response: " . $response . "<br>";
            echo "HTTP Code: " . $httpCode . "<br>";
            echo "<br><strong>วิธีแก้ไข:</strong><br>";
            echo "1. ไปที่ Supabase Dashboard > Settings > Database<br>";
            echo "2. ปิด Row Level Security (RLS) สำหรับตาราง locations<br>";
            echo "3. หรือสร้าง RLS policy ที่อนุญาตการ INSERT<br>";
            echo "</div>";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มสถานที่</title>
    <link rel="stylesheet" href="css/add_admin.css">
    <script>
        // ป้องกันการถามยืนยันเมื่อรีเฟรชหน้าเว็บ
        window.addEventListener('beforeunload', function(e) {
            // ไม่ต้องแสดงข้อความยืนยัน
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
    <h1>เพิ่มสถานที่ใหม่</h1>
    <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
    <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
        <label for="location_name">ชื่อสถานที่:</label>
        <input type="text" id="location_name" name="location_name" required 
               value="<?php echo isset($_POST['location_name']) ? htmlspecialchars($_POST['location_name']) : ''; ?>">
        
        <label for="camera_id">กล้อง:</label>
        <select id="camera_id" name="camera_id" required>
            <option value="">เลือกกล้อง</option>
            <?php if (is_array($cameras)): ?>
                <?php foreach ($cameras as $camera): ?>
                    <option value="<?php echo htmlspecialchars($camera['camera_id']); ?>" 
                            <?php echo (isset($_POST['camera_id']) && $_POST['camera_id'] == $camera['camera_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($camera['camera_name']); ?> (ID: <?php echo htmlspecialchars($camera['camera_id']); ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        
        <button type="submit" class="btn-submit">บันทึก</button>
    </form>
    <br>
    <button class="btn-back" onclick="window.location.href='profile.php'">ย้อนกลับ</button>
</body>
</html>
