<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) logActivity($_SESSION['user_id'], 'LOGOUT', 'User logged out');

// Fully clear session before showing the logged-out page.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logged Out</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<div class="container min-vh-100 d-flex align-items-center justify-content-center">
		<div class="card shadow-sm border-0" style="max-width:460px;width:100%;">
			<div class="card-body p-4 p-md-5 text-center">
				<h4 class="fw-bold mb-2">You have been logged out</h4>
				<p class="text-muted mb-4">Your session ended successfully.</p>
				<a href="<?= SITE_URL ?>/home.php" class="btn btn-dark w-100">Go to Home Page</a>
			</div>
		</div>
	</div>
</body>
</html>