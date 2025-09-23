<?php
require_once 'db_connect.php';

// ฟังก์ชันสำหรับเรียก Supabase API
function call_supabase($method, $url, $apiKey, $payload = null, $prefer = 'return=minimal') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $headers = [
        "apikey: {$apiKey}",
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json",
    ];
    if ($prefer) $headers[] = "Prefer: {$prefer}";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$http, $resp];
}

// ฟังก์ชันสำหรับโหลดข้อมูลกล้อง
function getCameras() {
    global $supabase_url, $supabase_key;
    
    list($httpCam, $respCam) = call_supabase(
        'GET',
        $supabase_url . "/rest/v1/camera?select=*",
        $supabase_key,
        null,
        null
    );
    return $httpCam === 200 ? json_decode($respCam, true) : [];
}

// ฟังก์ชันสำหรับโหลดข้อมูลสถานที่
function getLocations() {
    global $supabase_url, $supabase_key;
    
    list($httpLoc, $respLoc) = call_supabase(
        'GET',
        $supabase_url . "/rest/v1/locations?select=id,location_name,camera_id",
        $supabase_key,
        null,
        null
    );
    return $httpLoc === 200 ? json_decode($respLoc, true) : [];
}

// ฟังก์ชันสำหรับจัดการการเพิ่มสถานที่
function handleAddLocation() {
    global $supabase_url, $supabase_key;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_location'])) {
        $location_name = trim($_POST['location_name'] ?? '');
        $camera_id     = trim($_POST['camera_id'] ?? '');

        if ($location_name !== '' && $camera_id !== '') {
            $data = ['location_name' => $location_name, 'camera_id' => $camera_id];
            list($httpAdd) = call_supabase(
                'POST',
                $supabase_url . "/rest/v1/locations",
                $supabase_key,
                $data,
                'return=minimal'
            );
            $success = ($httpAdd === 201 || $httpAdd === 204) ? "เพิ่มสถานที่สำเร็จ!" : "เกิดข้อผิดพลาดในการเพิ่มสถานที่ (HTTP {$httpAdd})";
            setMessage($httpAdd === 201 || $httpAdd === 204 ? 'success' : 'error', $success);
            
            // เพิ่มการรีเฟรชหลังบันทึกสำเร็จ
            if ($httpAdd === 201 || $httpAdd === 204) {
                echo "<script>
                    setTimeout(function() {
                        window.location.href = window.location.pathname;
                    }, 1500);
                </script>";
            }
        } else {
            setMessage('error', "กรุณากรอกข้อมูลให้ครบถ้วน");
        }
    }
}

// ฟังก์ชันสำหรับจัดการการลบสถานที่
function handleDeleteLocation() {
    global $supabase_url, $supabase_key;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_location'])) {
        $location_id = trim($_POST['location_id'] ?? '');
        if ($location_id !== '') {
            // ดึงข้อมูลสถานที่ที่จะลบเพื่อหา camera_id และ location_name
            $url_get = $supabase_url . "/rest/v1/locations?id=eq." . urlencode($location_id) . "&select=camera_id,location_name";
            list($httpGet, $respGet) = call_supabase('GET', $url_get, $supabase_key, null, null);
            
            $camera_id = null;
            $location_name = '';
            if ($httpGet === 200) {
                $location_data = json_decode($respGet, true);
                if (!empty($location_data)) {
                    $camera_id = $location_data[0]['camera_id'];
                    $location_name = $location_data[0]['location_name'];
                }
            }

            if ($camera_id) {
                // ลบข้อมูลที่เกี่ยวข้องในตาราง parking_slots_status ก่อน
                $url_parking = $supabase_url . "/rest/v1/parking_slots_status?location_id=eq." . urlencode($location_id);
                list($httpParking) = call_supabase('DELETE', $url_parking, $supabase_key);

                // ลบสถานที่
                $url_delete = $supabase_url . "/rest/v1/locations?id=eq." . urlencode($location_id);
                list($httpDelete) = call_supabase('DELETE', $url_delete, $supabase_key);

                if ($httpDelete === 204 || $httpDelete === 200) {
                    setMessage('success', "ลบสถานที่สำเร็จ");
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = window.location.pathname;
                        }, 1500);
                    </script>";
                } else {
                    setMessage('error', "เกิดข้อผิดพลาดในการลบสถานที่ (HTTP {$httpDelete})");
                }
            } else {
                setMessage('error', "ไม่พบข้อมูลสถานที่ที่ต้องการลบ");
            }
        } else {
            setMessage('error', "กรุณาเลือกสถานที่ที่ต้องการลบ");
        }
    }
}
?>