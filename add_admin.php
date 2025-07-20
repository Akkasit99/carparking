<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เพิ่มผู้ดูแลระบบ</title>
  <link rel="stylesheet" href="css/add_admin.css">
</head>
<body>
  <h1>เพิ่มผู้ดูแลระบบ</h1>
  <?php
  session_start();
  if (!isset($_SESSION['user'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href = 'index.php';</script>";
    exit();
  }
  require_once 'db_connect.php';

  $success = $error = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['username']);
    $password = trim($_POST['password']);
    $parking_name = trim($_POST['parking_name']);
    $position = trim($_POST['position']);
    $created_by = trim($_POST['created_by']);
    if ($name && $password && $parking_name && $position && $created_by) {
      $url = $supabase_url . "/rest/v1/users";
      $data = [
        "username" => $name,
        "password" => $password,
        "parking_name" => $parking_name,
        "position" => $position,
        "created_by" => $created_by
      ];
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json",
        "Prefer: return=representation"
      ]);
      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      if ($http_code === 201) {
        $success = 'เพิ่มผู้ดูแลระบบสำเร็จ!';
      } else {
        $error = 'เกิดข้อผิดพลาด: ' . htmlspecialchars($response);
      }
    } else {
      $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
  }
  // ดึงรายชื่อผู้ใช้ (dropdown)
  $url = $supabase_url . "/rest/v1/users?select=id,username";
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $supabase_key",
    "Authorization: Bearer $supabase_key",
    "Content-Type: application/json"
  ]);
  $response = curl_exec($ch);
  curl_close($ch);
  $userOptions = '';
  $userResult = json_decode($response, true);
  if (is_array($userResult) && count($userResult) > 0) {
    foreach($userResult as $u) {
      $userOptions .= '<option value="' . htmlspecialchars($u['username']) . '">' . htmlspecialchars($u['username']) . '</option>';
    }
  }
  ?>
  <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
  <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <label>ชื่อ</label>
    <input type="text" name="username" required>
    <label>รหัสผ่าน</label>
    <input type="password" name="password" required>
    <label>ชื่อลานจอดรถ</label>
    <input type="text" name="parking_name" required>
    <label>ตำแหน่งของบุคคล</label>
    <input type="text" name="position" required>
    <label>คนที่เพิ่มข้อมูล</label>
    <select name="created_by" required>
      <option value="">-- เลือก --</option>
      <?php echo $userOptions; ?>
    </select>
    <button type="submit" class="btn-submit">เพิ่มผู้ดูแลระบบ</button>
  </form>
  <br>
  <button class="btn-back" onclick="window.location.href='dashboard.php'">ย้อนกลับ</button>
</body>
</html>
