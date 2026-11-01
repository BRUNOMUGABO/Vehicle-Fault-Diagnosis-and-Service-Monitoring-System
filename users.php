<?php
$pageTitle = 'User Management';
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';

if (!hasRole('admin')) { header('Location: dashboard.php'); exit; }

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $_POST['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $err = 'Email already exists.';
        } else {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,phone) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $_POST['name'], $_POST['email'], $hash, $_POST['role'], $_POST['phone']);
            $stmt->execute() ? $msg = 'User created.' : $err = $conn->error;
        }

    } elseif ($action === 'edit') {
        $stmt = $conn->prepare("UPDATE users SET name=?,email=?,role=?,phone=? WHERE id=?");
        $stmt->bind_param("ssssi", $_POST['name'], $_POST['email'], $_POST['role'], $_POST['phone'], $_POST['user_id']);
        $stmt->execute() ? $msg = 'User updated.' : $err = $conn->error;

    } elseif ($action === 'reset_password') {
        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, (int)$_POST['user_id']]);
        $msg = 'Password reset successfully.';

    } elseif ($action === 'delete') {
        $uid = (int)$_POST['user_id'];
        if ($uid === $_SESSION['user_id']) { $err = 'Cannot delete your own account.'; }
        else {
            $conn->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            $msg = 'User deleted.';
        }
    }
}

$users = $conn->query("
    SELECT u.*, COUNT(v.id) as vehicle_count
    FROM users u LEFT JOIN vehicles v ON v.user_id=u.id
    GROUP BY u.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php require_once 'includes/header.php'; ?>
<div class="container-fluid">

<?php if ($msg): ?><div class="alert alert-success alert-dismissible py-2"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger alert-dismissible py-2"><?= $err ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">User Management</h4>
    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>Add User
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Vehicles</th><th>Joined</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                    <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="small"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $u['role']==='admin'?'danger':($u['role']==='fleet_manager'?'primary':'success') ?>">
                            <?= ucfirst(str_replace('_',' ',$u['role'])) ?>
                        </span>
                    </td>
                    <td><span class="badge bg-secondary"><?= $u['vehicle_count'] ?></span></td>
                    <td class="small text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-sm btn-outline-warning" onclick="resetPwd(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>')" title="Reset Password"><i class="bi bi-key"></i></button>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete user '<?= htmlspecialchars($u['name']) ?>' and all their data?"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title fw-bold">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="owner">Vehicle Owner</option>
                <option value="fleet_manager">Fleet Manager</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="6" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Create User</button></div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="user_id" id="eu_id">
      <div class="modal-header"><h5 class="modal-title fw-bold">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" id="eu_name" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="eu_email" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" id="eu_phone" class="form-control"></div>
        <div class="mb-3"><label class="form-label">Role</label>
            <select name="role" id="eu_role" class="form-select">
                <option value="owner">Vehicle Owner</option>
                <option value="fleet_manager">Fleet Manager</option>
                <option value="admin">Admin</option>
            </select>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-dark">Save Changes</button></div>
    </form>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwdModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="user_id" id="rp_id">
      <div class="modal-header"><h5 class="modal-title fw-bold">Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="text-muted small">Resetting password for: <strong id="rp_name"></strong></p>
        <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning text-dark">Reset Password</button></div>
    </form>
  </div>
</div>

<script>
function editUser(u) {
    document.getElementById('eu_id').value    = u.id;
    document.getElementById('eu_name').value  = u.name;
    document.getElementById('eu_email').value = u.email;
    document.getElementById('eu_phone').value = u.phone || '';
    document.getElementById('eu_role').value  = u.role;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
function resetPwd(id, name) {
    document.getElementById('rp_id').value   = id;
    document.getElementById('rp_name').textContent = name;
    new bootstrap.Modal(document.getElementById('resetPwdModal')).show();
}
</script>
<?php require_once 'includes/footer.php'; ?>
