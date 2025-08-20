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
</body>
<script>
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

// เพิ่มฟังก์ชันรีเซ็ตแบบ manual
function resetAllForms() {
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
}

// เรียกใช้ฟังก์ชันรีเซ็ตเมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', resetAllForms);
</script>
</html>