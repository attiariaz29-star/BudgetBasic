<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid = (int)$user['id'];
$m = $_GET['month'] ?? date('Y-m');

$incomeM = $pdo->prepare("SELECT COALESCE(SUM(amount),0) t FROM income WHERE user_id=? AND DATE_FORMAT(date,'%Y-%m')=?");
$incomeM->execute([$uid,$m]); $mi = (float)$incomeM->fetchColumn();
$expM = $pdo->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE user_id=? AND DATE_FORMAT(date,'%Y-%m')=?");
$expM->execute([$uid,$m]); $me = (float)$expM->fetchColumn();
$totI = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=?"); $totI->execute([$uid]); $ti=(float)$totI->fetchColumn();
$totE = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=?"); $totE->execute([$uid]); $te=(float)$totE->fetchColumn();
$totS = $pdo->prepare("SELECT COALESCE(SUM(saved_amount),0) FROM savings_goals WHERE user_id=?"); $totS->execute([$uid]); $ts=(float)$totS->fetchColumn();
$bal = $ti - $te;

$cat = $pdo->prepare("SELECT c.name, c.color, COALESCE(SUM(e.amount),0) total FROM expenses e LEFT JOIN categories c ON c.id=e.category_id WHERE e.user_id=? AND DATE_FORMAT(e.date,'%Y-%m')=? GROUP BY c.name, c.color ORDER BY total DESC");
$cat->execute([$uid,$m]); $byCat = $cat->fetchAll();

$trend = $pdo->prepare("SELECT DATE_FORMAT(date,'%Y-%m') ym, SUM(amount) total FROM expenses WHERE user_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym");
$trend->execute([$uid]); $monthly = $trend->fetchAll();

$recent = $pdo->prepare("(SELECT 'income' k, source label, amount, date FROM income WHERE user_id=?) UNION ALL (SELECT 'expense' k, description label, amount, date FROM expenses WHERE user_id=?) ORDER BY date DESC LIMIT 8");
$recent->execute([$uid,$uid]); $txns = $recent->fetchAll();

$goals = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id=? ORDER BY updated_at DESC LIMIT 3");
$goals->execute([$uid]); $goals = $goals->fetchAll();

$bud = $pdo->prepare("SELECT * FROM budgets WHERE user_id=? AND month=?"); $bud->execute([$uid,$m]); $budget = $bud->fetch();
$split = split_50_30_20($mi > 0 ? $mi : ($budget ? (float)$budget['monthly_income'] : 0));

$page_title = 'Dashboard'; $page_sub = 'Welcome back, ' . $user['name'] . ' — here is your money at a glance.'; $active='dashboard.php';
include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <form class="d-flex gap-2" method="get"><input type="month" name="month" value="<?= e($m) ?>" class="form-control"><button class="btn btn-ghost">View</button></form>
  <div class="ms-auto d-flex gap-2 flex-wrap">
    <a href="income.php?action=add" class="btn btn-ghost"><i class="bi bi-plus-lg"></i> Add Income</a>
    <a href="expenses.php?action=add" class="btn btn-ghost"><i class="bi bi-plus-lg"></i> Add Expense</a>
    <a href="budget.php" class="btn btn-ghost"><i class="bi bi-pie-chart"></i> Create Budget</a>
    <a href="savings.php?action=add" class="btn btn-primary-bb"><i class="bi bi-piggy-bank"></i> Add Goal</a>
  </div>
</div>

<div class="row g-3">
  <?php $cards=[['Monthly Income', $mi,'bi-arrow-down-circle','linear-gradient(135deg,#4f46e5,#7c3aed)'],['Total Expenses ('.$m.')',$me,'bi-arrow-up-circle','linear-gradient(135deg,#ef4444,#f97316)'],['Available Balance',$bal,'bi-wallet2','linear-gradient(135deg,#0ea5e9,#22d3ee)'],['Total Saved',$ts,'bi-piggy-bank-fill','linear-gradient(135deg,#16a34a,#4ade80)']];
  foreach($cards as [$l,$v,$ic,$bg]): ?>
  <div class="col-6 col-xl-3"><div class="bb-card stat-card p-3" style="background:<?= $bg ?>">
    <div class="blob"></div><span class="s-icon"><i class="bi <?= $ic ?>"></i></span>
    <div class="small opacity-75 mt-2"><?= e($l) ?></div><h3 class="mb-0"><?= money($v) ?></h3>
  </div></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-7"><div class="bb-card p-4 h-100">
    <div class="d-flex justify-content-between"><h5 class="mb-0">Monthly spending trend</h5><a href="reports.php" class="small fw-semibold">Full reports →</a></div>
    <?php if(!$monthly): ?><div class="empty mt-3"><div class="big">📊</div><p class="mb-1 fw-semibold">No spending data yet</p><p class="text-muted small">Add expenses to unlock charts.</p><a href="expenses.php?action=add" class="btn btn-primary-bb btn-sm">+ Add Expense</a></div>
    <?php else: ?><canvas id="trendChart" height="140"></canvas><?php endif; ?>
  </div></div>
  <div class="col-lg-5"><div class="bb-card p-4 h-100">
    <div class="d-flex justify-content-between"><h5 class="mb-0">Expenses by category</h5><a href="expenses.php" class="small fw-semibold">Manage →</a></div>
    <?php if(!$byCat): ?><div class="empty mt-3"><div class="big">🧾</div><p class="mb-1 fw-semibold">No expenses this month</p><p class="text-muted small">Start tracking your spending today.</p></div>
    <?php else: ?><canvas id="catChart" height="180"></canvas><?php endif; ?>
  </div></div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6"><div class="bb-card p-4 h-100">
    <div class="d-flex justify-content-between align-items-center"><h5 class="mb-0">Recent transactions</h5><a href="expenses.php" class="small fw-semibold">View all →</a></div>
    <?php if(!$txns): ?><div class="empty mt-3"><div class="big">💳</div><p class="mb-0 fw-semibold">No transactions yet</p></div>
    <?php else: ?><div class="table-responsive mt-2"><table class="table table-sm align-middle"><tbody>
      <?php foreach($txns as $t): ?>
      <tr><td><span class="badge <?= $t['k']==='income'?'text-bg-success':'text-bg-danger' ?>"><?= $t['k']==='income'?'Income':'Expense' ?></span></td>
      <td><?= e($t['label']) ?><div class="small text-muted"><?= e($t['date']) ?></div></td>
      <td class="text-end fw-semibold"><?= ($t['k']==='income'?'+':'−') . money($t['amount']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div></div>
  <div class="col-lg-3"><div class="bb-card p-4 h-100">
    <h5>Budget · <?= e($m) ?></h5>
    <?php if($budget): $spent = $me; $lim=(float)$budget['monthly_income']; $pc=$lim>0?min(100,round($spent/$lim*100)):0; ?>
      <div class="d-flex justify-content-between small"><span>Spent <?= money($spent) ?> of <?= money($lim) ?></span><strong><?= $pc ?>%</strong></div>
      <div class="progress progress-bb my-2"><div class="progress-bar" style="width:<?= $pc ?>%"></div></div>
      <div class="small text-muted">Needs <?= money($budget['needs_budget']) ?> · Wants <?= money($budget['wants_budget']) ?> · Save <?= money($budget['savings_target']) ?></div>
      <a href="budget.php" class="btn btn-ghost btn-sm w-100 mt-2">Manage budget</a>
    <?php else: ?>
      <p class="text-muted small">Suggested 50/30/20 on <?= money($split['needs']+$split['wants']+$split['savings']) ?>: Needs <?= money($split['needs']) ?>, Wants <?= money($split['wants']) ?>, Save <?= money($split['savings']) ?>.</p>
      <a href="budget.php" class="btn btn-primary-bb btn-sm w-100">Create budget</a>
    <?php endif; ?>
  </div></div>
  <div class="col-lg-3"><div class="bb-card p-4 h-100">
    <div class="d-flex justify-content-between"><h5 class="mb-0">Savings goals</h5><a href="savings.php" class="small fw-semibold">All →</a></div>
    <?php if(!$goals): ?><div class="empty mt-3"><div class="big">🎯</div><p class="small mb-2">No goals yet.</p><a href="savings.php?action=add" class="btn btn-primary-bb btn-sm">+ Add goal</a></div>
    <?php else: foreach($goals as $g): $p=$g['target_amount']>0?min(100,round($g['saved_amount']/$g['target_amount']*100)):0; ?>
      <div class="mt-2"><div class="d-flex justify-content-between small"><strong><?= e($g['goal_name']) ?></strong><span><?= $p ?>%</span></div>
      <div class="progress progress-bb"><div class="progress-bar" style="width:<?= $p ?>%"></div></div>
      <div class="small text-muted"><?= money($g['saved_amount']) ?> / <?= money($g['target_amount']) ?></div></div>
    <?php endforeach; endif; ?>
  </div></div>
</div>

<?php
$extra_js = '<script>
'.($monthly ? 'new Chart(document.getElementById("trendChart"),{type:"bar",data:{labels:'.json_encode(array_column($monthly,'ym')).',datasets:[{label:"Expenses",data:'.json_encode(array_map('floatval',array_column($monthly,'total'))).',backgroundColor:"#4f46e5",borderRadius:8}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});' : '').'
'.($byCat ? 'new Chart(document.getElementById("catChart"),{type:"doughnut",data:{labels:'.json_encode(array_column($byCat,'name')).',datasets:[{data:'.json_encode(array_map('floatval',array_column($byCat,'total'))).',backgroundColor:'.json_encode(array_map(fn($r)=>$r['color']?:'#6366f1',$byCat)).'}]},options:{plugins:{legend:{position:"bottom"}}}});' : '').'
</script>';
include __DIR__ . '/includes/footer.php';
