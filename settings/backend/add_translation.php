<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';

// check db_connect.php

if (isset($_POST['sys']) && isset($_POST['locale']) && isset($_POST['name'])) {
    $sys = $_POST['sys'];
    $locale = $_POST['locale'];
    $name = $_POST['name'];

    $sql = "insert into language (sys, locale, name) values (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $sys, $locale, $name);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}