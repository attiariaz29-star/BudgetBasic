<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';
if (current_user()) { redirect('dashboard.php'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['confirm'] ?? '';
    if (strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!valid_email($email)) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists. Try logging in.';
        else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?,?,?)');
            $stmt->execute([$name, $email, $hash]);
            $id = (int)$pdo->lastInsertId();
            login_user(['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'user']);
            flash('success', 'Welcome to BudgetBasics, ' . $name . '! Your account was created.');
            redirect('dashboard.php');
        }
    }
}
$page_title = 'Create account'; $hide_shell = true;
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><div class="container"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
<div class="text-center mb-3"><a class="bb-brand justify-content-center" href="index.php"><span class="brand-mark"><i class="bi bi-wallet2"></i></span> BudgetBasics</a></div>
<div class="card auth-card p-4">
  <h3 class="fw-bold">Start budgeting free</h3><p class="text-muted">Track income, expenses and savings goals.</p>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" novalidate>
    <div class="mb-2"><label class="form-label fw-semibold">Full name</label><input name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="Ali Raza"></div>
    <div class="mb-2"><label class="form-label fw-semibold">Email</label><input name="email" type="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com"></div>
    <div class="row g-2">
      <div class="col-6"><label class="form-label fw-semibold">Password</label><input name="password" type="password" class="form-control" required minlength="8" placeholder="Min. 8 characters"></div>
      <div class="col-6"><label class="form-label fw-semibold">Confirm</label><input name="confirm" type="password" class="form-control" required placeholder="Repeat password"></div>
    </div>
    <button class="btn btn-primary-bb w-100 mt-3">Create account <i class="bi bi-arrow-right"></i></button>
  </form>
  <div class="text-center mt-3 small">Already have an account? <a href="login.php" class="fw-semibold">Log in</a></div>
</div>
<div class="text-center mt-3"><a href="index.php" class="text-muted small">← Back to home</a></div>
</div></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
