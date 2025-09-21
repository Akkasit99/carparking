<?php
require_once 'db_connect.php';

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้
function getUserData($username) {
    global $supabase_url, $supabase_key;
    
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
    
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        return $data[0];
    }
    return null;
}

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้จาก session
function fetchUserData() {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    return getUserData($_SESSION['user']);
}

// ฟังก์ชันสำหรับตรวจสอบรูป avatar
function getAvatarPath($username) {
    $avatarPath = '';
    $avatarDir = 'uploads/avatars/';
    $possibleExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];

    // ค้นหาไฟล์ avatar ของผู้ใช้คนนี้
    foreach ($possibleExtensions as $ext) {
        $checkPath = $avatarDir . $username . '_avatar.' . $ext;
        if (file_exists($checkPath)) {
            $avatarPath = $checkPath;
            break;
        }
    }

    // อัปเดต Session
    if ($avatarPath) {
        $_SESSION['user_avatar'] = $avatarPath;
    } else {
        unset($_SESSION['user_avatar']);
    }
    
    return $avatarPath;
}

// ฟังก์ชันสำหรับตรวจสอบและอัปเดต avatar
function checkAndUpdateAvatar($userData) {
    global $avatarPath;
    
    if ($userData && isset($userData['username'])) {
        $avatarPath = getAvatarPath($userData['username']);
    } else {
        $avatarPath = '';
    }
    
    return $avatarPath;
}

// ฟังก์ชันสำหรับจัดการการออกจากระบบ
function handleProfileLogout() {
    if (isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        echo "<script>alert('ออกจากระบบเรียบร้อยแล้ว'); window.location.href = 'index.php';</script>";
        exit();
    }
}
?>