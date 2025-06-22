<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT ecgAverage, sbp, dbp FROM readings WHERE id = $id LIMIT 1";
    $result = $health_conn->query($query);

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row); //return all 3 values as JSON
    } else {
        echo json_encode(["ecgAverage" => 0, "sbp" => 0, "dbp" => 0]);
    }
}
?>
