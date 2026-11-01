<?php
$pageTitle = 'Service Log';
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

$uid     = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$isFleet = in_array($role, ['admin', 'fleet_manager']);
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE service_logs SET vehicle_id=?,service_type=?,description=?,cost=?,mileage_at_service=?,service_date=?,next_service_date=?,technician=?,service_center=? WHERE id=?");
        $stmt->bind_param("issdisssi", $_POST['vehicle_id'], $_POST['service_type'], $_POST['description'],
            $_POST['cost'], $_POST['mileage_at_service'], $_POST['service_date'],
            $_POST['next_service_date'] ?: null, $_POST['technician'], $_POST['service_center'], $_POST['log_id']);
        $stmt->execute() ? $msg = 'Record updated.' : $err = $conn->error;

    } elseif ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO service_logs (vehicle_id,service_type,description,cost,mileage_at_service,service_date,next_service_date,technician,service_center) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issdissss", $_POST['vehicle_id'], $_POST['service_type'], $_POST['description'],
            $_POST['cost'], $_POST['mileage_at_service'], $_POST['service_date'],
            $_POST['next_service_date'] ?: null, $_POST['technician'], $_POST['service_center']);
        if ($stmt->execute()) {
            // Update vehicle last_service and mileage
            $conn->prepare("UPDATE vehicles SET last_service=?, mileage=? WHERE id=?")
                ->execute([$_POST['service_date'], $_POST['mileage_at_service'], $_POST['vehicle_id']]);
            $msg = 'Service record added.';
        } else { $err = $conn->error; }

    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM service_logs WHERE id=?")->execute([(int)$_POST['log_id']]);
        $msg = 'Record deleted.';
    }
}

$vWhere = $isFleet ? "" : "WHERE user_id = $uid";
$myVehicles = $conn->query("SELECT id, make, model, license_plate FROM vehicles $vWhere ORDER BY make")->fetch_all(MYSQLI_ASSOC);

$filterVehicle = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$where = $isFleet ? [] : ["v.user_id = $uid"];
if ($filterVehicle) $where[] = "sl.vehicle_id = $filterVehicle";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$logs = $conn->query("
    SELECT sl.*, v.make, v.model, v.license_plate
    FROM service_logs sl JOIN vehicles v ON sl.vehicle_id = v.id
    $whereSQL ORDER BY sl.service_date DESC")->fetch_all(MYSQLI_ASSOC);

// Total cost
$totalCost = array_sum(array_column($logs, 'cost'));

// Upcoming services
$upcomingWhere = $isFleet ? "WHERE sl.next_service_date >= CURDATE()" : "WHERE sl.next_service_date >= CURDATE() AND v.user_id = $uid";
$upcoming = $conn->query("
    SELECT sl.*, v.make, v.model, v.license_plate
    FROM service_logs sl JOIN vehicles v ON sl.vehicle_id=v.id
    $upcomingWhere ORDER BY sl.next_service_date ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">

<?php if ($msg): ?><div class="alert alert-success alert-dismissible py-2"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger py-2"><?= $err ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Service Log</h4>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addLogModal">
        <i class="bi bi-plus-lg me-1"></i>Add Service Record
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-check"></i></div>
                <div><div class="fs-4 fw-bold"><?= count($logs) ?></div><div class="text-muted small">Total Records</div></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-currency-dollar"></i></div>
                <div><div class="fs-4 fw-bold">$<?= number_format($totalCost,2) ?></div><div class="text-muted small">Total Spent</div></div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-calendar-event"></i></div>
                <div><div class="fs-4 fw-bold"><?= count($upcoming) ?></div><div class="text-muted small">Upcoming Services</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <!-- Filter -->
        <div class="card mb-3 p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-6">
                    <label class="form-label small fw-semibold">Filter by Vehicle</label>
                    <select name="vehicle_id" class="form-select form-select-sm">
                        <option value="">All Vehicles</option>
                        <?php foreach ($myVehicles as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= $filterVehicle==$v['id']?'selected':'' ?>><?= htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3"><button type="submit" class="btn btn-sm btn-dark w-100">Filter</button></div>
                <div class="col-sm-3"><a href="service_log.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
            </form>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Vehicle</th><th>Service Type</th><th>Date</th><th>Mileage</th><th>Cost</th><th>Center</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($l['make'].' '.$l['model']) ?><br><code class="small"><?= $l['license_plate'] ?></code></td>
                            <td>
                                <div class="fw-semibold small"><?= htmlspecialchars($l['service_type']) ?></div>
                                <?php if ($l['technician']): ?><div class="text-muted" style="font-size:.75rem"><i class="bi bi-person me-1"></i><?= htmlspecialchars($l['technician']) ?></div><?php endif; ?>
                            </td>
                            <td class="small"><?= date('d M Y', strtotime($l['service_date'])) ?></td>
                            <td class="small"><?= $l['mileage_at_service'] ? number_format($l['mileage_at_service']).' km' : '—' ?></td>
                            <td class="fw-semibold text-success small">$<?= number_format($l['cost'],2) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($l['service_center'] ?: '—') ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" onclick="editLog(<?= htmlspecialchars(json_encode($l)) ?>)"><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="log_id" value="<?= $l['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this record?"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No service records found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Services -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-warning"></i>Upcoming Services</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcoming)): ?>
                <p class="text-muted small p-3 mb-0">No upcoming services scheduled.</p>
                <?php else: ?>
                <?php foreach ($upcoming as $u):
                    $daysLeft = (int)((strtotime($u['next_service_date']) - time()) / 86400);
                    $urgency  = $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success');
                ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold small"><?= htmlspecialchars($u['service_type']) ?></div>
                            <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($u['make'].' '.$u['model']) ?></div>
                        </div>
                        <span class="badge bg-<?= $urgency ?>"><?= $daysLeft ?> days</span>
                    </div>
                    <div class="text-muted small mt-1"><i class="bi bi-calendar me-1"></i><?= date('d M Y', strtotime($u['next_service_date'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Edit Log Modal -->
<div class="modal fade" id="editLogModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="log_id" id="el_id">
      <div class="modal-header"><h5 class="modal-title fw-bold">Edit Service Record</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle</label>
                <select name="vehicle_id" id="el_vehicle" class="form-select" required>
                    <?php foreach ($myVehicles as $v): ?>
                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Service Type</label><input type="text" name="service_type" id="el_type" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="el_desc" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Cost ($)</label><input type="number" name="cost" id="el_cost" class="form-control" step="0.01" min="0"></div>
            <div class="col-md-4"><label class="form-label">Mileage at Service</label><input type="number" name="mileage_at_service" id="el_mileage" class="form-control" min="0"></div>
            <div class="col-md-4"><label class="form-label">Service Date</label><input type="date" name="service_date" id="el_date" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Next Service Date</label><input type="date" name="next_service_date" id="el_next" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Technician</label><input type="text" name="technician" id="el_tech" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Service Center</label><input type="text" name="service_center" id="el_center" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Save Changes</button></div>
    </form>
  </div>
</div>

<!-- Add Log Modal -->
<div class="modal fade" id="addLogModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title fw-bold">Add Service Record</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle</label>
                <select name="vehicle_id" class="form-select" required>
                    <option value="">Select vehicle...</option>
                    <?php foreach ($myVehicles as $v): ?>
                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Service Type</label><input type="text" name="service_type" class="form-control" placeholder="e.g. Oil Change" required></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label">Cost ($)</label><input type="number" name="cost" class="form-control" step="0.01" value="0.00" min="0"></div>
            <div class="col-md-4"><label class="form-label">Mileage at Service</label><input type="number" name="mileage_at_service" class="form-control" min="0"></div>
            <div class="col-md-4"><label class="form-label">Service Date</label><input type="date" name="service_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Next Service Date</label><input type="date" name="next_service_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Technician</label><input type="text" name="technician" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Service Center</label><input type="text" name="service_center" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Save Record</button></div>
    </form>
  </div>
</div>
</div>
<script>
function editLog(l) {
    document.getElementById('el_id').value      = l.id;
    document.getElementById('el_vehicle').value = l.vehicle_id;
    document.getElementById('el_type').value    = l.service_type;
    document.getElementById('el_desc').value    = l.description || '';
    document.getElementById('el_cost').value    = l.cost;
    document.getElementById('el_mileage').value = l.mileage_at_service || '';
    document.getElementById('el_date').value    = l.service_date;
    document.getElementById('el_next').value    = l.next_service_date || '';
    document.getElementById('el_tech').value    = l.technician || '';
    document.getElementById('el_center').value  = l.service_center || '';
    new bootstrap.Modal(document.getElementById('editLogModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
