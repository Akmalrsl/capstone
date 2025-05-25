<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT ecgAverage FROM readings WHERE id = $id LIMIT 1";
    $result = $health_conn->query($query);

    if ($row = $result->fetch_assoc()) {
        echo $row['ecgAverage'];
    } else {
        echo "0";
    }
}
?>
