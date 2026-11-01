<?php
$pageTitle = 'Analytics';
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

if (!isAdminOrFleet()) { header('Location: dashboard.php'); exit; }

// Faults by severity
$bySeverity = $conn->query("SELECT severity, COUNT(*) as c FROM faults GROUP BY severity")->fetch_all(MYSQLI_ASSOC);

// Faults by category
$byCategory = $conn->query("SELECT category, COUNT(*) as c FROM faults GROUP BY category ORDER BY c DESC")->fetch_all(MYSQLI_ASSOC);

// Monthly faults last 12 months
$monthly = $conn->query("
    SELECT DATE_FORMAT(detected_at,'%b %Y') as month, COUNT(*) as c
    FROM faults WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY YEAR(detected_at), MONTH(detected_at)
    ORDER BY detected_at")->fetch_all(MYSQLI_ASSOC);

// Vehicle status breakdown
$vStatus = $conn->query("SELECT status, COUNT(*) as c FROM vehicles GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Top 5 most faulty vehicles
$topFaulty = $conn->query("
    SELECT v.make, v.model, v.license_plate, COUNT(f.id) as fault_count
    FROM vehicles v LEFT JOIN faults f ON f.vehicle_id = v.id
    GROUP BY v.id ORDER BY fault_count DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Service cost by month
$serviceCost = $conn->query("
    SELECT DATE_FORMAT(service_date,'%b %Y') as month, SUM(cost) as total
    FROM service_logs WHERE service_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(service_date), MONTH(service_date)
    ORDER BY service_date")->fetch_all(MYSQLI_ASSOC);

// Predictive: vehicles overdue for service (last_service > 6 months ago)
$overdue = $conn->query("
    SELECT v.*, u.name as owner_name,
    DATEDIFF(NOW(), v.last_service) as days_since_service
    FROM vehicles v JOIN users u ON v.user_id=u.id
    WHERE v.last_service < DATE_SUB(NOW(), INTERVAL 6 MONTH) OR v.last_service IS NULL
    ORDER BY days_since_service DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Analytics & Predictive Insights</h4>
    <span class="text-muted small"><i class="bi bi-calendar me-1"></i><?= date('d M Y') ?></span>
</div>

<div class="row g-3 mb-4">
    <!-- Monthly Fault Trend -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-danger"></i>Monthly Fault Trend (12 Months)</h6></div>
            <div class="card-body"><div class="chart-container"><canvas id="monthlyChart"></canvas></div></div>
        </div>
    </div>
    <!-- Severity Breakdown -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-warning"></i>Faults by Severity</h6></div>
            <div class="card-body"><div class="chart-container"><canvas id="severityChart"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Category Bar Chart -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-primary"></i>Faults by Category</h6></div>
            <div class="card-body"><div class="chart-container"><canvas id="categoryChart"></canvas></div></div>
        </div>
    </div>
    <!-- Service Cost -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-currency-dollar me-2 text-success"></i>Service Cost (6 Months)</h6></div>
            <div class="card-body"><div class="chart-container"><canvas id="costChart"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Top Faulty Vehicles -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-danger"></i>Most Fault-Prone Vehicles</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Vehicle</th><th>Plate</th><th>Total Faults</th></tr></thead>
                    <tbody>
                    <?php foreach ($topFaulty as $t): ?>
                    <tr>
                        <td class="fw-semibold small"><?= htmlspecialchars($t['make'].' '.$t['model']) ?></td>
                        <td><code class="small"><?= $t['license_plate'] ?></code></td>
                        <td><span class="badge bg-danger"><?= $t['fault_count'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Predictive: Overdue Service -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-exclamation-circle me-2 text-warning"></i>Predictive Alert — Overdue Service</h6></div>
            <div class="card-body p-0">
                <?php if (empty($overdue)): ?>
                <p class="text-muted small p-3 mb-0"><i class="bi bi-check-circle text-success me-1"></i>All vehicles are up to date.</p>
                <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Vehicle</th><th>Owner</th><th>Last Service</th><th>Days Overdue</th></tr></thead>
                    <tbody>
                    <?php foreach ($overdue as $o): ?>
                    <tr>
                        <td class="fw-semibold small"><?= htmlspecialchars($o['make'].' '.$o['model']) ?><br><code class="small"><?= $o['license_plate'] ?></code></td>
                        <td class="small"><?= htmlspecialchars($o['owner_name']) ?></td>
                        <td class="small"><?= $o['last_service'] ? date('d M Y', strtotime($o['last_service'])) : 'Never' ?></td>
                        <td><span class="badge bg-<?= $o['days_since_service'] > 365 ? 'danger' : 'warning text-dark' ?>"><?= $o['days_since_service'] ?? '∞' ?> days</span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script>
const monthly    = <?= json_encode($monthly) ?>;
const severity   = <?= json_encode($bySeverity) ?>;
const categories = <?= json_encode($byCategory) ?>;
const costData   = <?= json_encode($serviceCost) ?>;

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: { labels: monthly.map(d=>d.month), datasets: [{ label: 'Faults', data: monthly.map(d=>d.c), backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 4 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('severityChart'), {
    type: 'doughnut',
    data: { labels: severity.map(d=>d.severity.charAt(0).toUpperCase()+d.severity.slice(1)), datasets: [{ data: severity.map(d=>d.c), backgroundColor: ['#10b981','#f59e0b','#ef4444','#9d174d'], borderWidth: 2 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});

new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: { labels: categories.map(d=>d.category.charAt(0).toUpperCase()+d.category.slice(1)), datasets: [{ data: categories.map(d=>d.c), backgroundColor: '#3b82f6', borderRadius: 4 }] },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('costChart'), {
    type: 'line',
    data: { labels: costData.map(d=>d.month), datasets: [{ label: 'Cost ($)', data: costData.map(d=>d.total), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4, fill: true, pointRadius: 4 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
<?php require_once 'includes/footer.php'; ?>
