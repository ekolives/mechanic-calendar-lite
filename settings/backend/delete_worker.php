<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';

// check db_connect.php

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM mechanics WHERE mechanic_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}