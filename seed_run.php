<?php
require_once 'config/database.php';
$conn = getDB();

$sql = file_get_contents(__DIR__ . '/sql/seed_data.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

$ok = 0; $fail = 0; $errors = [];
foreach ($statements as $stmt) {
    if (empty($stmt) || strpos($stmt, '--') === 0 || strpos($stmt, 'USE ') === 0) continue;
    if ($conn->query($stmt) === true) {
        $ok++;
    } else {
        $fail++;
        $errors[] = $conn->error . ' — <code>' . htmlspecialchars(substr($stmt, 0, 80)) . '...</code>';
    }
}
?>
<!DOCTYPE html><html><head><title>Seed Data</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<h4>Seed Data Import</h4>
<div class="alert alert-success">✓ <?= $ok ?> statements executed successfully.</div>
<?php if ($fail): ?>
<div class="alert alert-warning">⚠ <?= $fail ?> statements skipped (may already exist):</div>
<ul class="small"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<a href="admin/reports.php" class="btn btn-dark mt-3">View Reports →</a>
</body></html>
