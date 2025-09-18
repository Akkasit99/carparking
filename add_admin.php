<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มผู้ดูแลระบบ - Car Parking Management</title>
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
    if (!isset($_SESSION['user'])) {
      echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
      exit();
    }
    require_once 'db_connect.php';

    $success = $error = '';
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
            $success = 'เพิ่มผู้ดูแลระบบสำเร็จ!';
          } else {
            $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($response);
          }
        } else {
          $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        }
      }
      
      // ลบผู้ดูแลระบบ
      if (isset($_POST['delete_admin'])) {
        $delete_username = trim($_POST['delete_username'] ?? '');
        
        if ($delete_username) {
          // ตรวจสอบว่าไม่ใช่ผู้ใช้ที่กำลัง login อยู่
          if ($delete_username === $_SESSION['user']) {
            $error = 'ไม่สามารถลบบัญชีของตัวเองได้';
          } else {
            // ลบข้อมูลผู้ดูแลระบบ
            $url = $supabase_url . "/rest/v1/users?username=eq." . urlencode($delete_username);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
              "apikey: $supabase_key",
              "Authorization: Bearer $supabase_key",
              "Content-Type: application/json"
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 204) {
              $success = 'ลบผู้ดูแลระบบสำเร็จ!';
            } else {
              $error = 'เกิดข้อผิดพลาดในการลบ: ' . ($response ?? 'ไม่ทราบสาเหตุ');
            }
          }
        } else {
          $error = 'กรุณาเลือกผู้ดูแลระบบที่ต้องการลบ';
        }
      }
    }
    // ดึงรายชื่อผู้ใช้ (dropdown)
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
    ?>
    <?php if($success): ?>
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
    <?php if($error): ?>
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
    
    <!-- ฟอร์มลบผู้ดูแลระบบ -->
    <form method="post" autocomplete="off" onsubmit="return confirm('ยืนยันการลบผู้ดูแลระบบนี้?');" style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #e9ecef;">
      <h2 style="color: #dc3545; margin-bottom: 20px;"><i class="fas fa-trash-alt"></i> ลบผู้ดูแลระบบ</h2>
      
      <div class="form-group">
        <label for="delete_username">เลือกผู้ดูแลระบบที่ต้องการลบ</label>
        <select id="delete_username" name="delete_username" required>
          <option value="">-- เลือกผู้ดูแลระบบ --</option>
          <?php echo $userOptions; ?>
        </select>
      </div>
      
      <button type="submit" name="delete_admin" class="btn-delete" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
        <i class="fas fa-trash-alt"></i> ลบผู้ดูแลระบบ
      </button>
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