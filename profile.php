<?php
$pageTitle = 'My Profile';
require_once $_SERVER['DOCUMENT_ROOT'].'/Vehicles Fault Diagnosis and Service Monitoring System/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/Vehicles Fault Diagnosis and Service Monitoring System/includes/auth.php';
requireLogin();
$uid = $_SESSION['user_id'];
$conn = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'update') {
        $st = $conn->prepare("UPDATE users SET name=?,phone=?,address=? WHERE id=?");
        $st->bind_param("sssi",$_POST['name'],$_POST['phone'],$_POST['address'],$uid);
        if ($st->execute()) { $_SESSION['name']=$_POST['name']; $_SESSION['user_name']=$_POST['name']; $msg='Profile updated.'; }
        else $err = $conn->error;
    } elseif ($a === 'password') {
        $u = $conn->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();
        if (!password_verify($_POST['current'],$u['password'])) { $err='Current password incorrect.'; }
        elseif ($_POST['newpass'] !== $_POST['confirm']) { $err='Passwords do not match.'; }
        else {
            $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($_POST['newpass'],PASSWORD_DEFAULT),$uid]);
            $msg = 'Password changed successfully.';
        }
    }
}

$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

require_once $_SERVER['DOCUMENT_ROOT'].'/Vehicles Fault Diagnosis and Service Monitoring System/includes/header.php';
?>
<div class="container-fluid"><div class="row justify-content-center"><div class="col-lg-6">
<?php if($msg): ?><div class="alert alert-success alert-dismissible py-2"><?=$msg?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-danger alert-dismissible py-2"><?=$err?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<h4 class="fw-bold mb-4">My Profile</h4>

<div class="card mb-3">
  <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-person me-2"></i>Personal Information</h6></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?=htmlspecialchars($user['name'])?>" required></div>
      <div class="mb-3"><label class="form-label">Username</label><input type="text" class="form-control" value="<?=htmlspecialchars($user['username']??'')?>" disabled></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?=htmlspecialchars($user['email'])?>" disabled></div>
      <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?=htmlspecialchars($user['phone']??'')?>"></div>
      <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?=htmlspecialchars($user['address']??'')?>"></div>
      <div class="mb-3"><label class="form-label">Role</label><input type="text" class="form-control" value="<?=ucfirst($user['role'])?>" disabled></div>
      <button type="submit" class="btn btn-dark">Save Changes</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0"><i class="bi bi-lock me-2"></i>Change Password</h6></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="action" value="password">
      <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="newpass" class="form-control" minlength="6" required></div>
      <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm" class="form-control" required></div>
      <button type="submit" class="btn btn-warning text-dark">Change Password</button>
    </form>
  </div>
</div>
</div></div></div>
<?php require_once $_SERVER['DOCUMENT_ROOT'].'/Vehicles Fault Diagnosis and Service Monitoring System/includes/footer.php'; ?>
