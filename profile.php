<?php
session_start();
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>alert('ออกจากระบบเรียบร้อยแล้ว'); window.location.href = 'index.php';</script>";
    exit();
}
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

// ดึงข้อมูลผู้ใช้จาก Supabase REST API
$username = $_SESSION['user'];
$url = $supabase_url . "/rest/v1/users?username=eq." . urlencode($username);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $supabase_key",
    "Authorization: Bearer $supabase_key",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);
$userData = null;
$data = json_decode($response, true);
if (is_array($data) && count($data) > 0) {
    $userData = $data[0];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลผู้ดูแลระบบ - Car Parking Management</title>
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
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="brand-text">
                            <h1>ข้อมูลผู้ดูแลระบบ</h1>
                            <span>Administrator Profile</span>
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
        <main class="page-main">
            <div class="page-container">
                <div class="profile-section">
                    <?php if ($userData): ?>
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="profile-title">
                                    <h2><?php echo htmlspecialchars($userData['username']); ?></h2>
                                    <span class="profile-subtitle">ผู้ดูแลระบบ</span>
                                </div>
                            </div>
                            
                            <div class="profile-details">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>ชื่อผู้ใช้</label>
                                        <span><?php echo htmlspecialchars($userData['username']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-parking"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>ชื่อลานจอดรถ</label>
                                        <span><?php echo htmlspecialchars($userData['parking_name']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-user-tag"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>ตำแหน่ง</label>
                                        <span><?php echo htmlspecialchars($userData['position']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>ไม่พบข้อมูลผู้ใช้</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>