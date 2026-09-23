<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';
if (current_user()) { redirect('dashboard.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!valid_email($email) || $pass === '') $error = 'Please enter a valid email and password.';
    else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($pass, $u['password'])) $error = 'Invalid email or password.';
        else {
            login_user($u);
            flash('success', 'Welcome back, ' . $u['name'] . '!');
            redirect(($u['role'] === 'admin') ? 'admin/index.php' : 'dashboard.php');
        }
    }
}
$page_title = 'Log in'; $hide_shell = true;
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
<div class="text-center mb-3"><a class="bb-brand justify-content-center" href="index.php"><span class="brand-mark"><i class="bi bi-wallet2"></i></span> BudgetBasics</a></div>
<div class="card auth-card p-4">
  <h3 class="fw-bold">Welcome back</h3><p class="text-muted">Log in to your dashboard.</p>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <div class="mb-2"><label class="form-label fw-semibold">Email</label><input name="email" type="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>"></div>
    <div class="mb-2"><label class="form-label fw-semibold">Password</label><input name="password" type="password" class="form-control" required></div>
    <button class="btn btn-primary-bb w-100 mt-2">Log in</button>
  </form>
  <div class="text-center mt-3 small">New here? <a href="register.php" class="fw-semibold">Create free account</a></div>
  <div class="alert alert-info mt-3 small mb-0">Demo admin: <code>admin@budgetbasics.local</code> — set its password hash in the SQL file, or register then promote via <code>UPDATE users SET role='admin'</code>.</div>
</div>
<div class="text-center mt-3"><a href="index.php" class="text-muted small">← Back to home</a></div>
</div></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
