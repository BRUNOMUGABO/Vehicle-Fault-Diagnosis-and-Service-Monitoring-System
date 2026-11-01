<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { redirectBasedOnRole(); }
header('Location: home.php');
exit;
