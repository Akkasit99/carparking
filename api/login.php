<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // เรียก Supabase REST API เพื่อตรวจสอบ username และ password
    $url = $supabase_url . "/rest/v1/users?username=eq." . urlencode($username) . "&password=eq." . urlencode($password);
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
        $_SESSION['user'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<h2 style='color:red; text-align:center;'>ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</h2>";
        echo "<p style='text-align:center;'><a href='index.php'>กลับไปหน้าล็อกอิน</a></p>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>
