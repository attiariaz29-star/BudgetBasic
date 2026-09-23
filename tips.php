<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';
$user = current_user();
$q=trim($_GET['q']??''); $cat=$_GET['cat']??'all'; $sort=$_GET['sort']??'new';
$cats=$pdo->query("SELECT DISTINCT category FROM tips ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$sql="SELECT * FROM tips WHERE 1=1"; $p=[];
if($q!==''){ $sql.=" AND (title LIKE ? OR description LIKE ?)"; $p[]="%$q%";$p[]="%$q%"; }
if($cat!=='all'){ $sql.=" AND category=?"; $p[]=$cat; }
$sql.=$sort==='az'?" ORDER BY title ASC":($sort==='cat'?" ORDER BY category ASC, created_at DESC":($sort==='old'?" ORDER BY created_at ASC":" ORDER BY created_at DESC"));
$s=$pdo->prepare($sql); $s->execute($p); $tips=$s->fetchAll();
$page_title='Money Tips'; $page_sub='Practical tips, searchable and filterable.'; $active='tips.php';
include __DIR__ . '/includes/header.php';
?>
<div class="bb-card p-3 mb-3"><form class="row g-2" method="get">
<div class="col-md-5"><input name="q" class="form-control" placeholder="Search tips…" value="<?= e($q) ?>"></div>
<div class="col-6 col-md-3"><select name="cat" class="form-select"><option value="all">All categories</option><?php foreach($cats as $c): ?><option <?= $cat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
<div class="col-6 col-md-2"><select name="sort" class="form-select"><option value="new" <?= $sort==='new'?'selected':'' ?>>Newest</option><option value="old" <?= $sort==='old'?'selected':'' ?>>Oldest</option><option value="az" <?= $sort==='az'?'selected':'' ?>>A–Z</option><option value="cat" <?= $sort==='cat'?'selected':'' ?>>Category</option></select></div>
<div class="col-md-2"><button class="btn btn-primary-bb w-100">Apply</button></div></form></div>
<div class="row g-3"><?php if(!$tips): ?><div class="col-12"><div class="empty"><div class="big">💡</div><p class="fw-semibold">No tips match your search</p></div></div><?php endif; ?>
<?php $colors=['Budgeting'=>'#4f46e5','Saving'=>'#16a34a','Spending'=>'#ec4899','Emergency'=>'#ef4444','Bills'=>'#0ea5e9'];
foreach($tips as $t): $c=$colors[$t['category']]??'#7c3aed'; ?>
<div class="col-md-6 col-lg-4"><div class="bb-card hover p-4 h-100">
<span class="badge mb-2" style="background:<?= $c ?>"><?= e($t['category']) ?></span>
<h5><?= e($t['title']) ?></h5><p class="text-muted small"><?= e($t['description']) ?></p>
<div class="small text-muted"><?= e(date('M d, Y',strtotime($t['created_at']))) ?></div></div></div>
<?php endforeach; ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
