<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid = (int)$user['id'];
$month = $_GET['month'] ?? current_month();

$stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id=? AND month=?");
$stmt->execute([$uid,$month]); $b = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mm = $_POST['month'] ?? current_month();
    $inc = $_POST['monthly_income'] ?? 0;
    $auto = isset($_POST['auto_split']);
    if (!preg_match('/^\d{4}-\d{2}$/', $mm)) flash('danger','Invalid month.');
    elseif (!is_numeric($inc) || (float)$inc < 0) flash('danger','Please enter a valid income.');
    else {
        $inc = (float)$inc;
        if ($auto || empty($_POST['needs_budget'])) {
            $s = split_50_30_20($inc);
            $needs=$s['needs']; $wants=$s['wants']; $save=$s['savings'];
        } else {
            $needs=(float)$_POST['needs_budget']; $wants=(float)$_POST['wants_budget']; $save=(float)$_POST['savings_target'];
        }
        $stmt=$pdo->prepare("INSERT INTO budgets (user_id, month, monthly_income, needs_budget, wants_budget, savings_target) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE monthly_income=VALUES(monthly_income), needs_budget=VALUES(needs_budget), wants_budget=VALUES(wants_budget), savings_target=VALUES(savings_target)");
        $stmt->execute([$uid,$mm,$inc,$needs,$wants,$save]);
        flash('success','Budget saved for '.$mm.'.');
        redirect('budget.php?month='.$mm);
    }
}

// actuals for selected month
$e=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND DATE_FORMAT(date,'%Y-%m')=?"); $e->execute([$uid,$month]); $spent=(float)$e->fetchColumn();
$i=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND DATE_FORMAT(date,'%Y-%m')=?"); $i->execute([$uid,$month]); $got=(float)$i->fetchColumn();

$page_title='Budget'; $page_sub='Plan monthly spending with 50/30/20.'; $active='budget.php';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
<div class="col-lg-5"><div class="bb-card p-4">
<h5>Monthly budget · <?= e($month) ?></h5>
<form method="get" class="d-flex gap-2 mb-3"><input type="month" name="month" value="<?= e($month) ?>" class="form-control"><button class="btn btn-ghost">View</button></form>
<form method="post">
<input type="hidden" name="month" value="<?= e($month) ?>">
<div class="mb-2"><label class="form-label fw-semibold">Monthly income (Rs.)</label><input name="monthly_income" type="number" step="0.01" min="0" class="form-control" required value="<?= e((string)($b['monthly_income'] ?? $got)) ?>"></div>
<div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="auto_split" id="auto" checked><label class="form-check-label" for="auto">Auto-split 50/30/20</label></div>
<div class="row g-2" id="manualBox" style="display:none">
<div class="col-4"><label class="form-label small">Needs</label><input name="needs_budget" type="number" step="0.01" class="form-control" value="<?= e((string)($b['needs_budget']??'')) ?>"></div>
<div class="col-4"><label class="form-label small">Wants</label><input name="wants_budget" type="number" step="0.01" class="form-control" value="<?= e((string)($b['wants_budget']??'')) ?>"></div>
<div class="col-4"><label class="form-label small">Savings</label><input name="savings_target" type="number" step="0.01" class="form-control" value="<?= e((string)($b['savings_target']??'')) ?>"></div>
</div>
<div class="bb-card p-3 mt-2 bg-light"><div class="small text-muted">Live 50/30/20 preview</div><div class="fw-semibold" id="splitPrev">—</div></div>
<button class="btn btn-primary-bb w-100 mt-3">Save budget</button>
</form></div></div>
<div class="col-lg-7"><div class="bb-card p-4 h-100">
<h5>Breakdown</h5>
<?php if(!$b): ?><div class="empty mt-2"><div class="big">📊</div><p class="fw-semibold mb-1">No budget for <?= e($month) ?></p><p class="text-muted small">Set your income on the left — we auto-calculate 50/30/20.</p></div>
<?php else:
$lim=(float)$b['monthly_income']; $pc=$lim>0?min(100,round($spent/$lim*100)):0;
$warn = $pc>=100 ? 'Over budget! Freeze wants spending.' : ($pc>=80 ? 'Heads up: 80%+ of budget used.' : 'On track. Keep going!');
$cls = $pc>=100?'danger':($pc>=80?'warning':'success');
?>
<div class="alert alert-<?= $cls ?>"><?= e($warn) ?> Spent <?= money($spent) ?> of <?= money($lim) ?> (<?= $pc ?>%).</div>
<div class="progress progress-bb mb-3"><div class="progress-bar" style="width:<?= $pc ?>%"></div></div>
<div class="rule-bar"><span style="width:50%;background:#4f46e5"></span><span style="width:30%;background:#06b6d4"></span><span style="width:20%;background:#16a34a"></span></div>
<div class="row text-center mt-3">
<div class="col-4"><div class="small text-muted">Needs 50%</div><strong><?= money($b['needs_budget']) ?></strong></div>
<div class="col-4"><div class="small text-muted">Wants 30%</div><strong><?= money($b['wants_budget']) ?></strong></div>
<div class="col-4"><div class="small text-muted">Savings 20%</div><strong><?= money($b['savings_target']) ?></strong></div>
</div>
<canvas id="budChart" height="140" class="mt-3"></canvas>
<?php $extra_js='<script>new Chart(document.getElementById("budChart"),{type:"doughnut",data:{labels:["Needs","Wants","Savings"],datasets:[{data:['.(float)$b['needs_budget'].','.(float)$b['wants_budget'].','.(float)$b['savings_target'].'],backgroundColor:["#4f46e5","#06b6d4","#16a34a"]}]},options:{plugins:{legend:{position:"bottom"}}}});</script>'; ?>
<?php endif; ?>
</div></div>
</div>
<?php
$extra_js = ($extra_js ?? '') . '<script>
const inc=document.querySelector("[name=monthly_income]"),auto=document.getElementById("auto"),mb=document.getElementById("manualBox"),pv=document.getElementById("splitPrev");
function upd(){const s=calc503020(inc.value);pv.textContent="Needs Rs. "+s.needs.toLocaleString()+" · Wants Rs. "+s.wants.toLocaleString()+" · Savings Rs. "+s.savings.toLocaleString();mb.style.display=auto.checked?"none":"flex";}
inc.addEventListener("input",upd);auto.addEventListener("change",upd);upd();</script>';
include __DIR__ . '/includes/footer.php';
