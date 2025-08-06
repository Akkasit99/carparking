<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';

// รับค่าการกรองวันที่และเวลา
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
$filter_time = isset($_GET['filter_time']) ? $_GET['filter_time'] : '';

// สร้างเงื่อนไขการกรอง
$filter_conditions = [];
if ($filter_date) {
    if ($filter_time) {
        // กรองตามวันที่และเวลาที่แน่นอน
        $datetime = $filter_date . 'T' . $filter_time;
        $filter_conditions[] = "date.eq.$datetime";
    } else {
        // กรองตามวันที่เท่านั้น
        $filter_conditions[] = "date.gte.$filter_date";
        $filter_conditions[] = "date.lt.$filter_date" . "T23:59:59";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ข้อมูลรถที่เข้าลานจอด</title>
  <link rel="stylesheet" href="css/parking_lot.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    .calendar-btn {
      position: absolute;
      top: 32px;
      right: 32px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 50%;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.07);
      cursor: pointer;
      font-size: 22px;
      transition: background 0.2s;
      z-index: 10;
    }
    .calendar-btn:hover {
      background: #f0f0f0;
    }
    .calendar-modal-bg {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.25);
      display: none;
      z-index: 1000;
    }
    .calendar-modal {
      background: #fff;
      border-radius: 10px;
      max-width: 350px;
      margin: 120px auto;
      padding: 28px 24px 18px 24px;
      position: relative;
      box-shadow: 0 4px 24px rgba(0,0,0,0.13);
    }
    .calendar-modal .close {
      position: absolute;
      top: 10px;
      right: 18px;
      font-size: 24px;
      color: #888;
      cursor: pointer;
    }
    .calendar-modal label {
      font-weight: bold;
      margin-bottom: 4px;
      display: block;
    }
    .calendar-modal input {
      width: 100%;
      margin-bottom: 14px;
      padding: 7px 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 15px;
    }
    .calendar-modal .btn-filter {
      width: 100%;
      background: #007bff;
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 10px 0;
      font-size: 16px;
      cursor: pointer;
      margin-top: 8px;
    }
    .calendar-modal .btn-filter:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <h1>ข้อมูลรถที่เข้าลานจอด</h1>
  <div style="position:relative; min-height:40px;">
    <button class="calendar-btn" title="เลือกวันที่/เวลา" onclick="openCalendarModal()">
      <i class="fa fa-calendar"></i>
    </button>
  </div>
  <!-- Modal สำหรับเลือกวันที่/เวลา -->
  <div id="calendarModalBg" class="calendar-modal-bg">
    <div class="calendar-modal">
      <span class="close" onclick="closeCalendarModal()">&times;</span>
      <form id="calendarForm" method="GET" action="">
        <label for="filter_date">วันที่:</label>
        <input type="date" id="filter_date" name="filter_date">
        <label for="filter_time">เวลา:</label>
        <input type="time" id="filter_time" name="filter_time">
        <button type="submit" class="btn-filter"><i class="fa fa-filter"></i> กรองข้อมูล</button>
      </form>
    </div>
  </div>
  <table>
    <tr>
      <th>ID</th>
      <th>รูปภาพ</th>
      <th>วันที่</th>
      <th>กล้อง</th>
    </tr>
    <?php
    // สร้าง URL สำหรับ API
    $url = $supabase_url . "/rest/v1/parking_lot?select=id,image,date,camera_id&order=date.desc";
    
    // เพิ่มเงื่อนไขการกรอง
    if (!empty($filter_conditions)) {
        $url .= "&" . implode("&", $filter_conditions);
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
    
    // แสดงข้อมูลการกรอง
    if ($filter_date || $filter_time) {
        echo '<div style="margin-bottom: 15px; color: #666; font-style: italic;">';
        echo 'แสดงผลข้อมูลที่กรองแล้ว';
        if ($filter_date) echo " วันที่: $filter_date";
        if ($filter_time) echo " เวลา: $filter_time";
        echo '</div>';
    }
    
    if (is_array($result) && count($result) > 0) {
      foreach($result as $row) {
        $imgFile = htmlspecialchars($row['image']);
        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td style="text-align:center;">';
        echo '<img src="image/' . $imgFile . '" width="80"><br>';
        echo '<button class="expand-btn" onclick="openModal(\'image/' . $imgFile . '\')">ขยายรูป</button>';
        echo '</td>';
        echo '<td>' . $row['date'] . '</td>';
        echo '<td>' . $row['camera_id'] . '</td>';
        echo '</tr>';
      }
    } else {
      echo '<tr><td colspan="4">ไม่มีข้อมูล</td></tr>';
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
    function openModal(imgSrc) {
      var modal = document.getElementById('imgModal');
      var modalImg = document.getElementById('modalImg');
      modal.style.display = 'block';
      modalImg.src = imgSrc;
    }
    function closeModal() {
      document.getElementById('imgModal').style.display = 'none';
    }
    // Calendar modal logic
    function openCalendarModal() {
      document.getElementById('calendarModalBg').style.display = 'block';
    }
    function closeCalendarModal() {
      document.getElementById('calendarModalBg').style.display = 'none';
    }
    // ปิด modal เมื่อกด ESC หรือคลิกพื้นหลัง
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeCalendarModal();
    });
    document.getElementById('calendarModalBg').addEventListener('click', function(e) {
      if (e.target === this) closeCalendarModal();
    });
  </script>
</body>
</html>
