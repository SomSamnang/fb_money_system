<?php
require_once "config/db.php";

if(isset($_POST['page']) && isset($_POST['type'])){
    $page = $_POST['page'];
    $type = $_POST['type'];
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("INSERT INTO clicks (page, type, ip) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $page, $type, $ip);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['status'=>'ok']);
} else {
    echo json_encode(['status'=>'fail','message'=>'Missing parameters']);
}
?>
