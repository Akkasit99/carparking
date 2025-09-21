<?php
require_once 'db_connect.php';

// ฟังก์ชันสำหรับรับค่าวันที่/เวลา จากฟอร์ม
function getSelectedDateTime() {
    $selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $selected_time = isset($_GET['time']) ? $_GET['time'] : '00:00';
    
    return [$selected_date, $selected_time];
}

// ฟังก์ชันสำหรับดึงข้อมูลรูปภาพ (ใช้สำหรับ entrance, exit, parking_lot)
function getImagesData($type = 'entrance') {
    // ข้อมูลรูปภาพตัวอย่าง - ในการใช้งานจริงจะดึงจากฐานข้อมูล
    $imagesData = [
        [
            'id' => 1,
            'timestamp' => '2024-01-15 08:30:00',
            'license_plate' => 'กข-1234',
            'image_url' => 'image/car1.jpg',
            'status' => 'detected'
        ],
        [
            'id' => 2,
            'timestamp' => '2024-01-15 09:15:00',
            'license_plate' => 'กค-5678',
            'image_url' => 'image/car2.jpg',
            'status' => 'detected'
        ],
        [
            'id' => 3,
            'timestamp' => '2024-01-15 10:45:00',
            'license_plate' => 'กง-9012',
            'image_url' => 'image/car3.jpg',
            'status' => 'detected'
        ]
    ];
    
    return $imagesData;
}
?>