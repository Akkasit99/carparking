<?php
// entrance_data_handler.php - จัดการข้อมูลรถทางเข้า

function getEntranceData($selected_date, $selected_time, $supabase_url, $supabase_key) {
    // สร้าง URL สำหรับ Supabase query พร้อมฟิลเตอร์วันที่
    $start_datetime = $selected_date . 'T' . $selected_time . ':00'; 
    $end_datetime   = $selected_date . 'T23:59:59';

    if ($selected_date == date('Y-m-d') && $selected_time == '00:00') {
        $url = $supabase_url . "/rest/v1/entrance?select=id,image,date,camera_id&order=date.desc";
    } else {
        $url = $supabase_url . "/rest/v1/entrance?select=id,image,date,camera_id"
             . "&date=gte." . urlencode($start_datetime)
             . "&date=lte." . urlencode($end_datetime)
             . "&order=date.desc";
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
    
    return json_decode($response, true);
}

function renderEntranceTable($result, $selected_date, $selected_time) {
    $imagesData = [];
    
    if (is_array($result) && count($result) > 0) {
        foreach ($result as $row) {
            $imgUrl = $row['image'] ?? '';
            if ($imgUrl && !filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                $imgUrl = "https://dgqqonbhhivprdoutkzp.supabase.co/storage/v1/object/public/image/" . ltrim($imgUrl, '/');
            }

            $imagesData[] = [
                'id'     => $row['id'],
                'url'    => $imgUrl,
                'date'   => $row['date'],
                'camera' => $row['camera_id']
            ];

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
            echo '<td class="image-cell">';
            echo '<img src="' . htmlspecialchars($imgUrl) . '" alt="entrance" onclick="openModal(\'' . htmlspecialchars($imgUrl) . '\')">';
            echo '</td>';
            echo '<td class="date-cell">' . date('d/m/Y H:i:s', strtotime($row['date'])) . '</td>';
            echo '<td>' . htmlspecialchars($row['camera_id']) . '</td>';
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
    
    return $imagesData;
}
?>