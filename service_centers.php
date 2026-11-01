<?php
$pageTitle = 'Service Centers';
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' && isAdminOrFleet()) {
        $spec = implode(',', (array)($_POST['specialization'] ?? []));
        $stmt = $conn->prepare("INSERT INTO service_centers (name,address,city,phone,email,specialization,rating,opening_hours) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssds", $_POST['name'], $_POST['address'], $_POST['city'],
            $_POST['phone'], $_POST['email'], $spec, $_POST['rating'], $_POST['opening_hours']);
        $stmt->execute() ? $msg = 'Service center added.' : $err = $conn->error;
    } elseif ($action === 'edit' && isAdminOrFleet()) {
        $spec = implode(',', (array)($_POST['specialization'] ?? []));
        $stmt = $conn->prepare("UPDATE service_centers SET name=?,address=?,city=?,phone=?,email=?,specialization=?,rating=?,opening_hours=? WHERE id=?");
        $stmt->bind_param("ssssssdsi", $_POST['name'], $_POST['address'], $_POST['city'],
            $_POST['phone'], $_POST['email'], $spec, $_POST['rating'], $_POST['opening_hours'], $_POST['center_id']);
        $stmt->execute() ? $msg = 'Center updated.' : $err = $conn->error;
    } elseif ($action === 'delete' && isAdminOrFleet()) {
        $conn->prepare("DELETE FROM service_centers WHERE id=?")->execute([(int)$_POST['center_id']]);
        $msg = 'Center deleted.';
    }
}

$filterSpec = $_GET['spec'] ?? '';
$filterCity = trim($_GET['city'] ?? '');
$where = [];
if ($filterSpec) $where[] = "FIND_IN_SET('" . $conn->real_escape_string($filterSpec) . "', specialization)";
if ($filterCity) $where[] = "city LIKE '%" . $conn->real_escape_string($filterCity) . "%'";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$centers = $conn->query("SELECT * FROM service_centers $whereSQL ORDER BY rating DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">

<?php if ($msg): ?><div class="alert alert-success alert-dismissible py-2"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Service Centers</h4>
    <?php if (isAdminOrFleet()): ?>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addCenterModal">
        <i class="bi bi-plus-lg me-1"></i>Add Center
    </button>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 p-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">City</label>
            <input type="text" name="city" class="form-control form-control-sm" value="<?= htmlspecialchars($filterCity) ?>" placeholder="Search city...">
        </div>
        <div class="col-sm-3">
            <label class="form-label small fw-semibold">Specialization</label>
            <select name="spec" class="form-select form-select-sm">
                <option value="">All</option>
                <?php foreach (['engine','transmission','brakes','electrical','suspension','exhaust','fuel','cooling','general'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterSpec===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2"><button type="submit" class="btn btn-sm btn-dark w-100">Filter</button></div>
        <div class="col-sm-2"><a href="service_centers.php" class="btn btn-sm btn-outline-secondary w-100">Reset</a></div>
    </form>
</div>

<div class="row g-3">
<?php foreach ($centers as $c): ?>
<div class="col-md-6 col-lg-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="fw-bold mb-0"><?= htmlspecialchars($c['name']) ?></h6>
                <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i><?= $c['rating'] ?></span>
            </div>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($c['address'].', '.$c['city']) ?></p>
            <?php if ($c['phone']): ?><p class="small mb-1"><i class="bi bi-telephone me-1 text-success"></i><?= htmlspecialchars($c['phone']) ?></p><?php endif; ?>
            <?php if ($c['opening_hours']): ?><p class="small mb-2"><i class="bi bi-clock me-1 text-primary"></i><?= htmlspecialchars($c['opening_hours']) ?></p><?php endif; ?>
            <div class="d-flex flex-wrap gap-1 mt-2">
                <?php foreach (explode(',', $c['specialization']) as $sp): ?>
                <span class="badge bg-light text-dark border"><?= ucfirst(trim($sp)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (isAdminOrFleet()): ?>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-primary" onclick="editCenter(<?= htmlspecialchars(json_encode($c)) ?>)"><i class="bi bi-pencil"></i> Edit</button>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="center_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete this center?"><i class="bi bi-trash"></i></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($centers)): ?>
<div class="col-12"><div class="card p-5 text-center text-muted"><i class="bi bi-geo-alt fs-1 d-block mb-2"></i>No service centers found.</div></div>
<?php endif; ?>
</div>
</div>

<!-- Edit Center Modal -->
<div class="modal fade" id="editCenterModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="center_id" id="ec_id">
      <div class="modal-header"><h5 class="modal-title fw-bold">Edit Service Center</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" id="ec_name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">City</label><input type="text" name="city" id="ec_city" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" id="ec_address" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" id="ec_phone" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" id="ec_email" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Rating (0-5)</label><input type="number" name="rating" id="ec_rating" class="form-control" min="0" max="5" step="0.1"></div>
            <div class="col-md-6"><label class="form-label">Opening Hours</label><input type="text" name="opening_hours" id="ec_hours" class="form-control"></div>
            <div class="col-12">
                <label class="form-label">Specializations</label>
                <div class="d-flex flex-wrap gap-2" id="ec_specs">
                <?php foreach (['engine','transmission','brakes','electrical','suspension','exhaust','fuel','cooling','general'] as $s): ?>
                <div class="form-check"><input class="form-check-input ec-spec" type="checkbox" name="specialization[]" value="<?= $s ?>" id="ecs_<?= $s ?>"><label class="form-check-label small" for="ecs_<?= $s ?>"><?= ucfirst($s) ?></label></div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Save Changes</button></div>
    </form>
  </div>

<div class="modal fade" id="addCenterModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title fw-bold">Add Service Center</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">City</label><input type="text" name="city" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Address</label><input type="text" name="address" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Rating (0-5)</label><input type="number" name="rating" class="form-control" min="0" max="5" step="0.1" value="4.0"></div>
            <div class="col-md-6"><label class="form-label">Opening Hours</label><input type="text" name="opening_hours" class="form-control" placeholder="Mon-Sat 8AM-6PM"></div>
            <div class="col-12">
                <label class="form-label">Specializations</label>
                <div class="d-flex flex-wrap gap-2">
                <?php foreach (['engine','transmission','brakes','electrical','suspension','exhaust','fuel','cooling','general'] as $s): ?>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="specialization[]" value="<?= $s ?>" id="spec_<?= $s ?>"><label class="form-check-label small" for="spec_<?= $s ?>"><?= ucfirst($s) ?></label></div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Add Center</button></div>
    </form>
  </div>
</div>
<script>
function editCenter(c) {
    document.getElementById('ec_id').value      = c.id;
    document.getElementById('ec_name').value    = c.name;
    document.getElementById('ec_city').value    = c.city;
    document.getElementById('ec_address').value = c.address;
    document.getElementById('ec_phone').value   = c.phone || '';
    document.getElementById('ec_email').value   = c.email || '';
    document.getElementById('ec_rating').value  = c.rating;
    document.getElementById('ec_hours').value   = c.opening_hours || '';
    // Reset checkboxes
    document.querySelectorAll('.ec-spec').forEach(cb => cb.checked = false);
    if (c.specialization) {
        c.specialization.split(',').forEach(s => {
            const cb = document.getElementById('ecs_' + s.trim());
            if (cb) cb.checked = true;
        });
    }
    new bootstrap.Modal(document.getElementById('editCenterModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
