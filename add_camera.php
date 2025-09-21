<?php
session_start();
require_once 'includes/session_check.php';
require_once 'includes/add_camera_handler.php';

// Check if user is logged in
requireLogin();

// Initialize CSRF token
initCSRFToken();

// Check for messages
$messages = checkMessages();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['camera_id'], $_POST['camera_name'], $_POST['location'], $_POST['created_by']) && !isset($_POST['add_topview_camera']) && !isset($_POST['delete_camera'])) {
        handleAddCamera();
    } elseif (isset($_POST['delete_camera'])) {
        handleDeleteCamera();
    } elseif (isset($_POST['add_topview_camera'])) {
        handleAddTopviewCamera();
    }
}

// Get data for dropdowns
$users = getUsers();
$cameras = getCameras();
$locations = getLocations();

$success = $error = '';

// ตรวจสอบข้อความจาก URL parameters (หลัง redirect)
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // สร้าง token ใหม่
        header('Location: add_camera.php?error=' . urlencode('การส่งข้อมูลไม่ถูกต้อง กรุณาลองใหม่'));
        exit();
    }

    // ฟอร์มเพิ่มกล้องปกติ (ไม่ใช่ topview)
    if (isset($_POST['camera_id'], $_POST['camera_name'], $_POST['location'], $_POST['created_by']) && !isset($_POST['add_topview_camera'])) {
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

            // สร้าง token ใหม่หลังการประมวลผล
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

    // ฟอร์มลบกล้อง
    if (isset($_POST['delete_camera'])) {
        $delete_id = trim($_POST['delete_id'] ?? '');
        if ($delete_id) {
            $deletion_errors = [];
            $deletion_success = [];
            
            // 1. ลบข้อมูลที่เกี่ยวข้องในตาราง parking_slots_status ก่อน
            $url_parking = $supabase_url . "/rest/v1/parking_slots_status?camera_id=eq." . urlencode($delete_id);
            $ch_parking = curl_init($url_parking);
            curl_setopt($ch_parking, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_parking, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_parking, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $parking_response = curl_exec($ch_parking);
            $parking_http_code = curl_getinfo($ch_parking, CURLINFO_HTTP_CODE);
            curl_close($ch_parking);
            
            if ($parking_http_code === 204 || $parking_http_code === 200) {
                $deletion_success[] = "ลบสำเร็จ";
            } elseif ($parking_http_code !== 404) {
                $deletion_errors[] = "ลบไม่สำเร็จ";
            }

            // 2. ลบข้อมูลใน entrance
            $url_entrance = $supabase_url . "/rest/v1/entrance?camera_id=eq." . urlencode($delete_id);
            $ch_entrance = curl_init($url_entrance);
            curl_setopt($ch_entrance, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_entrance, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_entrance, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $entrance_response = curl_exec($ch_entrance);
            $entrance_http_code = curl_getinfo($ch_entrance, CURLINFO_HTTP_CODE);
            curl_close($ch_entrance);
            
            if ($entrance_http_code === 204 || $entrance_http_code === 200) {
                $deletion_success[] = "ลบสำเร็จ";
            } elseif ($entrance_http_code !== 404) {
                $deletion_errors[] = "ลบไม่สำเร็จ";
            }

            // 3. ลบข้อมูลใน parking_exit
            $url_exit = $supabase_url . "/rest/v1/parking_exit?camera_id=eq." . urlencode($delete_id);
            $ch_exit = curl_init($url_exit);
            curl_setopt($ch_exit, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_exit, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_exit, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $exit_response = curl_exec($ch_exit);
            $exit_http_code = curl_getinfo($ch_exit, CURLINFO_HTTP_CODE);
            curl_close($ch_exit);
            
            if ($exit_http_code === 204 || $exit_http_code === 200) {
                $deletion_success[] = "ลบสำเร็จ";
            } elseif ($exit_http_code !== 404) {
                $deletion_errors[] = "ลบไม่สำเร็จ";
            }

            // 4. ลบข้อมูลใน parking_lot
            $url_parking_lot = $supabase_url . "/rest/v1/parking_lot?camera_id=eq." . urlencode($delete_id);
            $ch_parking_lot = curl_init($url_parking_lot);
            curl_setopt($ch_parking_lot, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_parking_lot, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_parking_lot, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $parking_lot_response = curl_exec($ch_parking_lot);
            $parking_lot_http_code = curl_getinfo($ch_parking_lot, CURLINFO_HTTP_CODE);
            curl_close($ch_parking_lot);
            
            if ($parking_lot_http_code === 204 || $parking_lot_http_code === 200) {
                $deletion_success[] = "ลบสำเร็จ";
            } elseif ($parking_lot_http_code !== 404) {
                $deletion_errors[] = "ลบไม่สำเร็จ";
            }

            // 5. ลบข้อมูลสถานที่ที่ใช้กล้องนี้
            $url_locations = $supabase_url . "/rest/v1/locations?camera_id=eq." . urlencode($delete_id);
            $ch_locations = curl_init($url_locations);
            curl_setopt($ch_locations, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_locations, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch_locations, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $locations_response = curl_exec($ch_locations);
            $locations_http_code = curl_getinfo($ch_locations, CURLINFO_HTTP_CODE);
            curl_close($ch_locations);
            
            if ($locations_http_code === 204 || $locations_http_code === 200) {
                $deletion_success[] = "ลบสำเร็จ";
            } elseif ($locations_http_code !== 404) {
                $deletion_errors[] = "ลบไม่สำเร็จ";
            }

            // 6. ลบข้อมูลกล้องในตาราง camera สุดท้าย
            $url = $supabase_url . "/rest/v1/camera?camera_id=eq." . urlencode($delete_id);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // สร้าง token ใหม่หลังการประมวลผล
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($http_code === 204) {
                $success_msg = 'ลบกล้องสำเร็จ';
                header('Location: add_camera.php?success=' . urlencode($success_msg));
                exit();
            } else {
                header('Location: add_camera.php?error=' . urlencode('ลบกล้องไม่สำเร็จ'));
                exit();
            }
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('กรุณาเลือก ID กล้องที่ต้องการลบ'));
            exit();
        }
    }
    
    // ฟอร์มเพิ่มกล้อง Topview
    if (isset($_POST['add_topview_camera'])) {
        $slot_id = intval(trim($_POST['topview_slot_id'] ?? 0));
        $lane = intval(trim($_POST['topview_lane'] ?? 0));
        $camera_id = trim($_POST['topview_camera_id'] ?? '');
        $location_id = intval(trim($_POST['topview_location_id'] ?? 0));

        if ($slot_id > 0 && $lane > 0 && $camera_id && $location_id > 0) {
            $success_count = 0;
            $error_count = 0;

            // ทำการ insert ข้อมูลตามจำนวน slot_id ที่ระบุ
            // สร้างตัวแปรเก็บค่า slot_id สูงสุดที่มีอยู่แล้ว
            $max_slot_id = 0;
            
            // ดึงค่า slot_id สูงสุดจากฐานข้อมูล
            $url_max = $supabase_url . "/rest/v1/parking_slots_status?select=slot_id&order=slot_id.desc&limit=1";
            $ch_max = curl_init($url_max);
            curl_setopt($ch_max, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch_max, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $response_max = curl_exec($ch_max);
            curl_close($ch_max);
            
            // แปลงข้อมูล JSON เป็น array
            $max_data = json_decode($response_max, true);
            if (!empty($max_data) && isset($max_data[0]['slot_id'])) {
                $max_slot_id = intval($max_data[0]['slot_id']);
            }
            
            // เริ่มบันทึกข้อมูลจาก slot_id ถัดไป
            for ($i = 1; $i <= $slot_id; $i++) {
                $current_slot_id = $max_slot_id + $i;
                
                // บันทึกข้อมูลลงในตาราง parking_slots_status
                $url = $supabase_url . "/rest/v1/parking_slots_status";
                $data = [
                    "slot_id" => $current_slot_id, // ใช้ค่า slot_id ที่ต่อเนื่องจากค่าสูงสุดที่มีอยู่
                    "lane" => $lane,
                    "camera_id" => $camera_id,
                    "location_id" => $location_id,
                    "status" => "F" // เพิ่มค่า status เพื่อไม่ให้เป็น null
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
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                // เก็บข้อมูลข้อผิดพลาดเพื่อตรวจสอบ
                if (!isset($_SESSION['debug_info'])) {
                    $_SESSION['debug_info'] = [];
                }
                $_SESSION['debug_info'][] = [
                    'slot_id' => $i,
                    'http_code' => $http_code,
                    'response' => $response,
                    'curl_error' => $curl_error
                ];
                
                if ($http_code === 201) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }

            // ลบการบันทึกข้อมูลซ้ำซ้อนหลังลูป for ทั้งหมด
            // ถ้าต้องการบันทึกข้อมูลลงในตาราง topview_camera ให้เปลี่ยน URL เป็นตารางที่ถูกต้อง
            $url = $supabase_url . "/rest/v1/parking_slots_status";
            $data = [
                "slot_id" => $i,
                "lane" => $lane,
                "camera_id" => $camera_id,
                "location_id" => $location_id
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
            
            // บันทึกข้อมูลลงในตาราง parking_slots_status
            if ($http_code === 201) {
                $url_status = $supabase_url . "/rest/v1/parking_slots_status";
                $data_status = [
                    "slot_id" => $i,
                    "lane" => $lane,
                    "camera_id" => $camera_id,
                    "location_id" => $location_id,
                    "status" => "F" // เพิ่มค่า status เป็น available
                ];
                
                $ch_status = curl_init($url_status);
                curl_setopt($ch_status, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_status, CURLOPT_POST, true);
                curl_setopt($ch_status, CURLOPT_POSTFIELDS, json_encode($data_status, JSON_UNESCAPED_UNICODE));
                curl_setopt($ch_status, CURLOPT_HTTPHEADER, [
                    "apikey: $supabase_key",
                    "Authorization: Bearer $supabase_key",
                    "Content-Type: application/json",
                    "Prefer: return=representation"
                ]);
                $response_status = curl_exec($ch_status);
                $http_code_status = curl_getinfo($ch_status, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch_status);
                curl_close($ch_status);
                
                // เก็บข้อมูลข้อผิดพลาดเพื่อตรวจสอบ
                if (!isset($_SESSION['debug_info'])) {
                    $_SESSION['debug_info'] = [];
                }
                $_SESSION['debug_info'][] = [
                    'slot_id' => $i,
                    'http_code' => $http_code_status,
                    'response' => $response_status,
                    'curl_error' => $curl_error
                ];
                
                if ($http_code_status === 201) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }

            // สร้าง token ใหม่หลังการประมวลผล
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($success_count > 0) {
                $message = "เพิ่มกล้อง Topview สำเร็จ $success_count รายการ";
                if ($error_count > 0) {
                    $message .= " (ล้มเหลว $error_count รายการ)";
                    // เพิ่มข้อมูลการดีบัก
                    if (isset($_SESSION['debug_info'])) {
                        $message .= " - ข้อมูลข้อผิดพลาด: " . json_encode($_SESSION['debug_info'], JSON_UNESCAPED_UNICODE);
                    }
                }
                header('Location: add_camera.php?success=' . urlencode($message));
                exit();
            } else {
                $error_message = 'เกิดข้อผิดพลาดในการเพิ่มกล้อง Topview';
                // เพิ่มข้อมูลการดีบัก
                if (isset($_SESSION['debug_info'])) {
                    $error_message .= " - ข้อมูลข้อผิดพลาด: " . json_encode($_SESSION['debug_info'], JSON_UNESCAPED_UNICODE);
                    // ล้างข้อมูลหลังจากใช้งาน
                    unset($_SESSION['debug_info']);
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

// ดึงข้อมูลกล้องทั้งหมด
function getAllCameras($supabase_url, $supabase_key) {
    $url = $supabase_url . "/rest/v1/camera?select=*";
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key"
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code === 200) return json_decode($response, true);
    return [];
}
$cameras = getAllCameras($supabase_url, $supabase_key);

// ดึงข้อมูลสถานที่ทั้งหมด
function getAllLocations($supabase_url, $supabase_key) {
    $url = $supabase_url . "/rest/v1/locations?select=id,location_name";
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key"
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code === 200) return json_decode($response, true);
    return [];
}
$locations = getAllLocations($supabase_url, $supabase_key);

// ดึงรายชื่อผู้ใช้จากตาราง users คอลัมน์ username
function getAllUsers($supabase_url, $supabase_key) {
    $url = $supabase_url . "/rest/v1/users?select=username";
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
    if ($http_code === 200) return json_decode($response, true);
    return [];
}
$users = getAllUsers($supabase_url, $supabase_key);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการกล้อง - Car Parking Management</title>
    <link rel="stylesheet" href="css/add_camera.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- เพิ่ม CSS สำหรับปุ่ม btn-view -->
    <style>
    .btn-view {
        display: inline-block !important;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        color: #fff !important;
        padding: 12px 12px !important;
        border: none !important;
        cursor: pointer !important;
        text-decoration: none !important;
        font-size: 14px !important;
        border-radius: 6px !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        position: relative !important;
        overflow: hidden !important;
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3) !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
        display: flex !important;
       /* align-items: center !important;*/
       /* justify-content: center !important;*/
        gap: 8px !important;
        width: auto !important;
        max-width: 200px !important;
        min-width: 150px !important;
        font-weight: 600 !important;
        font-family: 'Inter', sans-serif !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        margin: 0 auto !important;
    }
    
    .btn-view::before {
        content: '' !important;
        position: absolute !important;
        top: 0 !important;
        left: -100% !important;
        width: 100% !important;
        height: 100% !important;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent) !important;
        transition: left 0.5s !important;
    }
    
    .btn-view:hover {
        background: linear-gradient(135deg, #218838 0%, #1e7e34 100%) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4) !important;
    }
    
    .btn-view:hover::before {
        left: 100% !important;
    }
    
    .btn-view:active {
        transform: translateY(0) !important;
        box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2) !important;
    }
    
    .btn-view.active {
        background: linear-gradient(135deg, rgb(244, 44, 44) 0%, rgb(255, 0, 0) 100%) !important;
        box-shadow: 0 3px 10px rgba(244, 44, 44, 0.4) !important;
    }
    
    .btn-view.active:hover {
        background: linear-gradient(135deg, rgb(255, 0, 0) 0%, rgb(255, 0, 0) 100%) !important;
        box-shadow: 0 4px 15px rgba(244, 44, 44, 0.5) !important;
    }
    
    .btn-view i {
        font-size: 14px !important;
        transition: all 0.3s ease !important;
    }
    
    .btn-view:hover i {
        transform: scale(1.05) !important;
    }
    
    /* Responsive สำหรับหน้าจอเล็ก */
    @media (max-width: 768px) {
        .btn-view {
            padding: 6px 12px !important;
            font-size: 13px !important;
            min-width: 120px !important;
            max-width: 180px !important;
        }
        
        .btn-view i {
            font-size: 13px !important;
        }
    }
    
    @media (max-width: 480px) {
        .btn-view {
            padding: 5px 10px !important;
            font-size: 12px !important;
            min-width: 100px !important;
            max-width: 150px !important;
            gap: 6px !important;
        }
        
        .btn-view i {
            font-size: 12px !important;
        }
    }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Background Shapes -->
        <div class="page-background">
        </div>

        <!-- Modern Header -->
        <header class="modern-header">
            <div class="header-container">
                <div class="header-left">
                    <div class="header-brand">
                        <div class="brand-logo">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="brand-text">
                            <h1>จัดการกล้อง</h1>
                            <span>Camera Management</span>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <nav class="header-nav">
                        <a href="dashboard.php" class="nav-btn">
                            <i class="fas fa-arrow-left"></i>
                            <span>ย้อนกลับ</span>
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="content-container">
            <div class="container">
        <?php if ($success): ?>
            <div id="successMessage" class="success-message" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: white !important; padding: 30px 50px !important; border-radius: 20px !important; font-size: 24px !important; font-weight: 700 !important; text-align: center !important; z-index: 9999 !important; box-shadow: 0 20px 60px rgba(40, 167, 69, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3) !important; border: 3px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 500px !important; animation: successPulse 0.8s ease-out !important;">
                ✓ <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <script>
                setTimeout(function() {
                    const successMsg = document.getElementById('successMessage');
                    if (successMsg) {
                        successMsg.style.animation = 'fadeOut 0.5s ease-out forwards';
                        setTimeout(() => successMsg.remove(), 500);
                    }
                }, 3000);
            </script>
        <?php endif; ?>
        <?php if ($error): ?>
            <div id="errorMessage" class="error-message" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; color: white !important; padding: 30px 50px !important; border-radius: 20px !important; font-size: 24px !important; font-weight: 700 !important; text-align: center !important; z-index: 9999 !important; box-shadow: 0 20px 60px rgba(220, 53, 69, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3) !important; border: 3px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 500px !important; animation: errorShake 0.8s ease-out !important;">
                ✕ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <script>
                setTimeout(function() {
                    const errorMsg = document.getElementById('errorMessage');
                    if (errorMsg) {
                        errorMsg.style.animation = 'fadeOut 0.5s ease-out forwards';
                        setTimeout(() => errorMsg.remove(), 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>

        <div class="form-container">
            <h2><i class="fas fa-plus-circle"></i> เพิ่มกล้องปกติ</h2>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="camera_id">รหัสกล้อง:</label>
                    <input type="text" id="camera_id" name="camera_id" placeholder="เช่น CAM1" required>
                </div>
                <div class="form-group">
                    <label for="camera_name">ชื่อกล้อง:</label>
                    <input type="text" id="camera_name" name="camera_name" placeholder="เช่น ทางเข้าลานจอด" required>
                </div>
                <div class="form-group">
                    <label for="location">ตำแหน่ง:</label>
                    <input type="text" id="location" name="location" placeholder="เช่น ทางเข้า" required>
                </div>
                <div class="form-group">
                    <label for="created_by">เพิ่มกล้องโดยใคร:</label>
                    <select id="created_by" name="created_by" required>
                        <option value="">-- เลือกผู้ใช้ --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">บันทึกกล้องปกติ</button>
            </form>
        </div>

        <!-- เพิ่มฟอร์มสำหรับกล้อง topview -->
        <div class="form-container">
            <h2><i class="fas fa-video"></i> เพิ่มกล้อง Topview</h2>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="add_topview_camera" value="1">
                
                <div class="form-group">
                    <label for="topview_slot_id">จำนวนช่องจอด:</label>
                    <input type="number" id="topview_slot_id" name="topview_slot_id" placeholder="เช่น 5 (จำนวน 5 ครั้ง บันทึกข้อมูล 5 ครั้ง)" min="1" required>
                </div>
                
                <!-- ลบช่องสถานะออก -->
                
                <div class="form-group">
                    <label for="topview_lane">ลานจอด:</label>
                    <input type="number" id="topview_lane" name="topview_lane" placeholder="เช่น 1" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="topview_camera_id">กล้อง:</label>
                    <select id="topview_camera_id" name="topview_camera_id" required>
                        <option value="">-- เลือกกล้อง --</option>
                        <?php foreach ($cameras as $cam): ?>
                            <option value="<?php echo htmlspecialchars($cam['camera_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars('(' . $cam['camera_id'] . ') ' . $cam['camera_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="topview_location_id">สถานที่:</label>
                    <select id="topview_location_id" name="topview_location_id" required>
                        <option value="">-- เลือกสถานที่ --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars('(' . $loc['id'] . ') ' . $loc['location_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit">บันทึกกล้อง Topview</button>
            </form>
        </div>

        <div class="form-container">
            <h2><i class="fas fa-trash-alt"></i> ลบกล้อง</h2>
            <form method="post" autocomplete="off" onsubmit="return confirm('ยืนยันการลบกล้องนี้?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="delete_id">เลือก ID กล้อง:</label>
                    <select id="delete_id" name="delete_id" required>
                        <option value="">-- เลือกกล้อง --</option>
                        <?php foreach ($cameras as $cam): ?>
                            <option value="<?php echo htmlspecialchars($cam['camera_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($cam['camera_id'] . ' - ' . $cam['camera_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="delete_camera" class="btn-delete">ลบกล้อง</button>
            </form>
        </div>

        <!-- ปุ่มแสดงตารางกล้อง -->
        <div class="form-container">
            <h2><i class="fas fa-table"></i> แสดงข้อมูลกล้องทั้งหมด</h2>
            <button type="button" id="toggleCameraTable" class="btn-view" aria-label="แสดงหรือซ่อนตารางข้อมูลกล้อง">
                <i class="fas fa-eye" aria-hidden="true"></i> แสดงตารางกล้อง
            </button>
        </div>

        <!-- คอนเทนเนอร์ตารางแยกต่างหาก -->
        <div class="table-main-container" id="cameraTableContainer" style="display: none;">
            <div class="table-container-wide">
                <div class="table-header">
                    <h3><i class="fas fa-camera"></i> รายการกล้องทั้งหมด</h3>
                    <span class="table-count">จำนวน: <?php echo count($cameras); ?> กล้อง</span>
                </div>
                
                <?php if (count($cameras) > 0): ?>
                <div class="table-wrapper">
                    <table class="camera-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> รหัสกล้อง</th>
                                <th><i class="fas fa-video"></i> ชื่อกล้อง</th>
                                <th><i class="fas fa-map-marker-alt"></i> ตำแหน่ง</th>
                                <th><i class="fas fa-user"></i> เพิ่มโดย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cameras as $index => $camera): ?>
                            <tr class="<?php echo ($index % 2 == 0) ? 'even' : 'odd'; ?>">
                                <td class="camera-id">
                                    <span class="id-badge"><?php echo htmlspecialchars($camera['camera_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="camera-name">
                                    <div class="name-info">
                                        <i class="fas fa-video camera-icon"></i>
                                        <span><?php echo htmlspecialchars($camera['camera_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="location">
                                    <div class="location-info">
                                        <i class="fas fa-map-marker-alt location-icon"></i>
                                        <span><?php echo htmlspecialchars($camera['position'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="created-by">
                                    <?php if (isset($camera['created_by']) && !empty($camera['created_by'])): ?>
                                        <div class="user-info">
                                            <i class="fas fa-user user-icon"></i>
                                            <span><?php echo htmlspecialchars($camera['created_by'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-data">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data-message">
                    <i class="fas fa-camera-slash"></i>
                    <h4>ไม่พบข้อมูลกล้อง</h4>
                    <p>ยังไม่มีกล้องในระบบ กรุณาเพิ่มกล้องใหม่</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
            </div>
        </div>
    </div>
</body>
<script>
// ฟังก์ชันแสดง/ซ่อนตาราง
function toggleTable() {
    const tableContainer = document.getElementById('cameraTableContainer');
    const toggleBtn = document.getElementById('toggleCameraTable');
    
    if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
        tableContainer.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> ซ่อนตารางกล้อง';
        toggleBtn.classList.add('active');
        
        // เพิ่มแอนิเมชัน
        tableContainer.style.opacity = '0';
        tableContainer.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            tableContainer.style.transition = 'all 0.3s ease';
            tableContainer.style.opacity = '1';
            tableContainer.style.transform = 'translateY(0)';
        }, 10);
    } else {
        tableContainer.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> แสดงตารางกล้อง';
        toggleBtn.classList.remove('active');
    }
}

// เพิ่มฟังก์ชัน resetAllForms ที่หายไป
function resetAllForms() {
    // รีเซ็ตฟอร์มทั้งหมด
    document.querySelectorAll('form').forEach(form => {
        form.reset();
    });
    
    // ล้างค่าใน input fields ทั้งหมด
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.value = '';
        input.placeholder = input.getAttribute('placeholder') || '';
    });
    
    // รีเซ็ต select dropdown
    document.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
    
    // ล้างค่าใน password fields
    document.querySelectorAll('input[type="password"]').forEach(input => {
        input.value = '';
    });
    
    // ล้างค่าใน email fields
    document.querySelectorAll('input[type="email"]').forEach(input => {
        input.value = '';
    });
    
    // ล้างค่าใน textarea
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.value = '';
    });
}

// รีเซ็ตฟอร์มเมื่อโหลดหน้าใหม่
window.addEventListener('load', function() {
    // รีเซ็ตฟอร์มเพิ่มกล้อง
    const addForm = document.querySelector('form[method="post"]:not([onsubmit])');
    if (addForm) {
        addForm.reset();
    }
    
    // รีเซ็ตฟอร์มลบกล้อง
    const deleteForm = document.querySelector('form[onsubmit]');
    if (deleteForm) {
        deleteForm.reset();
    }
    
    // ล้างค่าใน input fields ทั้งหมด
    document.querySelectorAll('input[type="text"]').forEach(input => {
        input.value = '';
    });
    
    // รีเซ็ต select dropdown
    document.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
});

// รีเซ็ตฟอร์มก่อนที่จะออกจากหน้า (เมื่อรีเฟรช)
window.addEventListener('beforeunload', function() {
    // ล้างค่าใน localStorage หากมี
    localStorage.removeItem('camera_form_data');
    
    // รีเซ็ตฟอร์มทั้งหมด
    document.querySelectorAll('form').forEach(form => {
        form.reset();
    });
});

// เพิ่ม Event Listener เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม Event Listener สำหรับปุ่ม toggle
    const toggleBtn = document.getElementById('toggleCameraTable');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleTable);
    }
    
    // รีเซ็ตฟอร์ม
    resetAllForms();
});
</script>
</html>
<!-- เพิ่มใน <style> section ที่มีอยู่แล้ว (หลังบรรทัด 260) -->
<style>
/* CSS เดิมที่มีอยู่... */

/* เพิ่ม CSS สำหรับ h2 สีขาว */
h2 {
    color: white !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    font-weight: 600 !important;
    margin-bottom: 20px !important;
}

/* ไอคอนใน h2 */
h2 i {
    color: white !important;
    margin-right: 10px !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
}

/* เอฟเฟกต์ hover */
h2:hover {
    text-shadow: 0 2px 8px rgba(255, 255, 255, 0.2) !important;
    transition: all 0.3s ease !important;
}

/* Responsive */
@media (max-width: 768px) {
    h2 {
        font-size: 1.5rem !important;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4) !important;
    }
}
</style>

</script>
</html>
           