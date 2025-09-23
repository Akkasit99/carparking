<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';
require_once 'includes/exit_data_handler.php';

// รับค่าวันที่/เวลา จากฟอร์ม
date_default_timezone_set('Asia/Bangkok');
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_time = isset($_GET['time']) ? $_GET['time'] : '00:00';

// ดึงข้อมูลจาก handler
$result = getExitData($selected_date, $selected_time, $supabase_url, $supabase_key);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลรถทางออก - Car Parking System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Page-specific styles will be moved to main CSS file */
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
                            <i class="fas fa-car-side"></i>
                        </div>
                        <div class="brand-text">
                            <h1>ข้อมูลรถทางออก</h1>
                            <span>Exit Data Management</span>
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
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label for="date"><i class="far fa-calendar-alt"></i> เลือกวันที่:</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="time"><i class="far fa-clock"></i> เลือกเวลา:</label>
                    <input type="time" id="time" name="time" value="<?php echo htmlspecialchars($selected_time); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn-submit"><i class="fas fa-search"></i> ค้นหา</button>
                    <button type="button" class="btn-secondary" onclick="resetFilter()"><i class="fas fa-undo"></i> ล้างฟิลเตอร์</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <tr>
                    <th>ID</th>
                    <th>รูปภาพ</th>
                    <th>วันที่</th>
                    <th>กล้อง</th>
                </tr>
                <?php
                  // แสดงตารางข้อมูลโดยใช้ handler
                  $imagesData = renderExitTable($result, $selected_date, $selected_time);
                ?>
            </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="imgModal" class="modal" onclick="closeModal()">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImg">
    </div>
    
    <script>
const imagesData = <?php echo json_encode($imagesData); ?>;

function openModal(imgSrc) {
    const modal = document.getElementById('imgModal');
    const modalImg = document.getElementById('modalImg');
    
    if (modal && modalImg) {
        modalImg.src = imgSrc;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    const modal = document.getElementById('imgModal');
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = 'auto';
    }
}

function resetFilter() {
    window.location.href = window.location.pathname;
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

// คลิกพื้นหลังเพื่อปิด
document.addEventListener('click', function(e) {
    const modal = document.getElementById('imgModal');
    if (e.target === modal) {
        closeModal();
    }
});
</script>
</body>
</html>