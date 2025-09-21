<?php
require_once 'db_connect.php';

// ฟังก์ชันสำหรับถอดรหัสรหัสผ่าน
function decryptPassword($encryptedPassword) {
    $key = 'SafetyParkingSystem2024';
    
    // Decode from Base64
    $encrypted = base64_decode($encryptedPassword);
    
    $decrypted = '';
    for ($i = 0; $i < strlen($encrypted); $i++) {
        $charCode = ord($encrypted[$i]) ^ ord($key[$i % strlen($key)]);
        $decrypted .= chr($charCode);
    }
    
    return $decrypted;
}

// ฟังก์ชันสำหรับจัดการการ login
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = $_POST['username'];
        
        // ตรวจสอบว่ามีรหัสผ่านที่เข้ารหัสหรือไม่
        if (isset($_POST['encrypted_password']) && !empty($_POST['encrypted_password'])) {
            $password = decryptPassword($_POST['encrypted_password']);
        } elseif (isset($_POST['password'])) {
            $password = $_POST['password'];
        } else {
            $_SESSION['login_error'] = 'กรุณากรอกรหัสผ่าน';
            header("Location: index.php");
            exit();
        }

        // ตรวจสอบว่าเป็น email หรือ username
        $is_email = filter_var($username, FILTER_VALIDATE_EMAIL);
        
        global $supabase_url, $supabase_key;
        
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
}
?>