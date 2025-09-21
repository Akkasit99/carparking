<?php
require_once 'db_connect.php';

// สร้าง CSRF token
function initCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// ตรวจสอบข้อความจาก URL parameters
function checkMessages() {
    if (isset($_GET['success'])) {
        setMessage('success', urldecode($_GET['success']));
    }
    if (isset($_GET['error'])) {
        setMessage('error', urldecode($_GET['error']));
    }
}

// ฟังก์ชันสำหรับจัดการการเพิ่มกล้อง
function handleAddCamera() {
    global $supabase_url, $supabase_key;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['camera_id'], $_POST['camera_name'], $_POST['location'], $_POST['created_by']) && !isset($_POST['add_topview_camera']) && !isset($_POST['delete_camera'])) {
        // ตรวจสอบ CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('การส่งข้อมูลไม่ถูกต้อง กรุณาลองใหม่'));
            exit();
        }

        $camera_id   = trim($_POST['camera_id']);
        $camera_name = trim($_POST['camera_name']);
        $location    = trim($_POST['location']);
        $created_by  = trim($_POST['created_by']);

        if ($camera_id && $camera_name && $location && $created_by) {
            $url  = $supabase_url . "/rest/v1/camera";
            $data = [
                "camera_id" => $camera_id, 
                "camera_name" => $camera_name, 
                "position" => $location,
                "created_by" => $created_by
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key",
                "Content-Type: application/json",
                "Prefer: return=representation"
            ]);
            $response  = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($http_code === 201) {
                header('Location: add_camera.php?success=' . urlencode('เพิ่มกล้องปกติสำเร็จ!'));
                exit();
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการเพิ่มกล้องปกติ';
                if ($response) {
                    $error_data = json_decode($response, true);
                    if (isset($error_data['message'])) {
                        $error_message .= ': ' . $error_data['message'];
                    } else {
                        $error_message .= ': ' . $response;
                    }
                }
                header('Location: add_camera.php?error=' . urlencode($error_message));
                exit();
            }
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('กรุณากรอกข้อมูลให้ครบถ้วน'));
            exit();
        }
    }
}

// ฟังก์ชันสำหรับลบกล้อง
function handleDeleteCamera() {
    global $supabase_url, $supabase_key;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_camera'])) {
        // ตรวจสอบ CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('การส่งข้อมูลไม่ถูกต้อง กรุณาลองใหม่'));
            exit();
        }
        
        $delete_id = trim($_POST['delete_id'] ?? '');
        if ($delete_id) {
            $deletion_errors = [];
            $deletion_success = [];
            
            // ลบข้อมูลที่เกี่ยวข้องในตาราง parking_slots_status ก่อน
            $url_parking = $supabase_url . "/rest/v1/parking_slots_status?camera_id=eq." . urlencode($delete_id);
            $ch_parking = curl_init($url_parking);
            curl_setopt($ch_parking, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_parking, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_parking, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key",
                "Content-Type: application/json"
            ]);
            $response_parking = curl_exec($ch_parking);
            $http_code_parking = curl_getinfo($ch_parking, CURLINFO_HTTP_CODE);
            curl_close($ch_parking);

            if ($http_code_parking === 204 || $http_code_parking === 200) {
                $deletion_success[] = "ลบข้อมูล parking slots ที่เกี่ยวข้องสำเร็จ";
            } else {
                $deletion_errors[] = "ไม่สามารถลบข้อมูล parking slots ที่เกี่ยวข้องได้: " . $response_parking;
            }

            // ลบข้อมูลในตาราง locations
            $url_locations = $supabase_url . "/rest/v1/locations?camera_id=eq." . urlencode($delete_id);
            $ch_locations = curl_init($url_locations);
            curl_setopt($ch_locations, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_locations, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_locations, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key",
                "Content-Type: application/json"
            ]);
            $response_locations = curl_exec($ch_locations);
            $http_code_locations = curl_getinfo($ch_locations, CURLINFO_HTTP_CODE);
            curl_close($ch_locations);

            if ($http_code_locations === 204 || $http_code_locations === 200) {
                $deletion_success[] = "ลบข้อมูล locations ที่เกี่ยวข้องสำเร็จ";
            } else {
                $deletion_errors[] = "ไม่สามารถลบข้อมูล locations ที่เกี่ยวข้องได้: " . $response_locations;
            }

            // ลบกล้องหลัก
            $url_camera = $supabase_url . "/rest/v1/camera?camera_id=eq." . urlencode($delete_id);
            $ch_camera = curl_init($url_camera);
            curl_setopt($ch_camera, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_camera, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_camera, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key",
                "Content-Type: application/json"
            ]);
            $response_camera = curl_exec($ch_camera);
            $http_code_camera = curl_getinfo($ch_camera, CURLINFO_HTTP_CODE);
            curl_close($ch_camera);

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($http_code_camera === 204 || $http_code_camera === 200) {
                $success_message = "ลบกล้องสำเร็จ";
                header('Location: add_camera.php?success=' . urlencode($success_message));
                exit();
            } else {
                $deletion_errors[] = "ไม่สามารถลบกล้องได้: " . $response_camera;
                $error_message = implode(", ", $deletion_errors);
                header('Location: add_camera.php?error=' . urlencode($error_message));
                exit();
            }
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('กรุณาเลือกกล้องที่ต้องการลบ'));
            exit();
        }
    }
}

// ฟังก์ชันสำหรับเพิ่มกล้อง topview
function handleAddTopviewCamera() {
    global $supabase_url, $supabase_key;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_topview_camera'])) {
        // ตรวจสอบ CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('การส่งข้อมูลไม่ถูกต้อง กรุณาลองใหม่'));
            exit();
        }
        
        $camera_id = trim($_POST['topview_camera_id'] ?? '');
        $location_id = trim($_POST['topview_location_id'] ?? '');

        if ($camera_id && $location_id) {
            $url = $supabase_url . "/rest/v1/parking_slots_status";
            $data = [
                "camera_id" => $camera_id,
                "location_id" => (int)$location_id,
                "slot_number" => 1,
                "status" => "available"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key",
                "Content-Type: application/json",
                "Prefer: return=representation"
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($http_code === 201) {
                header('Location: add_camera.php?success=' . urlencode('เพิ่มกล้อง Topview สำเร็จ!'));
                exit();
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการเพิ่มกล้อง Topview';
                if ($response) {
                     $error_data = json_decode($response, true);
                     if (isset($error_data['message'])) {
                         $error_message .= ': ' . $error_data['message'];
                     } else {
                         $error_message .= ': ' . $response;
                     }
                 }
                 header('Location: add_camera.php?error=' . urlencode($error_message));
                 exit();
             }
         } else {
             $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
             header('Location: add_camera.php?error=' . urlencode('กรุณาเลือกกล้องและสถานที่สำหรับ Topview'));
             exit();
         }
     }
}

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้
function getUsers() {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . "/rest/v1/users?select=username";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?: [];
}

// ฟังก์ชันสำหรับดึงข้อมูลกล้อง
function getCameras() {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . "/rest/v1/camera?select=camera_id,camera_name,position,created_by&order=camera_id.asc";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?: [];
}

// ฟังก์ชันสำหรับดึงข้อมูลสถานที่
function getLocations() {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . "/rest/v1/locations?select=id,location_name,camera_id&order=id.asc";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true) ?: [];
}
?>