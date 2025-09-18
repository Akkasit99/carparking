<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ตรวจสอบว่าเป็น email หรือ username
    $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
    
    if ($is_email) {
        // ถ้าเป็น email ให้ค้นหาด้วย email
        $url = $supabase_url . "/rest/v1/users?email=eq." . urlencode($username) . "&password=eq." . urlencode($password);
    } else {
        // ถ้าไม่ใช่ email ให้ค้นหาด้วย username
        $url = $supabase_url . "/rest/v1/users?username=eq." . urlencode($username) . "&password=eq." . urlencode($password);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($http_code === 200 && is_array($data) && count($data) > 0) {
        // เก็บข้อมูลผู้ใช้ใน session
        $_SESSION['user'] = $data[0]['username']; // ใช้ username จากฐานข้อมูล
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['login_error'] = 'ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง';
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>