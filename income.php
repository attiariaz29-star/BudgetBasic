<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid = (int)$user['id'];
$action = $_GET['action'] ?? 'list';

// Delete
if (($action === 'delete') && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM income WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['id'], $uid]);
    flash('success', 'Income deleted.');
    redirect('income.php');
}

// Add / Edit submit
$edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM income WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['id'], $uid]);
    $edit = $stmt->fetch();
    if (!$edit) { flash('danger', 'Record not found.'); redirect('income.php'); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source = trim($_POST['source'] ?? '');
    $amount = $_POST['amount'] ?? '';
    $date = $_POST['date'] ?? '';
    $desc = trim($_POST['description'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($source === '' || !valid_amount($amount) || !valid_date($date)) {
        flash('danger', 'Please enter a valid source, positive amount, and date.');
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE income SET source=?, amount=?, description=?, date=? WHERE id=? AND user_id=?");
            $stmt->execute([$source, $amount, $desc ?: null, $date, $id, $uid]);
            flash('success', 'Income updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO income (user_id, source, amount, description, date) VALUES (?,?,?,?,?)");
            $stmt->execute([$uid, $source, $amount, $desc ?: null, $date]);
            flash('success', 'Income added successfully.');
        }
        redirect('income.php');
    }
}

// Filters
$q = trim($_GET['q'] ?? '');
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$order = match($sort) { 'oldest' => 'date ASC', 'highest' => 'amount DESC', 'lowest' => 'amount ASC', 'az' => 'source ASC', default => 'date DESC' };
$sql = "SELECT * FROM income WHERE user_id=?"; $p = [$uid];
if ($q !== '') { $sql .= " AND (source LIKE ? OR description LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
if (valid_date($from)) { $sql .= " AND date >= ?"; $p[] = $from; }
if (valid_date($to)) { $sql .= " AND date <= ?"; $p[] = $to; }
$sql .= " ORDER BY $order LIMIT 200";
$stmt = $pdo->prepare($sql); $stmt->execute($p); $rows = $stmt->fetchAll();
$total = array_sum(array_column($rows, 'amount'));

$page_title = 'Income'; $page_sub = 'Track every rupee you earn.'; $active='income.php';
include __DIR__ . '/includes/header.php';
?>
<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="row justify-content-center"><div class="col-lg-6"><div class="bb-card p-4">
  <h5><?= $edit ? 'Edit income' : 'Add income' ?></h5>
  <form method="post">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="mb-2"><label class="form-label fw-semibold">Source *</label><input name="source" class="form-control" required value="<?= e($edit['source'] ?? '') ?>" placeholder="Salary, Freelance…"></div>
    <div class="row g-2"><div class="col-6"><label class="form-label fw-semibold">Amount (Rs.) *</label><input name="amount" type="number" step="0.01" min="0.01" class="form-control" required value="<?= e((string)($edit['amount'] ?? '')) ?>"></div>
    <div class="col-6"><label class="form-label fw-semibold">Date *</label><input name="date" type="date" class="form-control" required value="<?= e($edit['date'] ?? date('Y-m-d')) ?>"></div></div>
    <div class="mt-2"><label class="form-label fw-semibold">Description (optional)</label><input name="description" class="form-control" value="<?= e($edit['description'] ?? '') ?>"></div>
    <div class="d-flex gap-2 mt-3"><button class="btn btn-primary-bb"><?= $edit ? 'Save changes' : 'Add income' ?></button><a href="income.php" class="btn btn-ghost">Cancel</a></div>
  </form>
</div></div></div>
<?php else: ?>
<div class="bb-card p-3 mb-3"><form class="row g-2" method="get">
  <div class="col-md-4"><input name="q" class="form-control" placeholder="Search source or note…" value="<?= e($q) ?>"></div>
  <div class="col-6 col-md-2"><input name="from" type="date" class="form-control" value="<?= e($from) ?>"></div>
  <div class="col-6 col-md-2"><input name="to" type="date" class="form-control" value="<?= e($to) ?>"></div>
  <div class="col-6 col-md-2"><select name="sort" class="form-select"><option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option><option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option><option value="highest" <?= $sort==='highest'?'selected':'' ?>>Highest amount</option><option value="lowest" <?= $sort==='lowest'?'selected':'' ?>>Lowest amount</option><option value="az" <?= $sort==='az'?'selected':'' ?>>A–Z</option></select></div>
  <div class="col-6 col-md-2 d-flex gap-2"><button class="btn btn-primary-bb flex-fill">Filter</button><a href="income.php?action=add" class="btn btn-ghost">+ Add</a></div>
</form></div>
<div class="bb-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Income history</h5><span class="badge text-bg-success">Total: <?= money($total) ?></span></div>
  <?php if (!$rows): ?><div class="empty"><div class="big">💰</div><p class="fw-semibold mb-1">No income yet</p><p class="text-muted small">Add your first income to get started.</p><a href="income.php?action=add" class="btn btn-primary-bb btn-sm">+ Add Income</a></div>
  <?php else: ?><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Source</th><th>Date</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['source']) ?></strong><div class="small text-muted"><?= e($r['description'] ?? '') ?></div></td><td><?= e($r['date']) ?></td><td class="text-end text-success fw-bold">+<?= money($r['amount']) ?></td>
    <td class="text-end"><a class="btn btn-sm btn-ghost" href="income.php?action=edit&id=<?= $r['id'] ?>"><i class="bi bi-pencil"></i></a> <a class="btn btn-sm btn-ghost text-danger" data-confirm="Delete this income record?" href="income.php?action=delete&id=<?= $r['id'] ?>"><i class="bi bi-trash"></i></a></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
