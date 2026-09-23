<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid = (int)$user['id'];
$action = $_GET['action'] ?? 'list';

if ($action==='delete' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM savings_goals WHERE id=? AND user_id=?")->execute([(int)$_GET['id'],$uid]);
    flash('success','Goal deleted.'); redirect('savings.php');
}
$edit=null;
if ($action==='edit' && isset($_GET['id'])) {
    $s=$pdo->prepare("SELECT * FROM savings_goals WHERE id=? AND user_id=?"); $s->execute([(int)$_GET['id'],$uid]); $edit=$s->fetch();
    if(!$edit){ flash('danger','Goal not found.'); redirect('savings.php'); }
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim($_POST['goal_name']??''); $target=$_POST['target_amount']??''; $saved=$_POST['saved_amount']??0;
    $tdate=$_POST['target_date']??''; $id=(int)($_POST['id']??0);
    if ($name===''||!valid_amount($target)||!is_numeric($saved)||(float)$saved<0) flash('danger','Please enter a valid goal name, positive target, and non-negative saved amount.');
    elseif ($tdate!==''&&!valid_date($tdate)) flash('danger','Invalid target date.');
    else {
        if($id>0){ $pdo->prepare("UPDATE savings_goals SET goal_name=?,target_amount=?,saved_amount=?,target_date=? WHERE id=? AND user_id=?")->execute([$name,$target,$saved,$tdate?:null,$id,$uid]); flash('success','Goal updated.'); }
        else { $pdo->prepare("INSERT INTO savings_goals (user_id,goal_name,target_amount,saved_amount,target_date) VALUES (?,?,?,?,?)")->execute([$uid,$name,$target,$saved,$tdate?:null]); flash('success','Savings goal created.'); }
        redirect('savings.php');
    }
}
$rows=$pdo->prepare("SELECT * FROM savings_goals WHERE user_id=? ORDER BY updated_at DESC"); $rows->execute([$uid]); $rows=$rows->fetchAll();
$page_title='Savings Goals'; $page_sub='Name it, fund it, hit it.'; $active='savings.php';
include __DIR__ . '/includes/header.php';
?>
<?php if($action==='add'||$action==='edit'): ?>
<div class="row justify-content-center"><div class="col-lg-6"><div class="bb-card p-4">
<h5><?= $edit?'Edit goal':'New savings goal' ?></h5>
<form method="post"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="mb-2"><label class="form-label fw-semibold">Goal name *</label><input name="goal_name" class="form-control" required value="<?= e($edit['goal_name']??'') ?>" placeholder="New Laptop"></div>
<div class="row g-2"><div class="col-6"><label class="form-label fw-semibold">Target (Rs.) *</label><input name="target_amount" type="number" step="0.01" min="0.01" class="form-control" required value="<?= e((string)($edit['target_amount']??'')) ?>"></div>
<div class="col-6"><label class="form-label fw-semibold">Saved (Rs.)</label><input name="saved_amount" type="number" step="0.01" min="0" class="form-control" value="<?= e((string)($edit['saved_amount']??0)) ?>"></div></div>
<div class="mt-2"><label class="form-label fw-semibold">Target date</label><input name="target_date" type="date" class="form-control" value="<?= e($edit['target_date']??'') ?>"></div>
<div class="d-flex gap-2 mt-3"><button class="btn btn-primary-bb"><?= $edit?'Save':'Create goal' ?></button><a href="savings.php" class="btn btn-ghost">Cancel</a></div>
</form></div></div></div>
<?php else: ?>
<div class="d-flex justify-content-end mb-3"><a href="savings.php?action=add" class="btn btn-primary-bb">+ New goal</a></div>
<?php if(!$rows): ?><div class="empty"><div class="big">🎯</div><p class="fw-semibold mb-1">No savings goals yet</p><p class="text-muted small">Create your first goal — e.g. Emergency Fund.</p><a href="savings.php?action=add" class="btn btn-primary-bb btn-sm">+ Add Savings Goal</a></div>
<?php else: ?><div class="row g-3"><?php foreach($rows as $g): $p=$g['target_amount']>0?min(100,round($g['saved_amount']/$g['target_amount']*100,1)):0; $rem=max(0,$g['target_amount']-$g['saved_amount']); ?>
<div class="col-md-6 col-xl-4"><div class="bb-card hover p-4 h-100">
<div class="d-flex justify-content-between"><h5 class="mb-0"><?= e($g['goal_name']) ?></h5><strong class="text-success"><?= $p ?>%</strong></div>
<div class="small text-muted">Target <?= money($g['target_amount']) ?> · Saved <?= money($g['saved_amount']) ?></div>
<div class="progress progress-bb my-2"><div class="progress-bar" style="width:<?= $p ?>%"></div></div>
<div class="small text-muted mb-2">Remaining <?= money($rem) ?><?= $g['target_date']?' · Due '.e($g['target_date']):'' ?></div>
<div class="d-flex gap-2"><a class="btn btn-ghost btn-sm flex-fill" href="savings.php?action=edit&id=<?= $g['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
<a class="btn btn-ghost btn-sm text-danger" data-confirm="Delete savings goal '<?= e($g['goal_name']) ?>'?" href="savings.php?action=delete&id=<?= $g['id'] ?>"><i class="bi bi-trash"></i></a></div>
</div></div><?php endforeach; ?></div><?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
