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

// ตรวจสอบรูป avatar เฉพาะของผู้ใช้คนนี้จากไฟล์ระบบ
$avatarPath = '';
$avatarDir = 'uploads/avatars/';
$possibleExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];

// ค้นหาไฟล์ avatar ของผู้ใช้คนนี้
foreach ($possibleExtensions as $ext) {
    $checkPath = $avatarDir . $username . '_avatar.' . $ext;
    if (file_exists($checkPath)) {
        $avatarPath = $checkPath;
        break;
    }
}

// อัปเดต Session
if ($avatarPath) {
    $_SESSION['user_avatar'] = $avatarPath;
} else {
    unset($_SESSION['user_avatar']);
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
                    <!-- เหลือเฉพาะปุ่มย้อนกลับ -->
                    <a href="dashboard.php" class="nav-btn">
                      <i class="fas fa-arrow-left"></i>
                      <span>ย้อนกลับ</span>
                    </a>
                    <!-- ลบปุ่มออกจากระบบออก -->
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
                                <div class="profile-avatar" onclick="document.getElementById('avatarUpload').click()">
                                    <?php if ($avatarPath): ?>
                                        <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" id="avatarImage">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle" id="defaultAvatar"></i>
                                    <?php endif; ?>
                                    <div class="avatar-overlay">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div class="upload-progress" id="uploadProgress">
                                        <i class="fas fa-spinner fa-spin"></i> กำลังอัปโหลด...
                                    </div>
                                </div>
                                <input type="file" id="avatarUpload" class="avatar-upload-input" accept="image/*">
                                <div class="profile-title">
                                    <h2><?php echo htmlspecialchars($userData['username']); ?></h2>
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
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="detail-content">
                                        <label>อีเมล</label>
                                        <span><?php echo htmlspecialchars($userData['email']); ?></span>
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

    <script>
    // JavaScript สำหรับจัดการอัปโหลดรูป
    document.getElementById('avatarUpload').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
            if (!file.type.startsWith('image/')) {
                showNotification('กรุณาเลือกไฟล์รูปภาพเท่านั้น', 'error');
                return;
            }
            
            // ตรวจสอบขนาดไฟล์ (10MB)
            if (file.size > 10000000) {
                showNotification('ขนาดไฟล์ใหญ่เกินไป (จำกัดที่ 10MB)', 'error');
                return;
            }
            
            // แสดงตัวอย่างรูปก่อนอัปโหลด
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatarContainer = document.querySelector('.profile-avatar');
                const defaultAvatar = document.getElementById('defaultAvatar');
                const avatarImage = document.getElementById('avatarImage');
                
                if (avatarImage) {
                    avatarImage.src = e.target.result;
                } else {
                    if (defaultAvatar) {
                        defaultAvatar.remove();
                    }
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.alt = 'Avatar';
                    newImg.id = 'avatarImage';
                    avatarContainer.insertBefore(newImg, avatarContainer.firstChild);
                }
            };
            reader.readAsDataURL(file);
            
            // แสดง progress
            document.getElementById('uploadProgress').style.display = 'flex';
            
            // สร้าง FormData
            const formData = new FormData();
            formData.append('avatar', file);
            
            // อัปโหลดไฟล์
            fetch('upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('uploadProgress').style.display = 'none';
                
                if (data.success) {
                    showNotification('อัปโหลดรูปโปรไฟล์สำเร็จ!', 'success');
                } else {
                    showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
                }
            })
            .catch(error => {
                document.getElementById('uploadProgress').style.display = 'none';
                showNotification('เกิดข้อผิดพลาดในการอัปโหลด', 'error');
                console.error('Error:', error);
            });
        }
    });

    // ฟังก์ชันแสดงการแจ้งเตือนแบบป๊อปอัปสวยงาม
    function showNotification(message, type = 'success') {
        const existingNotification = document.querySelector('.modern-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = 'modern-notification';
        
        const isSuccess = type === 'success';
        const icon = isSuccess ? '✓' : '✕';
        const bgColor = isSuccess ? 
            'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 
            'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
        const shadowColor = isSuccess ? 
            'rgba(40, 167, 69, 0.4)' : 
            'rgba(220, 53, 69, 0.4)';
        
        notification.style.cssText = `
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            background: ${bgColor} !important;
            color: white !important;
            padding: 30px 50px !important;
            border-radius: 20px !important;
            font-size: 24px !important;
            font-weight: 700 !important;
            text-align: center !important;
            z-index: 9999 !important;
            box-shadow: 0 20px 60px ${shadowColor}, 0 10px 30px rgba(0, 0, 0, 0.3) !important;
            border: 3px solid rgba(255, 255, 255, 0.3) !important;
            min-width: 300px !important;
            max-width: 500px !important;
            animation: ${isSuccess ? 'successPulse' : 'errorShake'} 0.8s ease-out !important;
            backdrop-filter: blur(10px) !important;
            font-family: 'Inter', sans-serif !important;
        `;
        
        notification.innerHTML = `${icon} ${message}`;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 500);
            }
        }, 3000);
    }
    </script>
</body>
</html>

<script>
// JavaScript สำหรับจัดการอัปโหลดรูป - คืนการแจ้งเตือนป๊อปอัปกลับมา
document.getElementById('avatarUpload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
        if (!file.type.startsWith('image/')) {
            showNotification('กรุณาเลือกไฟล์รูปภาพเท่านั้น', 'error');
            return;
        }
        
        // ตรวจสอบขนาดไฟล์ (10MB)
        if (file.size > 10000000) {
            showNotification('ขนาดไฟล์ใหญ่เกินไป (จำกัดที่ 10MB)', 'error');
            return;
        }
        
        // แสดงตัวอย่างรูปก่อนอัปโหลด
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarContainer = document.querySelector('.profile-avatar');
            const defaultAvatar = document.getElementById('defaultAvatar');
            const avatarImage = document.getElementById('avatarImage');
            
            if (avatarImage) {
                avatarImage.src = e.target.result;
            } else {
                if (defaultAvatar) {
                    defaultAvatar.remove();
                }
                const newImg = document.createElement('img');
                newImg.src = e.target.result;
                newImg.alt = 'Avatar';
                newImg.id = 'avatarImage';
                avatarContainer.insertBefore(newImg, avatarContainer.firstChild);
            }
        };
        reader.readAsDataURL(file);
        
        // แสดง progress
        document.getElementById('uploadProgress').style.display = 'flex';
        
        // สร้าง FormData
        const formData = new FormData();
        formData.append('avatar', file);
        
        // อัปโหลดไฟล์
        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('uploadProgress').style.display = 'none';
            
            if (data.success) {
                // แสดงการแจ้งเตือนป๊อปอัปสีเขียวสวยงาม
                showNotification('อัปโหลดรูปโปรไฟล์สำเร็จ!', 'success');
            } else {
                // แสดงการแจ้งเตือนป๊อปอัปสีแดง
                showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
            }
        })
        .catch(error => {
            document.getElementById('uploadProgress').style.display = 'none';
            showNotification('เกิดข้อผิดพลาดในการอัปโหลด', 'error');
            console.error('Error:', error);
        });
    }
});

// ฟังก์ชันแสดงการแจ้งเตือนแบบป๊อปอัปสวยงาม
function showNotification(message, type = 'success') {
    // ลบการแจ้งเตือนเก่าถ้ามี
    const existingNotification = document.querySelector('.modern-notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // สร้างการแจ้งเตือนใหม่
    const notification = document.createElement('div');
    notification.className = 'modern-notification';
    
    const isSuccess = type === 'success';
    const icon = isSuccess ? '✓' : '✕';
    const bgColor = isSuccess ? 
        'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 
        'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
    const shadowColor = isSuccess ? 
        'rgba(40, 167, 69, 0.4)' : 
        'rgba(220, 53, 69, 0.4)';
    
    notification.style.cssText = `
        position: fixed !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        background: ${bgColor} !important;
        color: white !important;
        padding: 30px 50px !important;
        border-radius: 20px !important;
        font-size: 24px !important;
        font-weight: 700 !important;
        text-align: center !important;
        z-index: 9999 !important;
        box-shadow: 0 20px 60px ${shadowColor}, 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        border: 3px solid rgba(255, 255, 255, 0.3) !important;
        min-width: 300px !important;
        max-width: 500px !important;
        animation: ${isSuccess ? 'successPulse' : 'errorShake'} 0.8s ease-out !important;
        backdrop-filter: blur(10px) !important;
        font-family: 'Inter', sans-serif !important;
    `;
    
    notification.innerHTML = `${icon} ${message}`;
    
    // เพิ่มลงใน body
    document.body.appendChild(notification);
    
    // ลบการแจ้งเตือนหลังจาก 3 วินาที
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 500);
        }
    }, 3000);
}
</script>

<!-- เพิ่ม CSS Animations -->
<style>
/* CSS สำหรับ Avatar Upload */
.profile-avatar {
    position: relative;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 50%;
    overflow: hidden;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.profile-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.profile-avatar i {
    font-size: 60px;
    color: white;
}

.avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 50%;
}

.profile-avatar:hover .avatar-overlay {
    opacity: 1;
}

.avatar-overlay i {
    font-size: 24px;
    color: white;
}

.avatar-upload-input {
    display: none;
}

.upload-progress {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 20px;
    display: none;
    z-index: 10;
}

/* CSS Animations สำหรับการแจ้งเตือนป๊อปอัป */
@keyframes successPulse {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.05);
    }
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

@keyframes errorShake {
    0%, 100% {
        transform: translate(-50%, -50%) translateX(0);
        opacity: 0;
    }
    10%, 30%, 50%, 70%, 90% {
        transform: translate(-50%, -50%) translateX(-5px);
    }
    20%, 40%, 60%, 80% {
        transform: translate(-50%, -50%) translateX(5px);
    }
    50% {
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
    to {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.8);
    }
}

.modern-notification {
    font-family: 'Inter', sans-serif !important;
    letter-spacing: 0.5px !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
}
</style>