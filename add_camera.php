<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

// สร้าง CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

    // ฟอร์มเพิ่มกล้อง
    if (isset($_POST['camera_id'], $_POST['camera_name'], $_POST['location'], $_POST['created_by'])) {
        $camera_id   = trim($_POST['camera_id']);
        $camera_name = trim($_POST['camera_name']);
        $location    = trim($_POST['location']);
        $created_by  = trim($_POST['created_by']);

        if ($camera_id && $camera_name && $location && $created_by) {
            $url  = $supabase_url . "/rest/v1/camera";
            $data = [
                "camera_id" => $camera_id, 
                "camera_name" => $camera_name, 
                "location" => $location,
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
                header('Location: add_camera.php?success=' . urlencode('เพิ่มกล้องสำเร็จ!'));
                exit();
            } else {
                header('Location: add_camera.php?error=' . urlencode('เกิดข้อผิดพลาด: ' . ($response ?? 'ไม่ทราบสาเหตุ')));
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
            $url = $supabase_url . "/rest/v1/camera?camera_id=eq." . urlencode($delete_id);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ]);
            $response  = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // สร้าง token ใหม่หลังการประมวลผล
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($http_code === 204) {
                header('Location: add_camera.php?success=' . urlencode('ลบกล้องสำเร็จ!'));
                exit();
            } else {
                header('Location: add_camera.php?error=' . urlencode('เกิดข้อผิดพลาดในการลบ: ' . ($response ?? 'ไม่ทราบสาเหตุ')));
                exit();
            }
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: add_camera.php?error=' . urlencode('กรุณาเลือก ID กล้องที่ต้องการลบ'));
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
    <link rel="stylesheet" href="css/style.css">
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
            <h2><i class="fas fa-plus-circle"></i> เพิ่มกล้อง</h2>
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
                <button type="submit" class="btn-submit">บันทึก</button>
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
            </div>
        </div>
    </div>

        <!-- ปุ่มแสดงตารางกล้อง -->
        <div class="form-container">
            <h2><i class="fas fa-table"></i> แสดงข้อมูลกล้องทั้งหมด</h2>
            <button type="button" id="toggleCameraTable" class="btn-view">
                <i class="fas fa-eye"></i> แสดงตารางกล้อง
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
                                        <span><?php echo htmlspecialchars($camera['location'], ENT_QUOTES, 'UTF-8'); ?></span>
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