<?php
// Include security headers
require_once 'includes/security_headers.php';

session_start();
require_once 'includes/session_check.php';

// Redirect if already logged in
checkAlreadyLoggedIn();

// Get login error message
$login_error = '';
if (isset($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Clear the error message
}

// Get logout success message
$logout_success = '';
if (isset($_SESSION['logout_success'])) {
    $logout_success = $_SESSION['logout_success'];
    unset($_SESSION['logout_success']); // Clear the success message
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบจัดการลานจอดรถ - Safety parking</title>
  <link rel="stylesheet" href="css/index.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-background">
      
    </div>
    
    <div class="login-container">
      <div class="login-header">
        <div class="logo">
          <i class="fas fa-car"></i>
        </div>
        <h1>Safety parking</h1>
        <p>ระบบระบุลานจอดรถ</p>
      </div>
      
      <?php if ($login_error): ?>
        <div id="errorMessage" class="error-message" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important; color: white !important; padding: 30px 50px !important; border-radius: 20px !important; font-size: 24px !important; font-weight: 700 !important; text-align: center !important; z-index: 9999 !important; box-shadow: 0 20px 60px rgba(220, 53, 69, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3) !important; border: 3px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 500px !important; animation: errorShake 0.8s ease-out !important;">
            ✕ <?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?>
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
      
      <?php if ($logout_success): ?>
        <div id="toastContainer" style="position: fixed !important; top: 20px !important; right: 20px !important; z-index: 10000 !important;">
            <div id="logoutToast" class="toast-notification" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: white !important; padding: 20px 25px !important; border-radius: 15px !important; font-size: 16px !important; font-weight: 600 !important; box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4), 0 5px 15px rgba(0, 0, 0, 0.2) !important; border: 2px solid rgba(255, 255, 255, 0.3) !important; min-width: 300px !important; max-width: 400px !important; animation: toastSlideIn 0.5s ease-out !important; backdrop-filter: blur(10px) !important; display: flex !important; align-items: center !important; gap: 12px !important;">
                <div style="background: rgba(255, 255, 255, 0.2); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                    ✓
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 16px; font-weight: 600; margin-bottom: 2px;"><?php echo htmlspecialchars($logout_success, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size: 13px; opacity: 0.9; font-weight: 400;">ขอบคุณที่ใช้บริการ</div>
                </div>
                <button onclick="closeToast()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; opacity: 0.7; padding: 5px; line-height: 1; margin-left: 10px;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'" aria-label="ปิดข้อความแจ้งเตือน" tabindex="0" onkeypress="if(event.key==='Enter') closeToast()">×</button>
            </div>
        </div>
        <script>
            function closeToast() {
                const toast = document.getElementById('logoutToast');
                if (toast) {
                    toast.style.animation = 'toastSlideOut 0.3s ease-in forwards';
                    setTimeout(() => {
                        const container = document.getElementById('toastContainer');
                        if (container) container.remove();
                    }, 300);
                }
            }
            
            setTimeout(function() {
                closeToast();
            }, 5000);
        </script>
      <?php endif; ?>
      
      <form id="loginForm" action="login.php" method="post" class="login-form">
        <div class="form-group">
          <div class="input-wrapper">
            <i class="fas fa-user"></i>
            <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้ หรือ อีเมล" required>
          </div>
        </div>
        
        <div class="form-group">
          <div class="input-wrapper">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required>
          </div>
        </div>
        
        <!-- Hidden field for encrypted password -->
        <input type="hidden" id="encryptedPassword" name="encrypted_password">
        
        <button type="submit" class="btn-login">
          <span>เข้าสู่ระบบ</span>
          <i class="fas fa-arrow-right"></i>
        </button>
      </form>
      
      <div class="login-footer">
        <p>© 2024 Smart Parking System. All rights reserved.</p>
      </div>
    </div>
  </div>

  <!-- JavaScript for password encryption -->
  <script>
    // Simple Base64 + XOR encryption function
    function encryptPassword(password) {
      const key = 'SafetyParkingSystem2024';
      let encrypted = '';
      
      for (let i = 0; i < password.length; i++) {
        const charCode = password.charCodeAt(i) ^ key.charCodeAt(i % key.length);
        encrypted += String.fromCharCode(charCode);
      }
      
      // Convert to Base64 to make it URL safe
      return btoa(encrypted);
    }
    
    // Handle form submission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const passwordField = document.getElementById('password');
      const encryptedField = document.getElementById('encryptedPassword');
      const originalPassword = passwordField.value;
      
      if (originalPassword) {
        // Encrypt the password
        const encrypted = encryptPassword(originalPassword);
        encryptedField.value = encrypted;
        
        // Clear the original password field
        passwordField.value = '';
        passwordField.name = '';
        
        // Submit the form
        this.submit();
      }
    });
  </script>

</body>
</html>