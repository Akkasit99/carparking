<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

// รับค่าวันที่จากฟอร์ม
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_time = isset($_GET['time']) ? $_GET['time'] : '00:00';
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
                // สร้าง URL สำหรับ Supabase query พร้อมฟิลเตอร์วันที่
                $start_datetime = $selected_date . 'T' . $selected_time . ':00';
                $end_datetime = $selected_date . 'T23:59:59';
                
                if ($selected_date == date('Y-m-d') && $selected_time == '00:00') {
                    $url = $supabase_url . "/rest/v1/parking_exit?select=id,image,date,camera_id&order=date.desc";
                } else {
                    $url = $supabase_url . "/rest/v1/parking_exit?select=id,image,date,camera_id&date=gte." . urlencode($start_datetime) . "&date=lte." . urlencode($end_datetime) . "&order=date.desc";
                }
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "apikey: $supabase_key",
                    "Authorization: Bearer $supabase_key",
                    "Content-Type: application/json"
                ]);
                $response = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($response, true);
                
                $imagesData = [];
                
                if (is_array($result) && count($result) > 0) {
                    foreach($result as $row) {
                        $imgUrl = $row['image'];
                        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                            $imgUrl = "https://dgqqonbhhivprdoutkzp.supabase.co/storage/v1/object/public/image/" . $imgUrl;
                        }
                        
                        $imagesData[] = [
                            'id' => $row['id'],
                            'url' => $imgUrl,
                            'date' => $row['date'],
                            'camera' => $row['camera_id']
                        ];
                        
                        echo '<tr>';
                        echo '<td>' . $row['id'] . '</td>';
                        echo '<td class="image-cell">';
                        echo '<img src="' . htmlspecialchars($imgUrl) . '" width="80" style="max-height: 80px; object-fit: cover;" onclick="openModal(\'' . htmlspecialchars($imgUrl) . '\')">';
                        echo '</td>';
                        echo '<td>' . $row['date'] . '</td>';
                        echo '<td>' . $row['camera_id'] . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '<tr class="summary-row">';
                    if ($selected_date == date('Y-m-d') && $selected_time == '00:00') {
                        echo '<td colspan="4"><i class="fas fa-info-circle"></i> แสดงรูปภาพทั้งหมดในฐานข้อมูล ' . count($result) . ' รายการ</td>';
                    } else {
                        echo '<td colspan="4"><i class="fas fa-info-circle"></i> พบข้อมูลในวันที่เลือก ' . count($result) . ' รายการ</td>';
                    }
                    echo '</tr>';
                } else {
                    echo '<tr class="summary-row"><td colspan="4"><i class="fas fa-exclamation-circle"></i> ไม่พบข้อมูล</td></tr>';
                }
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
            modal.style.display = 'block';
            modalImg.src = imgSrc;
        }
        
        function closeModal() {
            document.getElementById('imgModal').style.display = 'none';
        }
        
        function resetFilter() {
            window.location.href = window.location.pathname;
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>