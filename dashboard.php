<?php
session_start();
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    // ลบ alert ออก และ redirect ไปหน้า index ทันที
    header('Location: index.php');
    exit();
}
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แผงควบคุม - Safety parking</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-wrapper">
    <div class="dashboard-background">
      
    </div>

    <header class="modern-header">
      <div class="header-container">
        <div class="header-left">
          <div class="header-brand">
            <div class="brand-logo">
              <i class="fas fa-car"></i>
            </div>
            <div class="brand-text">
              <h1>Safety parking</h1>
              <span>ระบบระบลานจอดรถ</span>
            </div>
          </div>
        </div>
        <div class="header-right">
          <nav class="header-nav">
            <a href="profile.php" class="nav-btn profile-btn">
              <i class="fas fa-user-circle"></i>
              <span>โปรไฟล์</span>
            </a>
            <!-- ปุ่ม logout ที่ใช้ Modal สวยงาม -->
            <a href="#" class="nav-btn logout-btn" onclick="showLogoutModal(); return false;">
              <i class="fas fa-sign-out-alt"></i>
              <span>ออกจากระบบ</span>
            </a>
          </nav>
        </div>
      </div>
    </header>

    <main class="dashboard-main">
      <div class="dashboard-container">
        <div class="dashboard-header">
          <h2>แผงควบคุมหลัก</h2>
          <p>เลือกเมนูที่ต้องการจัดการ</p>
        </div>
        
        <div class="dashboard-grid">
          <a href="entrance.php" class="dashboard-card entrance">
            <div class="card-icon">
              <i class="fas fa-arrow-circle-right"></i>
            </div>
            <div class="card-content">
              <h3>ทางเข้า</h3>
              <p>ข้อมูลรถที่เข้าลานจอด</p>
            </div>
            <div class="card-arrow">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>

          <a href="exit.php" class="dashboard-card exit">
            <div class="card-icon">
              <i class="fas fa-arrow-circle-left"></i>
            </div>
            <div class="card-content">
              <h3>ทางออก</h3>
              <p>ข้อมูลรถที่ออกจากลานจอด</p>
            </div>
            <div class="card-arrow">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>

          <a href="parking_lot.php" class="dashboard-card parking">
            <div class="card-icon">
              <i class="fas fa-car"></i>
            </div>
            <div class="card-content">
              <h3>ลานจอด</h3>
              <p>ข้อมูลรถในลานจอดปัจจุบัน</p>
            </div>
            <div class="card-arrow">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>

          <a href="add_admin.php" class="dashboard-card admin">
            <div class="card-icon">
              <i class="fas fa-user-plus"></i>
            </div>
            <div class="card-content">
              <h3>ผู้ดูแลระบบ</h3>
              <p>เพิ่มและจัดการผู้ดูแลระบบ</p>
            </div>
            <div class="card-arrow">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>

          <a href="add_camera.php" class="dashboard-card camera">
            <div class="card-icon">
              <i class="fas fa-camera"></i>
            </div>
            <div class="card-content">
              <h3>กล้อง</h3>
              <p>จัดการกล้องและการตรวจจับ</p>
            </div>
            <div class="card-arrow">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>

          <a href="add_location.php" class="dashboard-card location">
            <div class="card-icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="card-content">
              <h3>สถานที่</h3>
              <p>จัดการตำแหน่งและพื้นที่จอด</p>
            </div>
            <div class="card-arrow">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>
        </div>
      </div>
    </main>
  </div>

  <!-- Logout Modal สวยงาม -->
  <div id="logoutModal" class="logout-modal" style="display: none;">
    <div class="logout-modal-content">
      <div class="logout-content">
        <div class="logout-icon">
          <i class="fas fa-sign-out-alt"></i>
        </div>
        <h3 class="logout-title">ออกจากระบบ</h3>
        <p class="logout-message">
          คุณต้องการออกจากระบบใช่หรือไม่?<br>
          ข้อมูลที่ยังไม่ได้บันทึกอาจสูญหาย
        </p>
        <div class="logout-buttons">
          <button class="logout-btn logout-btn-confirm" onclick="confirmLogout()">
            <i class="fas fa-check"></i> ยืนยัน
          </button>
          <button class="logout-btn logout-btn-cancel" onclick="closeLogoutModal()">
            <i class="fas fa-times"></i> ยกเลิก
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript สำหรับ Modal -->
  <script>
  function showLogoutModal() {
      document.getElementById('logoutModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
  }

  function closeLogoutModal() {
      document.getElementById('logoutModal').style.display = 'none';
      document.body.style.overflow = '';
  }

  function confirmLogout() {
      // ไม่แสดง alert ใดๆ เมื่อออกจากระบบ - redirect ทันที
      window.location.href = 'dashboard.php?logout=1';
  }

  // ปิด modal เมื่อคลิกพื้นหลัง
  document.getElementById('logoutModal').addEventListener('click', function(e) {
      if (e.target === this) {
          closeLogoutModal();
      }
  });

  // ปิด modal เมื่อกด ESC
  document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
          closeLogoutModal();
      }
  });
  </script>

  <style>
  .logout-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(10px);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      animation: fadeIn 0.3s ease;
  }

  .logout-modal-content {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 20px;
      padding: 40px;
      max-width: 450px;
      width: 90%;
      text-align: center;
      box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
      border: 2px solid rgba(255, 255, 255, 0.2);
      animation: slideUp 0.4s ease;
  }

  .logout-icon {
      font-size: 64px;
      color: #fff;
      margin-bottom: 20px;
      animation: bounce 0.6s ease;
  }

  .logout-title {
      font-size: 28px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 15px;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  }

  .logout-message {
      font-size: 16px;
      color: rgba(255, 255, 255, 0.9);
      margin-bottom: 30px;
      line-height: 1.5;
  }

  .logout-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
  }

  .logout-btn {
      padding: 12px 30px;
      border: none;
      border-radius: 50px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
  }

  .logout-btn-confirm {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
      color: white;
      box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
  }

  .logout-btn-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 35px rgba(255, 107, 107, 0.5);
  }

  .logout-btn-cancel {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      border: 2px solid rgba(255, 255, 255, 0.3);
  }

  .logout-btn-cancel:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
  }

  @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
  }

  @keyframes slideUp {
      from {
          opacity: 0;
          transform: translateY(50px) scale(0.9);
      }
      to {
          opacity: 1;
          transform: translateY(0) scale(1);
      }
  }

  @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {
          transform: translateY(0);
      }
      40% {
          transform: translateY(-10px);
      }
      60% {
          transform: translateY(-5px);
      }
  }

  @media (max-width: 768px) {
      .logout-modal-content {
          padding: 30px 20px;
          margin: 20px;
      }
      
      .logout-buttons {
          flex-direction: column;
      }
      
      .logout-btn {
          width: 100%;
      }
  }
  </style>
</body>
</html>