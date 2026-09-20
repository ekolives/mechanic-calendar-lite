<?php
function audit_log($conn, $user, $process, $value) {
    $stmt = $conn->prepare("INSERT INTO audit (sys_submiter, sys_createdate, process, value) VALUES (?, NOW(), ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $user, $process, $value);
        $stmt->execute();
        $stmt->close();
    }
}

?>