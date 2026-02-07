<?php

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function getCountryFromIP($ip) {
    // Handle Localhost
    if ($ip == '127.0.0.1' || $ip == '::1') {
        return 'Localhost';
    }

    // Use ip-api.com (Free for non-commercial use)
    $url = "http://ip-api.com/json/" . $ip;


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout after 3 seconds to prevent lag
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] == 'success') {
            return $data['country'];
        }
    }

    return 'Unknown';
}

function saveVisitor($conn) {
    $ip = getUserIP();
    $country = getCountryFromIP($ip);
    $visit_date = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("INSERT INTO visitors (ip_address, country, visit_date) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $ip, $country, $visit_date);
        $stmt->execute();
        $stmt->close();
    } else {
        // Database error: Table 'visitors' likely does not exist
    }
}

function countVisitors($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM visitors");
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    return 0;
}
?>