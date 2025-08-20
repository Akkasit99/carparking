<?php
session_start();
session_destroy();
session_start();
$_SESSION['logout_success'] = 'ออกจากระบบสำเร็จ';
header("Location: index.php");
exit();
?>
