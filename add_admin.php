<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ดูแลระบบ - Car Parking Management</title>
    <link rel="stylesheet" href="css/add_admin.css">
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
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="brand-text">
                            <h1>เพิ่มผู้ดูแลระบบ</h1>
                            <span>Add Administrator</span>
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
            <div class="form-container">
<?php
session_start();
require_once 'includes/session_check.php';
require_once 'includes/add_admin_handler.php';

// Check if user is logged in
requireLogin();

// Handle form submission
handleAddAdmin();

// Get messages
$messages = getMessages();

// Get user options
$userOptions = getUserOptions();
?>
    <?php if($messages['success']): ?>
        <div id="successMessage" class="success-message" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: white !important; padding: 30px 50px !important; border-radius: 20px !important; font-size: 24px !important; font-weight: 700 !important; text-align: center !important; z-index: 9999 !important; box-shadow: 0 20px 60px rgba(40, 167, 69, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3) !important; border: 3px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 500px !important; animation: successPulse 0.8s ease-out !important;">
            ✓ <?php echo htmlspecialchars($messages['success'], ENT_QUOTES, 'UTF-8'); ?>
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
    <?php if($messages['error']): ?>
        <div id="errorMessage" class="error-message" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; color: white !important; padding: 30px 50px !important; border-radius: 20px !important; font-size: 24px !important; font-weight: 700 !important; text-align: center !important; z-index: 9999 !important; box-shadow: 0 20px 60px rgba(220, 53, 69, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3) !important; border: 3px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 500px !important; animation: errorShake 0.8s ease-out !important;">
            ✕ <?php echo htmlspecialchars($messages['error'], ENT_QUOTES, 'UTF-8'); ?>
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
    <form method="post" autocomplete="off">
      <div class="form-group">
        <label for="username">ชื่อ</label>
        <input type="text" id="username" name="username" placeholder="เช่น admin" required>
      </div>
      
      <div class="form-group">
        <label for="password">รหัสผ่าน</label>
        <input type="password" id="password" name="password" placeholder="เช่น 123" required>
      </div>
      
      <div class="form-group">
        <label for="email">อีเมล</label>
        <input type="email" id="email" name="email" placeholder="เช่น admin@example.com" required>
      </div>
      
      <div class="form-group">
        <label for="parking_name">ชื่อลานจอดรถ</label>
        <input type="text" id="parking_name" name="parking_name" placeholder="เช่น โรงเรียน" required>
      </div>
      
      <div class="form-group">
        <label for="position">ตำแหน่งของบุคคล</label>
        <input type="text" id="position" name="position" placeholder="เช่น ผู้ดูแลระบบ" required>
      </div>
      
      <div class="form-group">
        <label for="created_by">คนที่เพิ่มข้อมูล</label>
        <select id="created_by" name="created_by" required>
          <option value="">-- เลือก --</option>
          <?php echo $userOptions; ?>
        </select>
      </div>
      
      <button type="submit" class="btn-submit">เพิ่มผู้ดูแลระบบ</button>
    </form>
            </div>
        </div>
    </div>
<!-- เพิ่มก่อน </body> -->
<script>
// ฟังก์ชันรีเซ็ตฟอร์ม
function resetForm() {
    const form = document.querySelector('form');
    if (form) {
        form.reset();
    }
    
    // ล้าง URL parameters
    if (window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// เพิ่ม Event Listener เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // รีเซ็ตฟอร์มเมื่อโหลดหน้า
    resetForm();
    
    // เพิ่ม Event Listener สำหรับการรีเฟรชหน้า
    window.addEventListener('beforeunload', function() {
        resetForm();
    });
    
    // รีเซ็ตฟอร์มเมื่อกดปุ่ม F5 หรือ Ctrl+R
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F5' || (e.ctrlKey && e.key === 'r')) {
            resetForm();
        }
    });
});

// รีเซ็ตฟอร์มเมื่อมีการ navigation
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        resetForm();
    }
});

// เพิ่มฟังก์ชันสำหรับรีเฟรชหลังดำเนินการสำเร็จ
window.addEventListener('load', function() {
    // ตรวจสอบว่ามีข้อความสำเร็จหรือไม่
    const successMessage = document.getElementById('successMessage');
    if (successMessage) {
        // รอให้แสดงข้อความเสร็จแล้วรีเซ็ตฟอร์ม
        setTimeout(function() {
            resetForm();
        }, 3500); // รอหลังจากข้อความหายไป
    }
});
</script>
</body>
</html>