<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/database.php';
$user = current_user();
$q=trim($_GET['q']??''); $cat=$_GET['cat']??'all'; $sort=$_GET['sort']??'new';
$cats=$pdo->query("SELECT DISTINCT category FROM articles ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$sql="SELECT * FROM articles WHERE 1=1"; $p=[];
if($q!==''){ $sql.=" AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)"; $p[]="%$q%";$p[]="%$q%";$p[]="%$q%"; }
if($cat!=='all'){ $sql.=" AND category=?"; $p[]=$cat; }
$sql.=$sort==='old'?" ORDER BY created_at ASC":" ORDER BY created_at DESC";
$s=$pdo->prepare($sql); $s->execute($p); $arts=$s->fetchAll();
$view=null;
if(isset($_GET['slug'])){ $s=$pdo->prepare("SELECT * FROM articles WHERE slug=?"); $s->execute([$_GET['slug']]); $view=$s->fetch(); }
$page_title='Learn'; $page_sub='Bite-size financial education.'; $active='learning.php';
include __DIR__ . '/includes/header.php';
?>
<?php if($view): ?>
<a href="learning.php" class="btn btn-ghost btn-sm mb-3">← All articles</a>
<div class="bb-card p-4 p-lg-5"><span class="badge text-bg-primary"><?= e($view['category']) ?> · <?= (int)$view['read_minutes'] ?> min</span>
<h2 class="mt-2"><?= e($view['title']) ?></h2><p class="text-muted"><?= e($view['excerpt']??'') ?></p><hr>
<div style="white-space:pre-line"><?= e($view['content']) ?></div></div>
<?php else: ?>
<div class="bb-card p-3 mb-3"><form class="row g-2" method="get">
<div class="col-md-5"><input name="q" class="form-control" placeholder="Search articles…" value="<?= e($q) ?>"></div>
<div class="col-6 col-md-3"><select name="cat" class="form-select"><option value="all">All categories</option><?php foreach($cats as $c): ?><option <?= $cat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
<div class="col-6 col-md-2"><select name="sort" class="form-select"><option value="new" <?= $sort==='new'?'selected':'' ?>>Newest</option><option value="old" <?= $sort==='old'?'selected':'' ?>>Oldest</option></select></div>
<div class="col-md-2"><button class="btn btn-primary-bb w-100">Search</button></div></form></div>
<div class="row g-3"><?php if(!$arts): ?><div class="col-12"><div class="empty"><div class="big">📚</div><p class="fw-semibold">No articles found</p></div></div><?php endif; ?>
<?php foreach($arts as $a): ?><div class="col-md-6 col-lg-4"><div class="bb-card hover p-4 h-100">
<span class="icon-tile mb-2" style="background:#4f46e5"><i class="bi <?= e($a['icon']) ?>"></i></span>
<div class="small text-muted"><?= e($a['category']) ?> · <?= (int)$a['read_minutes'] ?> min read</div>
<h5 class="mt-1"><?= e($a['title']) ?></h5><p class="text-muted small"><?= e($a['excerpt']??'') ?></p>
<a href="learning.php?slug=<?= e($a['slug']) ?>" class="fw-semibold">Read article →</a></div></div><?php endforeach; ?></div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
