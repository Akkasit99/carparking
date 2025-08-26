<?php
session_start();

// ตรวจสอบว่ามี session อยู่หรือไม่
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'ไม่พบ session ที่ต้องการออกจากระบบ'
    ]);
    exit();
}

// เก็บข้อมูลผู้ใช้ก่อนลบ session
$username = $_SESSION['user'];

// ล้างข้อมูล Session ทั้งหมด
session_unset();
session_destroy();

// ลบ Cookie ถ้ามี
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// ลบ Cookie อื่นๆ ที่เกี่ยวข้อง (ถ้ามี)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time()-3600, '/');
}

// ส่งผลลัพธ์กลับ
echo json_encode([
    'success' => true,
    'message' => 'ออกจากระบบเรียบร้อยแล้ว',
    'username' => $username,
    'redirect_url' => 'index.php'
]);
?>
<?php
session_start();

// ตรวจสอบว่ามี session อยู่หรือไม่
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'ไม่พบ session ที่ต้องการออกจากระบบ'
    ]);
    exit();
}

// เก็บข้อมูลผู้ใช้ก่อนลบ session
$username = $_SESSION['user'];

// ล้างข้อมูล Session ทั้งหมด
session_unset();
session_destroy();

// ลบ Cookie ถ้ามี
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// ลบ Cookie อื่นๆ ที่เกี่ยวข้อง (ถ้ามี)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time()-3600, '/');
}

// ส่งผลลัพธ์กลับ
echo json_encode([
    'success' => true,
    'message' => 'ออกจากระบบเรียบร้อยแล้ว',
    'username' => $username,
    'redirect_url' => 'index.php'
]);
?>