<?php
$pageTitle = 'Vehicles';
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

$uid    = $_SESSION['user_id'];
$role   = $_SESSION['role'];
$isFleet = in_array($role, ['admin', 'fleet_manager']);
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $ownerId = $isFleet ? (int)$_POST['owner_id'] : $uid;
        $stmt = $conn->prepare("INSERT INTO vehicles (user_id,make,model,year,license_plate,vin,mileage,fuel_type,status,last_service) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issiisiss", $ownerId, $_POST['make'], $_POST['model'], $_POST['year'],
            $_POST['license_plate'], $_POST['vin'], $_POST['mileage'], $_POST['fuel_type'], $_POST['status'], $_POST['last_service'] ?: null);
        $stmt->execute() ? $msg = 'Vehicle added successfully.' : $err = $conn->error;

    } elseif ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE vehicles SET make=?,model=?,year=?,license_plate=?,vin=?,mileage=?,fuel_type=?,status=?,last_service=? WHERE id=?");
        $stmt->bind_param("ssisisssi", $_POST['make'], $_POST['model'], $_POST['year'],
            $_POST['license_plate'], $_POST['vin'], $_POST['mileage'], $_POST['fuel_type'], $_POST['status'], $_POST['last_service'], $_POST['vehicle_id']);
        $stmt->execute() ? $msg = 'Vehicle updated.' : $err = $conn->error;

    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM vehicles WHERE id=?")->execute([(int)$_POST['vehicle_id']]);
        $msg = 'Vehicle deleted.';
    }
}

$vWhere    = $isFleet ? "" : "WHERE v.user_id = $uid";
$filterStatus = $_GET['status'] ?? '';

$statusFilter = '';
if ($filterStatus === 'operational') {
    $statusFilter = $isFleet ? "WHERE v.status='operational'" : "WHERE v.user_id=$uid AND v.status='operational'";
} elseif ($filterStatus === 'needs_attention') {
    $statusFilter = $isFleet ? "WHERE v.status IN ('faulty','under_maintenance','critical')" : "WHERE v.user_id=$uid AND v.status IN ('faulty','under_maintenance','critical')";
} elseif ($filterStatus) {
    $s = $conn->real_escape_string($filterStatus);
    $statusFilter = $isFleet ? "WHERE v.status='$s'" : "WHERE v.user_id=$uid AND v.status='$s'";
} else {
    $statusFilter = $vWhere;
}

$vehicles = $conn->query("
    SELECT v.*, u.name as owner_name,
    (SELECT COUNT(*) FROM faults WHERE vehicle_id=v.id AND status!='resolved') as open_faults
    FROM vehicles v JOIN users u ON v.user_id=u.id $statusFilter
    ORDER BY FIELD(v.status,'critical','faulty','under_maintenance','operational')")->fetch_all(MYSQLI_ASSOC);

// For detail view
$detail = null;
if (isset($_GET['id'])) {
    $vid = (int)$_GET['id'];
    $detail = $conn->query("SELECT v.*, u.name as owner_name FROM vehicles v JOIN users u ON v.user_id=u.id WHERE v.id=$vid")->fetch_assoc();
    $detailFaults = $conn->query("SELECT * FROM faults WHERE vehicle_id=$vid ORDER BY detected_at DESC")->fetch_all(MYSQLI_ASSOC);
    $detailLogs   = $conn->query("SELECT * FROM service_logs WHERE vehicle_id=$vid ORDER BY service_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
}

$owners = $isFleet ? $conn->query("SELECT id, name FROM users ORDER BY name")->fetch_all(MYSQLI_ASSOC) : [];

$statusColors = ['operational'=>'success','faulty'=>'danger','under_maintenance'=>'primary','critical'=>'warning'];
?>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">

<?php if ($msg): ?><div class="alert alert-success alert-dismissible py-2"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible py-2"><?= $err ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php if ($detail): ?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="vehicles.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h4 class="fw-bold mb-0"><?= $detail['year'].' '.htmlspecialchars($detail['make'].' '.$detail['model']) ?></h4>
    <span class="badge badge-<?= $detail['status'] ?> ms-2"><?= ucfirst(str_replace('_',' ',$detail['status'])) ?></span>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Vehicle Info</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">License Plate</td><td><code><?= htmlspecialchars($detail['license_plate']) ?></code></td></tr>
                <tr><td class="text-muted">VIN</td><td class="small"><?= htmlspecialchars($detail['vin'] ?: '—') ?></td></tr>
                <tr><td class="text-muted">Fuel Type</td><td><?= ucfirst($detail['fuel_type']) ?></td></tr>
                <tr><td class="text-muted">Mileage</td><td><?= number_format($detail['mileage']) ?> km</td></tr>
                <tr><td class="text-muted">Last Service</td><td><?= $detail['last_service'] ? date('d M Y', strtotime($detail['last_service'])) : '—' ?></td></tr>
                <tr><td class="text-muted">Owner</td><td><?= htmlspecialchars($detail['owner_name']) ?></td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card p-3 mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Active Faults</h6>
            <?php $activeFaults = array_filter($detailFaults, fn($f) => $f['status'] !== 'resolved'); ?>
            <?php if (empty($activeFaults)): ?>
                <p class="text-muted small mb-0"><i class="bi bi-check-circle text-success me-1"></i>No active faults.</p>
            <?php else: ?>
                <?php foreach ($activeFaults as $f): ?>
                <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                    <div>
                        <span class="fw-semibold small"><?= htmlspecialchars($f['title']) ?></span>
                        <span class="text-muted small ms-2"><?= $f['fault_code'] ?></span>
                    </div>
                    <span class="badge sev-<?= $f['severity'] ?>"><?= ucfirst($f['severity']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-tools me-2 text-success"></i>Recent Service</h6>
            <?php if (empty($detailLogs)): ?>
                <p class="text-muted small mb-0">No service records.</p>
            <?php else: ?>
                <?php foreach ($detailLogs as $l): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <div><span class="fw-semibold small"><?= htmlspecialchars($l['service_type']) ?></span><br><span class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($l['service_center'] ?: '') ?></span></div>
                    <div class="text-end"><span class="small"><?= date('d M Y', strtotime($l['service_date'])) ?></span><br><span class="text-success small fw-semibold">$<?= number_format($l['cost'],2) ?></span></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Vehicle List ── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Vehicles</h4>
        <?php if ($filterStatus): ?>
        <span class="badge bg-secondary mt-1">
            Filtered: <?= $filterStatus === 'needs_attention' ? 'Faulty / Under Maintenance / Critical' : ucfirst(str_replace('_',' ',$filterStatus)) ?>
            &nbsp;<a href="vehicles.php" class="text-white"><i class="bi bi-x-circle"></i></a>
        </span>
        <?php endif; ?>
    </div>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i>Add Vehicle
    </button>
</div>

<div class="row g-3">
<?php foreach ($vehicles as $v): ?>
<div class="col-sm-6 col-lg-4">
    <div class="card vehicle-card" onclick="location.href='vehicles.php?id=<?= $v['id'] ?>'">
        <div class="card-body pb-2">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-bold"><?= $v['year'] ?> <?= htmlspecialchars($v['make'].' '.$v['model']) ?></div>
                    <code class="small"><?= htmlspecialchars($v['license_plate']) ?></code>
                </div>
                <?php if ($v['open_faults'] > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $v['open_faults'] ?> fault<?= $v['open_faults']>1?'s':'' ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-3 text-muted small mb-2">
                <span><i class="bi bi-speedometer2 me-1"></i><?= number_format($v['mileage']) ?> km</span>
                <span><i class="bi bi-fuel-pump me-1"></i><?= ucfirst($v['fuel_type']) ?></span>
            </div>
            <?php if ($isFleet): ?>
            <div class="text-muted small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($v['owner_name']) ?></div>
            <?php endif; ?>
        </div>
        <div class="vehicle-status-bar bg-<?= $statusColors[$v['status']] ?? 'secondary' ?>"></div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="badge badge-<?= $v['status'] ?>"><?= ucfirst(str_replace('_',' ',$v['status'])) ?></span>
            <div class="d-flex gap-1" onclick="event.stopPropagation()">
                <button class="btn btn-sm btn-outline-secondary" onclick="editVehicle(<?= htmlspecialchars(json_encode($v)) ?>)"><i class="bi bi-pencil"></i></button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this vehicle and all its data?"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($vehicles)): ?>
<div class="col-12"><div class="card p-5 text-center text-muted"><i class="bi bi-truck fs-1 d-block mb-2"></i>No vehicles found. Add your first vehicle.</div></div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title fw-bold">Add Vehicle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <?php if ($isFleet): ?>
        <div class="mb-3">
            <label class="form-label">Owner</label>
            <select name="owner_id" class="form-select" required>
                <?php foreach ($owners as $o): ?><option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Make</label><input type="text" name="make" class="form-control" placeholder="Toyota" required></div>
            <div class="col-md-4"><label class="form-label">Model</label><input type="text" name="model" class="form-control" placeholder="Camry" required></div>
            <div class="col-md-4"><label class="form-label">Year</label><input type="number" name="year" class="form-control" min="1990" max="2030" value="<?= date('Y') ?>" required></div>
            <div class="col-md-4"><label class="form-label">License Plate</label><input type="text" name="license_plate" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">VIN</label><input type="text" name="vin" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Mileage (km)</label><input type="number" name="mileage" class="form-control" value="0" min="0"></div>
            <div class="col-md-4"><label class="form-label">Fuel Type</label>
                <select name="fuel_type" class="form-select"><option value="petrol">Petrol</option><option value="diesel">Diesel</option><option value="electric">Electric</option><option value="hybrid">Hybrid</option></select>
            </div>
            <div class="col-md-4"><label class="form-label">Status</label>
                <select name="status" class="form-select"><option value="operational">Operational</option><option value="faulty">Faulty</option><option value="under_maintenance">Under Maintenance</option><option value="critical">Critical</option></select>
            </div>
            <div class="col-md-4"><label class="form-label">Last Service Date</label><input type="date" name="last_service" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Add Vehicle</button></div>
    </form>
  </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="vehicle_id" id="edit_id">
      <div class="modal-header"><h5 class="modal-title fw-bold">Edit Vehicle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Make</label><input type="text" name="make" id="edit_make" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Model</label><input type="text" name="model" id="edit_model" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Year</label><input type="number" name="year" id="edit_year" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">License Plate</label><input type="text" name="license_plate" id="edit_plate" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">VIN</label><input type="text" name="vin" id="edit_vin" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Mileage (km)</label><input type="number" name="mileage" id="edit_mileage" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Fuel Type</label>
                <select name="fuel_type" id="edit_fuel" class="form-select"><option value="petrol">Petrol</option><option value="diesel">Diesel</option><option value="electric">Electric</option><option value="hybrid">Hybrid</option></select>
            </div>
            <div class="col-md-4"><label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-select"><option value="operational">Operational</option><option value="faulty">Faulty</option><option value="under_maintenance">Under Maintenance</option><option value="critical">Critical</option></select>
            </div>
            <div class="col-md-4"><label class="form-label">Last Service Date</label><input type="date" name="last_service" id="edit_service" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Save Changes</button></div>
    </form>
  </div>
</div>

<script>
function editVehicle(v) {
    document.getElementById('edit_id').value     = v.id;
    document.getElementById('edit_make').value   = v.make;
    document.getElementById('edit_model').value  = v.model;
    document.getElementById('edit_year').value   = v.year;
    document.getElementById('edit_plate').value  = v.license_plate;
    document.getElementById('edit_vin').value    = v.vin || '';
    document.getElementById('edit_mileage').value= v.mileage;
    document.getElementById('edit_fuel').value   = v.fuel_type;
    document.getElementById('edit_status').value = v.status;
    document.getElementById('edit_service').value= v.last_service || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
