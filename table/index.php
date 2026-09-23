<?php

$isMobile = preg_match(
    '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
    $_SERVER['HTTP_USER_AGENT']
);

if ($isMobile) {
    require __DIR__ . '/mobile.php';
} else {
    require __DIR__ . '/desktop.php';
}