<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ข้อมูลลานจอดรถ</title>
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

  <h1>ข้อมูลลานจอดรถ</h1>

  <form action="login.php" method="post">
    <div>
        <label for="username">ผู้ดูแลระบบ</label>
        <input type="text" id="username" name="username" placeholder="Admin@mail.com">
    </div>
    <div>
       <label for="password">รหัสผ่าน</label>
        <input type="password" id="password" name="password" placeholder="1234"> 
    </div>
    <div>
        <button type="submit">เข้าสู่ระบบ</button>
    </div>
    
  </form>

</body>
</html> 