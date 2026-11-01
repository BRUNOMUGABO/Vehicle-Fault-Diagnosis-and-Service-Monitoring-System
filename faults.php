<?php
$pageTitle = 'Faults';
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
        $stmt = $conn->prepare("UPDATE faults SET vehicle_id=?,fault_code=?,title=?,description=?,category=?,severity=?,status=? WHERE id=?");
        $stmt->bind_param("issssssi", $_POST['vehicle_id'], $_POST['fault_code'], $_POST['title'],
            $_POST['description'], $_POST['category'], $_POST['severity'], $_POST['status'], $_POST['fault_id']);
        $stmt->execute() ? $msg = 'Fault updated.' : $err = $conn->error;

    } elseif ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO faults (vehicle_id,fault_code,title,description,category,severity,status) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $_POST['vehicle_id'], $_POST['fault_code'], $_POST['title'],
            $_POST['description'], $_POST['category'], $_POST['severity'], $_POST['status']);
        $stmt->execute() ? $msg = 'Fault logged.' : $err = $conn->error;
        // Update vehicle status if critical/high
        if ($_POST['severity'] === 'critical') {
            $conn->query("UPDATE vehicles SET status='critical' WHERE id=".(int)$_POST['vehicle_id']);
        } elseif (in_array($_POST['severity'], ['high','medium']) && $_POST['status'] !== 'resolved') {
            $conn->query("UPDATE vehicles SET status='faulty' WHERE id=".(int)$_POST['vehicle_id']." AND status='operational'");
        }

    } elseif ($action === 'update_status') {
        $fid    = (int)$_POST['fault_id'];
        $status = $_POST['status'];
        $resolved = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
        $stmt = $conn->prepare("UPDATE faults SET status=?, resolved_at=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $resolved, $fid);
        $stmt->execute();
        // If resolved, check if vehicle has remaining open faults
        if ($status === 'resolved') {
            $vid = $conn->query("SELECT vehicle_id FROM faults WHERE id=$fid")->fetch_assoc()['vehicle_id'];
            $remaining = $conn->query("SELECT COUNT(*) as c FROM faults WHERE vehicle_id=$vid AND status!='resolved'")->fetch_assoc()['c'];
            if ($remaining == 0) $conn->query("UPDATE vehicles SET status='operational' WHERE id=$vid");
        }
        $msg = 'Status updated.';

    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM faults WHERE id=?")->execute([(int)$_POST['fault_id']]);
        $msg = 'Fault deleted.';
    }
}

// Filters
$filterStatus   = $_GET['status']   ?? '';
$filterSeverity = $_GET['severity'] ?? '';
$filterVehicle  = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;

$where = [];
if (!$isFleet) $where[] = "v.user_id = $uid";
if ($filterStatus)   $where[] = "f.status = '" . $conn->real_escape_string($filterStatus) . "'";
if ($filterSeverity) $where[] = "f.severity = '" . $conn->real_escape_string($filterSeverity) . "'";
if ($filterVehicle)  $where[] = "f.vehicle_id = $filterVehicle";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$fQuery = $conn->query("
    SELECT f.*, v.make, v.model, v.license_plate
    FROM faults f JOIN vehicles v ON f.vehicle_id = v.id
    $whereSQL ORDER BY FIELD(f.severity,'critical','high','medium','low'), f.detected_at DESC");
$faults = $fQuery ? $fQuery->fetch_all(MYSQLI_ASSOC) : [];

// Vehicles for dropdown
$vWhere = $isFleet ? "" : "WHERE user_id = $uid";
$vQuery = $conn->query("SELECT id, make, model, license_plate FROM vehicles $vWhere ORDER BY make");
$myVehicles = $vQuery ? $vQuery->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">

<?php if ($msg): ?><div class="alert alert-success alert-dismissible py-2"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger py-2"><?= $err ?></div><?php endif; ?>

<?php if (empty($myVehicles)): ?>
<div class="alert alert-info alert-dismissible py-3 mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>No vehicles found.</strong> You need to add vehicles before you can log faults.
    <a href="receptionist/vehicles.php" class="alert-link ms-2">Go to Vehicles</a>
    <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Fault Diagnosis</h4>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addFaultModal" <?= empty($myVehicles) ? 'disabled' : '' ?>>
        <i class="bi bi-plus-lg me-1"></i>Log Fault
    </button>
</div>

<!-- Filters -->
<div class="card mb-4 p-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">Vehicle</label>
            <select name="vehicle_id" class="form-select form-select-sm">
                <option value="">All Vehicles</option>
                <?php foreach ($myVehicles as $v): ?>
                <option value="<?= $v['id'] ?>" <?= $filterVehicle==$v['id']?'selected':'' ?>><?= htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="open" <?= $filterStatus==='open'?'selected':'' ?>>Open</option>
                <option value="in_progress" <?= $filterStatus==='in_progress'?'selected':'' ?>>In Progress</option>
                <option value="resolved" <?= $filterStatus==='resolved'?'selected':'' ?>>Resolved</option>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label small fw-semibold">Severity</label>
            <select name="severity" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="critical" <?= $filterSeverity==='critical'?'selected':'' ?>>Critical</option>
                <option value="high" <?= $filterSeverity==='high'?'selected':'' ?>>High</option>
                <option value="medium" <?= $filterSeverity==='medium'?'selected':'' ?>>Medium</option>
                <option value="low" <?= $filterSeverity==='low'?'selected':'' ?>>Low</option>
            </select>
        </div>
        <div class="col-sm-2">
            <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
        </div>
        <div class="col-sm-2">
            <a href="faults.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
        </div>
    </form>
</div>

<!-- Faults Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Code</th><th>Fault</th><th>Vehicle</th><th>Category</th><th>Severity</th><th>Status</th><th>Detected</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($faults as $f): ?>
                <tr class="<?= $f['severity']==='critical' ? 'table-danger' : '' ?>">
                    <td><code class="small"><?= htmlspecialchars($f['fault_code'] ?: '—') ?></code></td>
                    <td>
                        <div class="fw-semibold small"><?= htmlspecialchars($f['title']) ?></div>
                        <div class="text-muted" style="font-size:.75rem;max-width:200px"><?= htmlspecialchars(substr($f['description'],0,60)) ?>...</div>
                    </td>
                    <td class="small"><?= htmlspecialchars($f['make'].' '.$f['model']) ?><br><code class="small"><?= $f['license_plate'] ?></code></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($f['category']) ?></span></td>
                    <td><span class="badge sev-<?= $f['severity'] ?>"><?= ucfirst($f['severity']) ?></span></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="fault_id" value="<?= $f['id'] ?>">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:110px">
                                <option value="open" <?= $f['status']==='open'?'selected':'' ?>>Open</option>
                                <option value="in_progress" <?= $f['status']==='in_progress'?'selected':'' ?>>In Progress</option>
                                <option value="resolved" <?= $f['status']==='resolved'?'selected':'' ?>>Resolved</option>
                            </select>
                        </form>
                    </td>
                    <td class="small text-muted"><?= date('d M Y', strtotime($f['detected_at'])) ?></td>
                    <td class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary" onclick="editFault(<?= htmlspecialchars(json_encode($f)) ?>)"><i class="bi bi-pencil"></i></button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="fault_id" value="<?= $f['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this fault?"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($faults)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-check-circle text-success me-2"></i>No faults found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Edit Fault Modal -->
<div class="modal fade" id="editFaultModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="fault_id" id="ef_id">
      <div class="modal-header"><h5 class="modal-title fw-bold">Edit Fault</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Vehicle</label>
                <select name="vehicle_id" id="ef_vehicle" class="form-select" required>
                    <option value="">Select vehicle...</option>
                    <?php foreach ($myVehicles as $v): ?>
                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['license_plate'].')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Fault Code</label><input type="text" name="fault_code" id="ef_code" class="form-control"></div>
            <div class="col-12"><label class="form-label">Fault Title</label><input type="text" name="title" id="ef_title" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="ef_desc" class="form-control" rows="3"></textarea></div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category" id="ef_cat" class="form-select">
                    <option value="engine">Engine</option><option value="transmission">Transmission</option>
                    <option value="brakes">Brakes</option><option value="electrical">Electrical</option>
                    <option value="suspension">Suspension</option><option value="exhaust">Exhaust</option>
                    <option value="fuel">Fuel</option><option value="cooling">Cooling</option><option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Severity</label>
                <select name="severity" id="ef_sev" class="form-select">
                    <option value="low">Low</option><option value="medium">Medium</option>
                    <option value="high">High</option><option value="critical">Critical</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" id="ef_status" class="form-select">
                    <option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option>
                </select>
            </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Save Changes</button></div>
    </form>
  </div>
</div>

<!-- Add Fault Modal -->
<div class="modal fade" id="addFaultModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title fw-bold">Log New Fault</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
            <div class="col-md-6"><label class="form-label">Fault Code</label><input type="text" name="fault_code" class="form-control" placeholder="e.g. P0300"></div>
            <div class="col-12"><label class="form-label">Fault Title</label><input type="text" name="title" class="form-control" placeholder="Brief description" required></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Detailed description..."></textarea></div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="engine">Engine</option><option value="transmission">Transmission</option>
                    <option value="brakes">Brakes</option><option value="electrical">Electrical</option>
                    <option value="suspension">Suspension</option><option value="exhaust">Exhaust</option>
                    <option value="fuel">Fuel</option><option value="cooling">Cooling</option><option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Severity</label>
                <select name="severity" class="form-select">
                    <option value="low">Low</option><option value="medium" selected>Medium</option>
                    <option value="high">High</option><option value="critical">Critical</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option>
                </select>
            </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Log Fault</button></div>
    </form>
  </div>
</div>
<script>
function editFault(f) {
    document.getElementById('ef_id').value      = f.id;
    document.getElementById('ef_vehicle').value = f.vehicle_id;
    document.getElementById('ef_code').value    = f.fault_code || '';
    document.getElementById('ef_title').value   = f.title;
    document.getElementById('ef_desc').value    = f.description || '';
    document.getElementById('ef_cat').value     = f.category;
    document.getElementById('ef_sev').value     = f.severity;
    document.getElementById('ef_status').value  = f.status;
    new bootstrap.Modal(document.getElementById('editFaultModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
