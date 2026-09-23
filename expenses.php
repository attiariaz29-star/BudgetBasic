<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid = (int)$user['id'];
$action = $_GET['action'] ?? 'list';
$cats = user_categories($pdo, $uid);

if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['id'], $uid]);
    flash('success', 'Expense deleted.');
    redirect('expenses.php');
}
$edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['id'], $uid]); $edit = $stmt->fetch();
    if (!$edit) { flash('danger', 'Record not found.'); redirect('expenses.php'); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = (int)($_POST['category_id'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $amount = $_POST['amount'] ?? '';
    $date = $_POST['date'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($desc === '' || !valid_amount($amount) || !valid_date($date)) flash('danger', 'Please enter a valid description, positive amount, and date.');
    else {
        // ensure category belongs to user or global
        if ($cat > 0) {
            $c = $pdo->prepare("SELECT id FROM categories WHERE id=? AND (user_id IS NULL OR user_id=?)");
            $c->execute([$cat, $uid]);
            if (!$c->fetch()) $cat = 0;
        }
        $cid = $cat > 0 ? $cat : null;
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE expenses SET category_id=?, description=?, amount=?, date=? WHERE id=? AND user_id=?");
            $stmt->execute([$cid, $desc, $amount, $date, $id, $uid]);
            flash('success', 'Expense updated successfully.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, category_id, description, amount, date) VALUES (?,?,?,?,?)");
            $stmt->execute([$uid, $cid, $desc, $amount, $date]);
            flash('success', 'Expense added successfully.');
        }
        redirect('expenses.php');
    }
}

$q = trim($_GET['q'] ?? ''); $fcat = (int)($_GET['cat'] ?? 0);
$from = $_GET['from'] ?? ''; $to = $_GET['to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$order = match($sort){ 'oldest'=>'e.date ASC','highest'=>'e.amount DESC','lowest'=>'e.amount ASC','az'=>'e.description ASC', default=>'e.date DESC' };
$sql = "SELECT e.*, c.name cat_name, c.color cat_color FROM expenses e LEFT JOIN categories c ON c.id=e.category_id WHERE e.user_id=?"; $p=[$uid];
if ($q!==''){ $sql.=" AND e.description LIKE ?"; $p[]="%$q%"; }
if ($fcat>0){ $sql.=" AND e.category_id=?"; $p[]=$fcat; }
if (valid_date($from)){ $sql.=" AND e.date>=?"; $p[]=$from; }
if (valid_date($to)){ $sql.=" AND e.date<=?"; $p[]=$to; }
$sql .= " ORDER BY $order LIMIT 300";
$stmt=$pdo->prepare($sql); $stmt->execute($p); $rows=$stmt->fetchAll();
$total=array_sum(array_column($rows,'amount'));

$page_title='Expenses'; $page_sub='Search, filter, sort and manage spending.'; $active='expenses.php';
include __DIR__ . '/includes/header.php';
?>
<?php if ($action==='add'||$action==='edit'): ?>
<div class="row justify-content-center"><div class="col-lg-6"><div class="bb-card p-4">
<h5><?= $edit?'Edit expense':'Add expense' ?></h5>
<form method="post">
<input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="row g-2"><div class="col-6"><label class="form-label fw-semibold">Category</label><select name="category_id" class="form-select">
<?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($edit['category_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
</select></div>
<div class="col-6"><label class="form-label fw-semibold">Date *</label><input name="date" type="date" class="form-control" required value="<?= e($edit['date']??date('Y-m-d')) ?>"></div></div>
<div class="mt-2"><label class="form-label fw-semibold">Description *</label><input name="description" class="form-control" required value="<?= e($edit['description']??'') ?>" placeholder="Groceries, rent…"></div>
<div class="mt-2"><label class="form-label fw-semibold">Amount (Rs.) *</label><input name="amount" type="number" step="0.01" min="0.01" class="form-control" required value="<?= e((string)($edit['amount']??'')) ?>"></div>
<div class="d-flex gap-2 mt-3"><button class="btn btn-primary-bb"><?= $edit?'Save changes':'Add expense' ?></button><a href="expenses.php" class="btn btn-ghost">Cancel</a></div>
</form></div></div></div>
<?php else: ?>
<div class="bb-card p-3 mb-3"><form class="row g-2" method="get">
<div class="col-md-3"><input name="q" class="form-control" placeholder="Search description…" value="<?= e($q) ?>"></div>
<div class="col-6 col-md-2"><select name="cat" class="form-select"><option value="0">All categories</option><?php foreach($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $fcat==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-6 col-md-2"><input name="from" type="date" class="form-control" value="<?= e($from) ?>"></div>
<div class="col-6 col-md-2"><input name="to" type="date" class="form-control" value="<?= e($to) ?>"></div>
<div class="col-6 col-md-2"><select name="sort" class="form-select"><option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option><option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest</option><option value="highest" <?= $sort==='highest'?'selected':'' ?>>Highest</option><option value="lowest" <?= $sort==='lowest'?'selected':'' ?>>Lowest</option><option value="az" <?= $sort==='az'?'selected':'' ?>>A–Z</option></select></div>
<div class="col-12 col-md-1 d-flex gap-2"><button class="btn btn-primary-bb flex-fill">Go</button></div>
</form></div>
<div class="bb-card p-4">
<div class="d-flex justify-content-between align-items-center mb-2"><h5 class="mb-0">Expense history</h5><div class="d-flex gap-2"><span class="badge text-bg-danger">Total: <?= money($total) ?></span><a href="expenses.php?action=add" class="btn btn-primary-bb btn-sm">+ Add</a></div></div>
<?php if(!$rows): ?><div class="empty"><div class="big">🧾</div><p class="fw-semibold mb-1">No expenses yet</p><p class="text-muted small">Start tracking your spending today.</p><a href="expenses.php?action=add" class="btn btn-primary-bb btn-sm">+ Add Expense</a></div>
<?php else: ?><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Category</th><th>Description</th><th>Date</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr>
<td><span class="badge" style="background:<?= e($r['cat_color']??'#64748b') ?>"><?= e($r['cat_name']??'Other') ?></span></td>
<td><?= e($r['description']) ?></td><td><?= e($r['date']) ?></td><td class="text-end fw-bold"><?= money($r['amount']) ?></td>
<td class="text-end"><a class="btn btn-sm btn-ghost" href="expenses.php?action=edit&id=<?= $r['id'] ?>"><i class="bi bi-pencil"></i></a> <a class="btn btn-sm btn-ghost text-danger" data-confirm="Are you sure you want to delete this expense?" href="expenses.php?action=delete&id=<?= $r['id'] ?>"><i class="bi bi-trash"></i></a></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
