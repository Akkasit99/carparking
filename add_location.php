<?php
// ปิดการแสดงผล error
ini_set('display_errors', 0);
error_reporting(0);

session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}

require_once 'db_connect.php';

$success = $error = '';

function call_supabase($method, $url, $apiKey, $payload = null, $prefer = 'return=minimal') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $headers = [
        "apikey: {$apiKey}",
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json",
    ];
    if ($prefer) $headers[] = "Prefer: {$prefer}";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$http, $resp];
}

// โหลดข้อมูลกล้อง
list($httpCam, $respCam) = call_supabase(
    'GET',
    $supabase_url . "/rest/v1/camera?select=*",
    $supabase_key,
    null,
    null
);
$cameras = $httpCam === 200 ? json_decode($respCam, true) : [];

// โหลดข้อมูลสถานที่ (ใช้คอลัมน์ id, location_name, camera_id)
list($httpLoc, $respLoc) = call_supabase(
    'GET',
    $supabase_url . "/rest/v1/locations?select=id,location_name,camera_id",
    $supabase_key,
    null,
    null
);
$locations = $httpLoc === 200 ? json_decode($respLoc, true) : [];

// เพิ่มสถานที่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name'] ?? '');
    $camera_id     = trim($_POST['camera_id'] ?? '');

    if ($location_name !== '' && $camera_id !== '') {
        $data = ['location_name' => $location_name, 'camera_id' => $camera_id];
        list($httpAdd) = call_supabase(
            'POST',
            $supabase_url . "/rest/v1/locations",
            $supabase_key,
            $data,
            'return=minimal'
        );
        $success = ($httpAdd === 201 || $httpAdd === 204) ? "เพิ่มสถานที่สำเร็จ!" : "เกิดข้อผิดพลาดในการเพิ่มสถานที่ (HTTP {$httpAdd})";
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// ลบสถานที่ (ลบด้วยคีย์หลัก id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    $location_id = trim($_POST['location_id'] ?? '');
    if ($location_id !== '') {
        $url = $supabase_url . "/rest/v1/locations?id=eq." . urlencode($location_id);
        list($httpDel) = call_supabase('DELETE', $url, $supabase_key, null, 'return=minimal');
        $success = ($httpDel === 204) ? "ลบสถานที่สำเร็จ!" : "เกิดข้อผิดพลาดในการลบสถานที่ (HTTP {$httpDel})";
    } else {
        $error = "กรุณาเลือกสถานที่ที่จะลบ";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการสถานที่</title>
    <link rel="stylesheet" href="css/add_location.css">
</head>
<body>
    <h1>จัดการสถานที่</h1>

    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่มสถานที่ -->
    <h2>เพิ่มสถานที่</h2>
    <form method="post">
        <input type="hidden" name="add_location" value="1">

        <label for="location_name">ชื่อสถานที่:</label>
        <input type="text" id="location_name" name="location_name" placeholder="เช่น โรงพยาบาล" required>

        <label for="camera_id">กล้อง:</label>
        <select id="camera_id" name="camera_id" required>
            <option value="">เลือกกล้อง</option>
            <?php foreach ($cameras as $camera): ?>
                <option value="<?php echo htmlspecialchars(trim($camera['camera_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($camera['camera_name'] ?? ($camera['camera_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-submit">บันทึก</button>
    </form>

    <!-- ฟอร์มลบสถานที่ -->
    <h2>ลบสถานที่</h2>
    <form method="post" onsubmit="return confirm('ยืนยันการลบสถานที่นี้?');">
        <input type="hidden" name="delete_location" value="1">

        <label for="location_id">เลือกสถานที่ (ชื่อสถานที่ - กล้อง):</label>
        <select id="location_id" name="location_id" required>
            <option value="">เลือกสถานที่</option>
            <?php foreach ($locations as $loc): ?>
                <?php
                    $id   = htmlspecialchars($loc['id'] ?? '', ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($loc['location_name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $cam  = htmlspecialchars($loc['camera_id'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
                <option value="<?php echo $id; ?>"><?php echo "{$name} ({$cam})"; ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-delete">ลบ</button>
    </form>

    <br>
    <button class="btn-back" onclick="window.location.href='profile.php'">ย้อนกลับ</button>
</body>
</html>
