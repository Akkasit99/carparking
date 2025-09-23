<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit();
}

$username = $_SESSION['user'];
$avatarDir = 'uploads/avatars/';
$possibleExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];

$deleted = false;
// ลบไฟล์ avatar ของผู้ใช้คนนี้
foreach ($possibleExtensions as $ext) {
    $filePath = $avatarDir . $username . '_avatar.' . $ext;
    if (file_exists($filePath)) {
        unlink($filePath);
        $deleted = true;
    }
}

// ลบจาก Session
unset($_SESSION['user_avatar']);

if ($deleted) {
    echo json_encode(['success' => true, 'message' => 'ลบรูปโปรไฟล์สำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบรูปโปรไฟล์ที่จะลบ']);
}
?>