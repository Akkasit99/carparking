<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}

// เรียกใช้ handler functions
require_once 'includes/add_camera_handler.php';

// เริ่มต้น CSRF token
initCSRFToken();

// ตรวจสอบข้อความจาก URL parameters
checkMessages();

// จัดการการส่งฟอร์ม
handleAddCamera();
handleDeleteCamera();
// ไม่เรียกใช้ handleAddTopviewCamera() เพราะจะจัดการ topview แยกต่างหาก

$success = $error = '';
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

// จัดการฟอร์ม Topview แยกต่างหาก (ตามที่ user ระบุ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_topview_camera'])) {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // สร้าง token ใหม่
        header('Location: add_camera.php?error=' . urlencode('การส่งข้อมูลไม่ถูกต้อง กรุณาลองใหม่'));
        exit();
    }

    $slot_id = intval(trim($_POST['topview_slot_id'] ?? 0));
    $lane = intval(trim($_POST['topview_lane'] ?? 0));
    $camera_id = trim($_POST['topview_camera_id'] ?? '');
    $location_id = intval(trim($_POST['topview_location_id'] ?? 0));

    if ($slot_id > 0 && $lane > 0 && $camera_id && $location_id > 0) {
        $success_count = 0;
        $error_count = 0;

        // ดึงค่า slot_id สูงสุดจากฐานข้อมูล
        $max_slot_id = 0;
        $url_max = $supabase_url . "/rest/v1/parking_slots_status?select=slot_id&order=slot_id.desc&limit=1";
        $ch_max = curl_init($url_max);
        curl_setopt($ch_max, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_max, CURLOPT_HTTPHEADER, [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key"
        ]);
        $response_max = curl_exec($ch_max);
        curl_close($ch_max);
        
        $max_data = json_decode($response_max, true);
        if (!empty($max_data) && isset($max_data[0]['slot_id'])) {
            $max_slot_id = intval($max_data[0]['slot_id']);
        }
        
        // เริ่มบันทึกข้อมูลจาก slot_id ถัดไป
        for ($i = 1; $i <= $slot_id; $i++) {
            $current_slot_id = $max_slot_id + $i;
            
            $url = $supabase_url . "/rest/v1/parking_slots_status";
            $data = [
                "slot_id" => $current_slot_id,
                "lane" => $lane,
                "camera_id" => $camera_id,
                "location_id" => $location_id,
                "status" => "F"
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
            
            if ($http_code === 201) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if ($success_count > 0) {
            $message = "เพิ่มกล้อง Topview สำเร็จ $success_count รายการ";
            if ($error_count > 0) {
                $message .= " (ล้มเหลว $error_count รายการ)";
            }
            header('Location: add_camera.php?success=' . urlencode($message));
            exit();
        } else {
            header('Location: add_camera.php?error=' . urlencode('เกิดข้อผิดพลาดในการเพิ่มกล้อง Topview'));
            exit();
        }
    } else {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: add_camera.php?error=' . urlencode('กรุณากรอกข้อมูลให้ครบถ้วน'));
        exit();
    }
}

// ดึงข้อมูลสำหรับแสดงผล
$users = getUsers();
$cameras = getCameras();
$locations = getLocations();
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
                            <i class="fas fa-video"></i>
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
                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
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
            <div id="errorMessage" class="error-message" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%) !important; color: white !important; padding: 30px 50px !important; border-radius: 20px !important; font-size: 24px !important; font-weight: 700 !important; text-align: center !important; z-index: 9999 !important; box-shadow: 0 20px 60px rgba(220, 53, 69, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3) !important; border: 3px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 500px !important; animation: errorShake 0.8s ease-out !important;">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
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

                <!-- ฟอร์มเพิ่มกล้องปกติ -->
                <div class="form-container">
                    <h2><i class="fas fa-video"></i> เพิ่มกล้องปกติ</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="camera_id">ID กล้อง:</label>
                            <input type="text" id="camera_id" name="camera_id" placeholder="เช่น CAM001" required>
                        </div>

                        <div class="form-group">
                            <label for="camera_name">ชื่อกล้อง:</label>
                            <input type="text" id="camera_name" name="camera_name" placeholder="เช่น กล้องทางเข้า" required>
                        </div>

                        <div class="form-group">
                            <label for="location">ตำแหน่ง:</label>
                            <input type="text" id="location" name="location" placeholder="เช่น ทางเข้าหลัก" required>
                        </div>

                        <div class="form-group">
                            <label for="created_by">เพิ่มกล้องในโครงการ:</label>
                            <select id="created_by" name="created_by" required>
                                <option value="">เลือกผู้สร้าง</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo htmlspecialchars($user['username']); ?>">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-submit">บันทึกกล้อง</button>
                    </form>
                </div>

                <!-- ฟอร์มเพิ่มกล้อง Topview -->
                <div class="form-container">
                    <h2><i class="fas fa-eye"></i> เพิ่มกล้อง Topview</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="add_topview_camera" value="1">
                        
                        <div class="form-group">
                            <label for="topview_slot_id">จำนวนช่องจอด:</label>
                            <input type="number" id="topview_slot_id" name="topview_slot_id" min="1" placeholder="เช่น 10" required>
                        </div>

                        <div class="form-group">
                            <label for="topview_lane">เลน:</label>
                            <input type="number" id="topview_lane" name="topview_lane" min="1" placeholder="เช่น 1" required>
                        </div>

                        <div class="form-group">
                            <label for="topview_camera_id">ID กล้อง:</label>
                            <select id="topview_camera_id" name="topview_camera_id" required>
                                <option value="">เลือกกล้อง</option>
                                <?php foreach ($cameras as $camera): ?>
                                    <option value="<?php echo htmlspecialchars($camera['camera_id']); ?>">
                                        <?php echo htmlspecialchars($camera['camera_id'] . ' - ' . $camera['camera_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="topview_location_id">สถานที่:</label>
                            <select id="topview_location_id" name="topview_location_id" required>
                                <option value="">เลือกสถานที่</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['id']; ?>">
                                        <?php echo htmlspecialchars($location['location_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-submit">บันทึกกล้อง Topview</button>
                    </form>
                </div>

                <!-- ฟอร์มลบกล้อง -->
                <div class="form-container">
                    <h2><i class="fas fa-trash-alt"></i> ลบกล้อง</h2>
                    <form method="POST" onsubmit="return confirm('ยืนยันการลบกล้องนี้?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="delete_camera" value="1">
                        
                        <div class="form-group">
                            <label for="delete_id">เลือกกล้องที่ต้องการลบ:</label>
                            <select id="delete_id" name="delete_id" required>
                                <option value="">เลือกกล้อง</option>
                                <?php foreach ($cameras as $camera): ?>
                                    <option value="<?php echo htmlspecialchars($camera['camera_id']); ?>">
                                        <?php echo htmlspecialchars($camera['camera_id'] . ' - ' . $camera['camera_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-delete">ลบกล้อง</button>
                    </form>
                </div>

        <!-- เพิ่ม div แสดงข้อมูลกล้องทั้งหมด -->
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
                    <h3><i class="fas fa-video"></i> รายการกล้องทั้งหมด</h3>
                    <span class="table-count">จำนวน: <?php echo count($cameras); ?> กล้อง</span>
                </div>
                
                <?php if (count($cameras) > 0): ?>
                <div class="table-wrapper">
                    <table class="camera-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-video"></i> ID กล้อง</th>
                                <th><i class="fas fa-tag"></i> ชื่อกล้อง</th>
                                <th><i class="fas fa-map-marker-alt"></i> ตำแหน่ง</th>
                                <th><i class="fas fa-user"></i> ผู้สร้าง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cameras as $index => $camera): ?>
                            <tr class="<?php echo ($index % 2 == 0) ? 'even' : 'odd'; ?>">
                                <td class="camera-id">
                                    <div class="name-info">
                                        <i class="fas fa-video camera-icon"></i>
                                        <span><?php echo htmlspecialchars($camera['camera_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="camera-name">
                                    <div class="name-info">
                                        <i class="fas fa-tag tag-icon"></i>
                                        <span><?php echo htmlspecialchars($camera['camera_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="camera-position">
                                    <div class="name-info">
                                        <i class="fas fa-map-marker-alt location-icon"></i>
                                        <span><?php echo htmlspecialchars($camera['position'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                                <td class="camera-creator">
                                    <div class="name-info">
                                        <i class="fas fa-user user-icon"></i>
                                        <span><?php echo htmlspecialchars($camera['created_by'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data-message">
                    <i class="fas fa-video-slash"></i>
                    <h4>ไม่พบข้อมูลกล้อง</h4>
                    <p>ยังไม่มีกล้องในระบบ กรุณาเพิ่มกล้องใหม่</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script>
// ฟังก์ชันแสดง/ซ่อนตาราง
function toggleCameraTable() {
    const tableContainer = document.getElementById('cameraTableContainer');
    const toggleBtn = document.getElementById('toggleCameraTable');
    
    if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
        tableContainer.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> ซ่อนตารางกล้อง';
        toggleBtn.classList.add('active');
        
        // เลื่อนไปยังตาราง
        setTimeout(() => {
            tableContainer.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 100);
    } else {
        tableContainer.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i> แสดงตารางกล้อง';
        toggleBtn.classList.remove('active');
    }
}

// เพิ่ม event listener สำหรับปุ่ม
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleCameraTable');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleCameraTable);
    }
});

        // Reset form after successful submission
        function resetForm() {
            document.querySelectorAll('form').forEach(form => {
                form.reset();
            });
        }

        // Auto-refresh after successful action
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') || urlParams.get('error')) {
                setTimeout(() => {
                    window.location.href = window.location.pathname;
                }, 2000);
            }
        });
    </script>

    <style>
        h2 {
            color: rgba(255, 255, 255, 0.95) !important;
            font-size: 1.5rem !important;
            font-weight: 600 !important;
            margin-bottom: 25px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
        }

        h2 i {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.3rem !important;
        }

        h2:hover {
            color: rgba(255, 255, 255, 1) !important;
            text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4) !important;
            transform: translateY(-1px) !important;
            transition: all 0.3s ease !important;
        }

        /* Responsive สำหรับ h2 */
        @media (max-width: 768px) {
            h2 {
                font-size: 1.3rem !important;
                margin-bottom: 20px !important;
                gap: 10px !important;
            }
            
            h2 i {
                font-size: 1.2rem !important;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 1.2rem !important;
                margin-bottom: 15px !important;
                gap: 8px !important;
            }
            
            h2 i {
                font-size: 1.1rem !important;
            }
        }
    </style>
</body>
</html>
           