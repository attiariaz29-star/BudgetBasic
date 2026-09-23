<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid=(int)$user['id'];
$cat=$pdo->prepare("SELECT c.name, c.color, SUM(e.amount) t FROM expenses e LEFT JOIN categories c ON c.id=e.category_id WHERE e.user_id=? GROUP BY c.name,c.color ORDER BY t DESC");
$cat->execute([$uid]); $byCat=$cat->fetchAll();
$m=$pdo->prepare("SELECT DATE_FORMAT(date,'%Y-%m') ym, SUM(amount) t FROM expenses WHERE user_id=? AND date>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym");
$m->execute([$uid]); $monthly=$m->fetchAll();
$iv=$pdo->prepare("SELECT DATE_FORMAT(date,'%Y-%m') ym, SUM(amount) t, 'i' k FROM income WHERE user_id=? AND date>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY ym UNION ALL SELECT DATE_FORMAT(date,'%Y-%m'), SUM(amount), 'e' FROM expenses WHERE user_id=? AND date>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(date,'%Y-%m') ORDER BY ym");
$iv->execute([$uid,$uid]); $rows=$iv->fetchAll();
$labels=array_values(array_unique(array_column($rows,'ym'))); sort($labels);
$incMap=[];$expMap=[]; foreach($rows as $r){ if($r['k']==='i')$incMap[$r['ym']]=(float)$r['t']; else $expMap[$r['ym']]=(float)$r['t']; }
$inc=array_map(fn($l)=>$incMap[$l]??0,$labels); $exp=array_map(fn($l)=>$expMap[$l]??0,$labels);
$goals=$pdo->prepare("SELECT goal_name, saved_amount, target_amount FROM savings_goals WHERE user_id=?"); $goals->execute([$uid]); $goals=$goals->fetchAll();
$page_title='Reports'; $page_sub='Analytics from your real data.'; $active='reports.php';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
<div class="col-lg-6"><div class="bb-card p-4"><h5>Expenses by category</h5><?php if(!$byCat): ?><div class="empty mt-2"><div class="big">📊</div><p class="small text-muted">No data yet.</p></div><?php else: ?><canvas id="c1" height="180"></canvas><?php endif; ?></div></div>
<div class="col-lg-6"><div class="bb-card p-4"><h5>Income vs expenses (6 mo)</h5><?php if(!$labels): ?><div class="empty mt-2"><div class="big">📈</div><p class="small text-muted">No data yet.</p></div><?php else: ?><canvas id="c2" height="180"></canvas><?php endif; ?></div></div>
<div class="col-lg-6"><div class="bb-card p-4"><h5>Monthly spending</h5><?php if(!$monthly): ?><div class="empty mt-2"><div class="big">🧾</div><p class="small text-muted">No data yet.</p></div><?php else: ?><canvas id="c3" height="180"></canvas><?php endif; ?></div></div>
<div class="col-lg-6"><div class="bb-card p-4"><h5>Savings progress</h5><?php if(!$goals): ?><div class="empty mt-2"><div class="big">🎯</div><p class="small text-muted">No goals yet.</p><a href="savings.php?action=add" class="btn btn-primary-bb btn-sm">+ Add goal</a></div><?php else: ?><canvas id="c4" height="180"></canvas><?php endif; ?></div></div>
</div>
<?php
$extra_js='<script>'
.($byCat?'new Chart(c1,{type:"doughnut",data:{labels:'.json_encode(array_column($byCat,'name')).',datasets:[{data:'.json_encode(array_map('floatval',array_column($byCat,'t'))).',backgroundColor:'.json_encode(array_map(fn($r)=>$r['color']?:'#6366f1',$byCat)).'}]},options:{plugins:{legend:{position:"bottom"}}}});':'')
.($labels?'new Chart(c2,{type:"bar",data:{labels:'.json_encode($labels).',datasets:[{label:"Income",data:'.json_encode($inc).',backgroundColor:"#16a34a",borderRadius:6},{label:"Expenses",data:'.json_encode($exp).',backgroundColor:"#ef4444",borderRadius:6}]},options:{scales:{y:{beginAtZero:true}}}});':'')
.($monthly?'new Chart(c3,{type:"line",data:{labels:'.json_encode(array_column($monthly,'ym')).',datasets:[{label:"Expenses",data:'.json_encode(array_map('floatval',array_column($monthly,'t'))).',borderColor:"#4f46e5",backgroundColor:"rgba(79,70,229,.15)",fill:true,tension:.4}]},options:{scales:{y:{beginAtZero:true}}}});':'')
.($goals?'new Chart(c4,{type:"bar",data:{labels:'.json_encode(array_column($goals,'goal_name')).',datasets:[{label:"Saved",data:'.json_encode(array_map('floatval',array_column($goals,'saved_amount'))).',backgroundColor:"#0ea5e9",borderRadius:6},{label:"Target",data:'.json_encode(array_map('floatval',array_column($goals,'target_amount'))).',backgroundColor:"#e2e8f0",borderRadius:6}]},options:{scales:{y:{beginAtZero:true}}}});':'')
.'</script>';
include __DIR__ . '/includes/footer.php';
