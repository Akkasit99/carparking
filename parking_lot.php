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
  <title>ข้อมูลรถที่เข้าลานจอด</title>
  <link rel="stylesheet" href="css/parking_lot.css">
  <style>
    .filter-section {
      background: #f5f5f5;
      padding: 20px;
      margin: 20px 0;
      border-radius: 8px;
      text-align: center;
    }
    .filter-section input, .filter-section button {
      margin: 5px;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    .filter-section button {
      background: #007bff;
      color: white;
      cursor: pointer;
    }
    .filter-section button:hover {
      background: #0056b3;
    }
    .filter-section button[type="reset"] {
      background: #6c757d;
    }
    .filter-section button[type="reset"]:hover {
      background: #545b62;
    }
  </style>
</head>
<body>
  <h1>ข้อมูลรถที่เข้าลานจอด</h1>
  
  <!-- ฟิลเตอร์วันที่/เวลา -->
  <div class="filter-section">
    <form method="GET" action="">
      <label for="date">เลือกวันที่:</label>
      <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
      
      <label for="time">เลือกเวลา:</label>
      <input type="time" id="time" name="time" value="<?php echo htmlspecialchars($selected_time); ?>">
      
      <button type="submit">ค้นหา</button>
      <button type="reset" onclick="resetFilter()">ล้างฟิลเตอร์</button>
    </form>
  </div>

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
    
    // ถ้าเป็นวันที่ปัจจุบันและเวลา 00:00 (ค่าเริ่มต้น) ให้แสดงรูปภาพทั้งหมด
    if ($selected_date == date('Y-m-d') && $selected_time == '00:00') {
      $url = $supabase_url . "/rest/v1/parking_lot?select=id,image,date,camera_id&order=date.desc";
    } else {
      $url = $supabase_url . "/rest/v1/parking_lot?select=id,image,date,camera_id&date=gte." . urlencode($start_datetime) . "&date=lte." . urlencode($end_datetime) . "&order=date.desc";
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
    
    // สร้าง array สำหรับเก็บข้อมูลรูปภาพสำหรับ JavaScript
    $imagesData = [];
    
    if (is_array($result) && count($result) > 0) {
      foreach($result as $row) {
        $imgUrl = $row['image'];
        // ตรวจสอบว่าเป็น URL เต็มหรือไม่ ถ้าไม่ใช่ให้สร้าง URL เต็ม
        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) {
          $imgUrl = "https://dgqqonbhhivprdoutkzp.supabase.co/storage/v1/object/public/image/" . $imgUrl;
        }
        
        // เก็บข้อมูลรูปภาพสำหรับ JavaScript
        $imagesData[] = [
          'id' => $row['id'],
          'url' => $imgUrl,
          'date' => $row['date'],
          'camera' => $row['camera_id']
        ];
        
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td style="text-align:center;">';
        echo '<img src="' . htmlspecialchars($imgUrl) . '" width="80" style="max-height: 80px; object-fit: cover;"><br>';
        echo '<button class="expand-btn" onclick="openModal(\'' . htmlspecialchars($imgUrl) . '\')">ขยายรูป</button>';
        echo '</td>';
        echo '<td>' . $row['date'] . '</td>';
        echo '<td>' . $row['camera_id'] . '</td>';
        echo '</tr>';
      }
      
      // แสดงข้อความสรุป
      if ($selected_date == date('Y-m-d') && $selected_time == '00:00') {
        echo '<tr><td colspan="4" style="text-align: center; font-weight: bold; color: #28a745;">แสดงรูปภาพทั้งหมดในฐานข้อมูล ' . count($result) . ' รายการ</td></tr>';
      } else {
        echo '<tr><td colspan="4" style="text-align: center; font-weight: bold; color: #007bff;">พบข้อมูลในวันที่เลือก ' . count($result) . ' รายการ</td></tr>';
      }
    } else {
      echo '<tr><td colspan="4" style="text-align: center; color: #dc3545;">ไม่พบข้อมูล</td></tr>';
    }
    ?>
  </table>

  <!-- Modal -->
  <div id="imgModal" class="modal" onclick="closeModal()">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImg">
  </div>
  <button class="btn-back" onclick="window.location.href='dashboard.php'">ย้อนกลับ</button>
  
  <script>
    // ข้อมูลรูปภาพจาก PHP
    const imagesData = <?php echo json_encode($imagesData); ?>;
    
    function openModal(imgSrc) {
      var modal = document.getElementById('imgModal');
      var modalImg = document.getElementById('modalImg');
      modal.style.display = 'block';
      modalImg.src = imgSrc;
    }
    
    function closeModal() {
      document.getElementById('imgModal').style.display = 'none';
    }
    
    function resetFilter() {
      window.location.href = window.location.pathname;
    }
    
    // ปิด modal เมื่อกด ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });
  </script>
</body>
</html>