<?php
require_once 'db_connect.php';

// ฟังก์ชันสำหรับเพิ่มผู้ดูแลระบบ
function handleAddAdmin() {
    global $supabase_url, $supabase_key;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // เพิ่มผู้ดูแลระบบ
        if (isset($_POST['username'], $_POST['password'], $_POST['email'], $_POST['parking_name'], $_POST['position'], $_POST['created_by'])) {
            $name = trim($_POST['username']);
            $password = trim($_POST['password']);
            $email = trim($_POST['email']);
            $parking_name = trim($_POST['parking_name']);
            $position = trim($_POST['position']);
            $created_by = trim($_POST['created_by']);
            
            if ($name && $password && $email && $parking_name && $position && $created_by) {
                $url = $supabase_url . "/rest/v1/users";
                $data = [
                    "username" => $name,
                    "password" => $password,
                    "email" => $email,
                    "parking_name" => $parking_name,
                    "position" => $position,
                    "created_by" => $created_by
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
                    setMessage('success', 'เพิ่มผู้ดูแลระบบสำเร็จ!');
                } else {
                    setMessage('error', 'เกิดข้อผิดพลาด: ' . htmlspecialchars($response));
                }
            } else {
                setMessage('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        }
    }
}

// ฟังก์ชันสำหรับดึงรายชื่อผู้ใช้
function getUserOptions() {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . "/rest/v1/users?select=id,username";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $userOptions = '';
    $userResult = json_decode($response, true);
    if (is_array($userResult) && count($userResult) > 0) {
        foreach($userResult as $u) {
            $userOptions .= '<option value="' . htmlspecialchars($u['username']) . '">' . htmlspecialchars($u['username']) . '</option>';
        }
    }
    return $userOptions;
}
?>