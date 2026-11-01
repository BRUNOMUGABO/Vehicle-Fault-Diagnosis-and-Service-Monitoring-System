<?php
require_once 'includes/header.php';

// Redirect to role-specific dashboard
if (isAdmin()) {
    header('Location: admin/dashboard.php');
} elseif (isStaff()) {
    header('Location: staff/dashboard.php');
} elseif (isReceptionist()) {
    header('Location: receptionist/dashboard.php');
} elseif (isClient()) {
    header('Location: client/dashboard.php');
}
exit();
?>