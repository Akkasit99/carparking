<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
}
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ข้อมูลรถทางออก</title>
  <link rel="stylesheet" href="css/exit.css">
</head>
<body>
  <h1>ข้อมูลรถทางออก</h1>
  <table>
    <tr>
      <th>ID</th>
      <th>รูปภาพ</th>
      <th>วันที่</th>
      <th>กล้อง</th>
    </tr>
    <?php
    $url = $supabase_url . "/rest/v1/parking_exit?select=id,image,date,camera_id&order=date.desc";
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
    // ปิด modal เมื่อกด ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });
  </script>
</body>
</html>
