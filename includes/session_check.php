<?php
// ตรวจสอบ session และ redirect ถ้าไม่ได้ login
function checkSession() {
    if (!isset($_SESSION['user'])) {
        echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
        exit();
    }
}

// ฟังก์ชันสำหรับตั้งค่าข้อความ
function setMessage($type, $message) {
    $_SESSION['messages'][$type] = $message;
}

// ฟังก์ชันสำหรับดึงข้อความและล้างออกจาก session
function getMessages() {
    $messages = [
        'success' => '',
        'error' => ''
    ];
    
    if (isset($_SESSION['messages']['success'])) {
        $messages['success'] = $_SESSION['messages']['success'];
        unset($_SESSION['messages']['success']);
    }
    
    if (isset($_SESSION['messages']['error'])) {
        $messages['error'] = $_SESSION['messages']['error'];
        unset($_SESSION['messages']['error']);
    }
    
    return $messages;
}

// ฟังก์ชันสำหรับตรวจสอบการ login (ใช้แทน checkSession)
function requireLogin() {
    if (!isset($_SESSION['user'])) {
        echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
        exit();
    }
}

// ตรวจสอบการออกจากระบบ
function handleLogout() {
    if (isset($_GET['logout']) && $_GET['logout'] == '1') {
        // ล้างข้อมูล session
        session_unset();
        session_destroy();
        
        // ล้าง cookies
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        // แสดงข้อความและ redirect
        echo "<script>
            alert('ออกจากระบบเรียบร้อยแล้ว');
            window.location.href = 'index.php';
        </script>";
        exit();
    }
}

// ตรวจสอบ session และ redirect ถ้า login แล้ว
function checkAlreadyLoggedIn() {
    if (isset($_SESSION['user'])) {
        header('Location: dashboard.php');
        exit();
    }
}
?>