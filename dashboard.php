<?php
session_start();
require_once 'includes/session_check.php';

// Handle logout
handleLogout();

// Check if user is logged in
requireLogin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แผงควบคุม - Safety parking</title>
  <link rel="stylesheet" href="css/dashboard.css">
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
            <!-- ปุ่ม logout แบบง่าย -->
            <a href="dashboard.php?logout=1" 
               onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')" 
               class="nav-btn logout-btn">
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
</body>
</html>