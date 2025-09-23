<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $username = $_SESSION['user'];
    $uploadDir = 'uploads/avatars/';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $file = $_FILES['avatar'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    // ตรวจสอบว่าเป็นไฟล์รูปภาพหรือไม่
    $imageInfo = getimagesize($fileTmpName);
    
    if ($imageInfo !== false) {
        // เป็นไฟล์รูปภาพ - รองรับทุกสกุล
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // ตรวจสอบขนาดไฟล์ (จำกัดที่ 10MB)
        if ($fileSize <= 10000000) {
            // สร้างชื่อไฟล์ใหม่ (เฉพาะของผู้ใช้คนนี้)
            $newFileName = $username . '_avatar.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // ลบรูปเก่าของผู้ใช้คนนี้ถ้ามี (เฉพาะไฟล์ avatar)
            $oldAvatarFile = $uploadDir . $username . '_avatar.*';
            $oldFiles = glob($oldAvatarFile);
            foreach ($oldFiles as $oldFile) {
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            // อัปโหลดไฟล์ใหม่
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // บันทึกเฉพาะใน Session (ไม่บันทึกลง Database)
                $_SESSION['user_avatar'] = $uploadPath;
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'อัปโหลดรูปโปรไฟล์สำเร็จ!',
                    'avatar_url' => $uploadPath
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ขนาดไฟล์ใหญ่เกินไป (จำกัดที่ 10MB)']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ไฟล์ที่เลือกไม่ใช่รูปภาพ']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด']);
}
?>