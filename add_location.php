<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}

require_once 'db_connect.php';

$success = $error = '';

function call_supabase($method, $url, $apiKey, $payload = null, $prefer = 'return=minimal') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $headers = [
        "apikey: {$apiKey}",
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json",
    ];
    if ($prefer) $headers[] = "Prefer: {$prefer}";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$http, $resp];
}

// โหลดข้อมูลกล้อง
list($httpCam, $respCam) = call_supabase(
    'GET',
    $supabase_url . "/rest/v1/camera?select=*",
    $supabase_key,
    null,
    null
);
$cameras = $httpCam === 200 ? json_decode($respCam, true) : [];

// โหลดข้อมูลสถานที่
list($httpLoc, $respLoc) = call_supabase(
    'GET',
    $supabase_url . "/rest/v1/locations?select=id,location_name,camera_id",
    $supabase_key,
    null,
    null
);
$locations = $httpLoc === 200 ? json_decode($respLoc, true) : [];

// เพิ่มสถานที่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
    $location_name = trim($_POST['location_name'] ?? '');
    $camera_id     = trim($_POST['camera_id'] ?? '');

    if ($location_name !== '' && $camera_id !== '') {
        $data = ['location_name' => $location_name, 'camera_id' => $camera_id];
        list($httpAdd) = call_supabase(
            'POST',
            $supabase_url . "/rest/v1/locations",
            $supabase_key,
            $data,
            'return=minimal'
        );
        $success = ($httpAdd === 201 || $httpAdd === 204) ? "เพิ่มสถานที่สำเร็จ!" : "เกิดข้อผิดพลาดในการเพิ่มสถานที่ (HTTP {$httpAdd})";
        
        // เพิ่มการรีเฟรชหลังบันทึกสำเร็จ
        if ($httpAdd === 201 || $httpAdd === 204) {
            echo "<script>
                setTimeout(function() {
                    window.location.href = window.location.pathname;
                }, 1500);
            </script>";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// ลบสถานที่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
    $location_id = trim($_POST['location_id'] ?? '');
    if ($location_id !== '') {
        // ดึงข้อมูลสถานที่ที่จะลบเพื่อหา camera_id และ location_name
        $url_get = $supabase_url . "/rest/v1/locations?id=eq." . urlencode($location_id) . "&select=camera_id,location_name";
        list($httpGet, $respGet) = call_supabase('GET', $url_get, $supabase_key, null, null);
        
        $camera_id = null;
        $location_name = '';
        if ($httpGet === 200) {
            $location_data = json_decode($respGet, true);
            if (!empty($location_data) && isset($location_data[0])) {
                $camera_id = $location_data[0]['camera_id'] ?? null;
                $location_name = $location_data[0]['location_name'] ?? '';
            }
        }

        $deletion_errors = [];
        $deletion_success = [];

        // 1. ลบข้อมูลที่เกี่ยวข้องในตาราง parking_slots_status ทั้งหมดที่มี location_id นี้
        $url_parking = $supabase_url . "/rest/v1/parking_slots_status?location_id=eq." . urlencode($location_id);
        list($httpParking, $respParking) = call_supabase('DELETE', $url_parking, $supabase_key, null, 'return=minimal');
        
        if ($httpParking === 204 || $httpParking === 200) {
            $deletion_success[] = "ลบข้อมูลช่องจอดที่เกี่ยวข้อง";
        } elseif ($httpParking !== 404) { // 404 หมายความว่าไม่มีข้อมูลให้ลบ ซึ่งไม่ใช่ error
            $deletion_errors[] = "ไม่สามารถลบข้อมูลช่องจอด (HTTP {$httpParking})";
        }

        // 2. ลบข้อมูลที่เกี่ยวข้องในตาราง entrance, exit, parking_lot ที่มี camera_id เดียวกัน
        if ($camera_id) {
            // ลบข้อมูลใน entrance
            $url_entrance = $supabase_url . "/rest/v1/entrance?camera_id=eq." . urlencode($camera_id);
            list($httpEntrance, $respEntrance) = call_supabase('DELETE', $url_entrance, $supabase_key, null, 'return=minimal');
            if ($httpEntrance === 204 || $httpEntrance === 200) {
                $deletion_success[] = "ลบข้อมูลทางเข้า";
            } elseif ($httpEntrance !== 404) {
                $deletion_errors[] = "ไม่สามารถลบข้อมูลทางเข้า (HTTP {$httpEntrance})";
            }

            // ลบข้อมูลใน parking_exit
            $url_exit = $supabase_url . "/rest/v1/parking_exit?camera_id=eq." . urlencode($camera_id);
            list($httpExit, $respExit) = call_supabase('DELETE', $url_exit, $supabase_key, null, 'return=minimal');
            if ($httpExit === 204 || $httpExit === 200) {
                $deletion_success[] = "ลบข้อมูลทางออก";
            } elseif ($httpExit !== 404) {
                $deletion_errors[] = "ไม่สามารถลบข้อมูลทางออก (HTTP {$httpExit})";
            }

            // ลบข้อมูลใน parking_lot
            $url_parking_lot = $supabase_url . "/rest/v1/parking_lot?camera_id=eq." . urlencode($camera_id);
            list($httpParkingLot, $respParkingLot) = call_supabase('DELETE', $url_parking_lot, $supabase_key, null, 'return=minimal');
            if ($httpParkingLot === 204 || $httpParkingLot === 200) {
                $deletion_success[] = "ลบข้อมูลลานจอด";
            } elseif ($httpParkingLot !== 404) {
                $deletion_errors[] = "ไม่สามารถลบข้อมูลลานจอด (HTTP {$httpParkingLot})";
            }
        }

        // 2.5. ลบ camera_id reference จาก locations ก่อน (เพื่อแก้ไข circular reference)
        $url_update_location = $supabase_url . "/rest/v1/locations?id=eq." . urlencode($location_id);
        $update_data = ['camera_id' => null];
        $ch = curl_init($url_update_location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($update_data, JSON_UNESCAPED_UNICODE));
        $headers = [
            "apikey: {$supabase_key}",
            "Authorization: Bearer {$supabase_key}",
            "Content-Type: application/json",
            "Prefer: return=minimal"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $respUpdate = curl_exec($ch);
        $httpUpdate = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpUpdate === 204 || $httpUpdate === 200) {
            $deletion_success[] = "ลบการอ้างอิงกล้องจากสถานที่";
        } elseif ($httpUpdate !== 404) {
            $deletion_errors[] = "ไม่สามารถลบการอ้างอิงกล้อง (HTTP {$httpUpdate})";
        }

        // 2.6. ตอนนี้ลบข้อมูลใน camera ได้แล้ว
        if ($camera_id) {
            $url_camera = $supabase_url . "/rest/v1/camera?location_id=eq." . urlencode($location_id);
            list($httpCamera, $respCamera) = call_supabase('DELETE', $url_camera, $supabase_key, null, 'return=minimal');
            if ($httpCamera === 204 || $httpCamera === 200) {
                $deletion_success[] = "ลบข้อมูลกล้อง";
            } elseif ($httpCamera !== 404) {
                $deletion_errors[] = "ไม่สามารถลบข้อมูลกล้อง (HTTP {$httpCamera})";
            }
        }
        
        // 3. ลบข้อมูลสถานที่สุดท้าย
        $url = $supabase_url . "/rest/v1/locations?id=eq." . urlencode($location_id);
        list($httpDel, $respDel) = call_supabase('DELETE', $url, $supabase_key, null, 'return=minimal');
        
        if ($httpDel === 204 || $httpDel === 200) {
            $success_msg = "ลบสถานที่สำเร็จ";
            $success = $success_msg;
            
            // เพิ่มการรีเฟรชหลังลบสำเร็จ
            echo "<script>
                setTimeout(function() {
                    window.location.href = window.location.pathname;
                }, 1500);
            </script>";
        } else {
            // ถ้าลบสถานที่ไม่สำเร็จ แสดงข้อผิดพลาดทั้งหมด
            $error_msg = "ไม่สามารถลบสถานที่ '{$location_name}' ได้ (HTTP {$httpDel})";
            if (!empty($deletion_errors)) {
                $error_msg .= " - " . implode(", ", $deletion_errors);
            }
            // เพิ่มข้อมูล debug เพื่อดูรายละเอียดข้อผิดพลาด
            $error_msg .= " | Response: " . substr($respDel, 0, 200);
            $error = $error_msg;
        }
    } else {
        $error = "กรุณาเลือกสถานที่ที่จะลบ";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสถานที่ - Car Parking Management</title>
    <link rel="stylesheet" href="css/add_location.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="brand-text">
                            <h1>จัดการสถานที่</h1>
                            <span>Location Management</span>
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
            <h2><i class="fas fa-plus-circle"></i> เพิ่มสถานที่</h2>
            <form method="post">
                <input type="hidden" name="add_location" value="1">
                <div class="form-group">
                    <label for="location_name">ชื่อสถานที่:</label>
                    <input type="text" id="location_name" name="location_name" placeholder="เช่น โรงพยาบาล" required>
                </div>
                <div class="form-group">
                    <label for="camera_id">กล้อง:</label>
                    <select id="camera_id" name="camera_id" required>
                        <option value="">เลือกกล้อง</option>
                        <!-- รูปแบบที่เรียบง่าย -->
                        <?php foreach ($cameras as $camera): ?>
                            <?php
                                $cam_id = htmlspecialchars(trim($camera['camera_id'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $cam_name = htmlspecialchars($camera['camera_name'] ?? 'ไม่มีชื่อ', ENT_QUOTES, 'UTF-8');
                            ?>
                            <option value="<?php echo $cam_id; ?>">
                                <?php echo "({$cam_id}) {$cam_name}"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">บันทึกสถานที่</button>
            </form>
        </div>

        <div class="form-container">
            <h2><i class="fas fa-trash-alt"></i> ลบสถานที่</h2>
            <form method="post" onsubmit="return confirm('ยืนยันการลบสถานที่นี้?');">
                <input type="hidden" name="delete_location" value="1">
                <div class="form-group">
                    <label for="location_id">เลือกสถานที่ (ชื่อสถานที่ - กล้อง):</label>
                    <select id="location_id" name="location_id" required>
                        <option value="">เลือกสถานที่</option>
                        <!-- แก้ไขส่วน dropdown ลบสถานที่ทั้งหมด (บรรทัดที่ 321-329) -->
                        <?php foreach ($locations as $loc): ?>
                            <?php
                                $id   = htmlspecialchars($loc['id'] ?? '', ENT_QUOTES, 'UTF-8');
                                $name = htmlspecialchars($loc['location_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                $cam  = htmlspecialchars($loc['camera_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                
                                // หาชื่อกล้องจาก camera_id
                                $camera_name = 'ไม่พบข้อมูลกล้อง';
                                foreach ($cameras as $camera) {
                                    if ($camera['camera_id'] === $cam) {
                                        $camera_name = $camera['camera_name'] ?? 'ไม่มีชื่อ';
                                        break;
                                    }
                                }
                            ?>
                            <option value="<?php echo $id; ?>">
                                <?php echo "{$name} - ({$cam}) {$camera_name}"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-delete">ลบสถานที่</button>
            </form>
        </div>

        <!-- เพิ่ม div แสดงข้อมูลสถานที่ทั้งหมด -->
        <div class="form-container">
            <h2><i class="fas fa-table"></i> แสดงข้อมูลสถานที่ทั้งหมด</h2>
            <button type="button" id="toggleLocationTable" class="btn-view" aria-label="แสดงหรือซ่อนตารางข้อมูลสถานที่">
                <i class="fas fa-eye" aria-hidden="true"></i> แสดงตารางสถานที่
            </button>
        </div>

        <!-- คอนเทนเนอร์ตารางแยกต่างหาก -->
        <div class="table-main-container" id="locationTableContainer" style="display: none;">
            <div class="table-container-wide">
                <div class="table-header">
                    <h3><i class="fas fa-map-marker-alt"></i> รายการสถานที่ทั้งหมด</h3>
                    <span class="table-count">จำนวน: <?php echo count($locations); ?> สถานที่</span>
                </div>
                
                <?php if (count($locations) > 0): ?>
                <div class="table-wrapper">
                    <table class="camera-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-map-marker-alt"></i> ชื่อสถานที่</th>
                                <th><i class="fas fa-video"></i> รหัสกล้อง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locations as $index => $location): ?>
                            <tr class="<?php echo ($index % 2 == 0) ? 'even' : 'odd'; ?>">
                                <td class="location-name">
                                    <div class="name-info">
                                        <i class="fas fa-map-marker-alt location-icon"></i>
                                        <span><?php echo htmlspecialchars($location['location_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="camera-id">
                                    <div class="name-info">
                                        <i class="fas fa-video camera-icon"></i>
                                        <span><?php echo htmlspecialchars($location['camera_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data-message">
                    <i class="fas fa-map-marker-slash"></i>
                    <h4>ไม่พบข้อมูลสถานที่</h4>
                    <p>ยังไม่มีสถานที่ในระบบ กรุณาเพิ่มสถานที่ใหม่</p>
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
function toggleLocationTable() {
    const tableContainer = document.getElementById('locationTableContainer');
    const toggleBtn = document.getElementById('toggleLocationTable');
    
    if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
        tableContainer.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> ซ่อนตารางสถานที่';
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
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> แสดงตารางสถานที่';
        toggleBtn.classList.remove('active');
    }
}

// เพิ่ม Event Listener เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม Event Listener สำหรับปุ่ม toggle
    const toggleBtn = document.getElementById('toggleLocationTable');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleLocationTable);
    }
});
</script>
</html><style>
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

<!-- เพิ่ม JavaScript สำหรับรีเซ็ตฟอร์มหลังรีเฟรช -->
<script>
// ฟังก์ชันรีเซ็ตฟอร์มทั้งหมด
function resetAllForms() {
    // รีเซ็ตฟอร์มเพิ่มสถานที่
    const addForm = document.getElementById('addLocationForm');
    if (addForm) {
        addForm.reset();
    }
    
    // รีเซ็ตฟอร์มลบสถานที่
    const deleteForm = document.getElementById('deleteLocationForm');
    if (deleteForm) {
        deleteForm.reset();
    }
    
    // ซ่อนตารางสถานที่
    const tableContainer = document.getElementById('locationTableContainer');
    const toggleBtn = document.getElementById('toggleLocationTable');
    if (tableContainer && toggleBtn) {
        tableContainer.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> แสดงตารางสถานที่';
        toggleBtn.classList.remove('active');
    }
    
    // ล้าง URL parameters
    if (window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// ฟังก์ชันแสดง/ซ่อนตาราง
function toggleLocationTable() {
    const tableContainer = document.getElementById('locationTableContainer');
    const toggleBtn = document.getElementById('toggleLocationTable');
    
    if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
        tableContainer.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> ซ่อนตารางสถานที่';
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
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> แสดงตารางสถานที่';
        toggleBtn.classList.remove('active');
    }
}

// เพิ่ม Event Listener เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // รีเซ็ตฟอร์มทั้งหมดเมื่อโหลดหน้า
    resetAllForms();
    
    // เพิ่ม Event Listener สำหรับปุ่ม toggle
    const toggleBtn = document.getElementById('toggleLocationTable');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleLocationTable);
    }
    
    // เพิ่ม Event Listener สำหรับการรีเฟรชหน้า
    window.addEventListener('beforeunload', function() {
        resetAllForms();
    });
    
    // รีเซ็ตฟอร์มเมื่อกดปุ่ม F5 หรือ Ctrl+R
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
            resetAllForms();
        }
    });
});

// รีเซ็ตฟอร์มเมื่อมีการ navigation
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        resetAllForms();
    }
});

// เพิ่มฟังก์ชันสำหรับรีเฟรชหลังดำเนินการสำเร็จ
window.addEventListener('load', function() {
    // ตรวจสอบว่ามีข้อความสำเร็จหรือไม่มี
    const successMessage = document.getElementById('successMessage');
    if (successMessage) {
        // รอให้แสดงข้อความเสร็จแล้วรีเซ็ตฟอร์ม
        setTimeout(function() {
            resetAllForms();
        }, 3500); // รอหลังจากข้อความหายไป
    }
});
</script>
