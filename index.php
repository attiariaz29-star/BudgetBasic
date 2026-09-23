<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
if (current_user()) { header('Location: dashboard.php'); exit; }
$page_title = 'Budget smarter, save faster';
require __DIR__ . '/config/database.php';
$tips = $pdo->query("SELECT * FROM tips ORDER BY created_at DESC LIMIT 3")->fetchAll();
$arts = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 3")->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<header class="hero">
  <div class="container py-5">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <span class="hero-badge"><i class="bi bi-stars"></i> Fintech-grade personal budgeting</span>
        <h1 class="mt-3">Take control of your money with <span class="grad">BudgetBasics</span></h1>
        <p class="lead text-muted">Track income & expenses, plan with the 50/30/20 rule, hit savings goals, and learn finance — all from one beautiful dashboard.</p>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <a href="register.php" class="btn btn-primary-bb btn-lg">Start Budgeting <i class="bi bi-arrow-right"></i></a>
          <a href="#rule" class="btn btn-ghost btn-lg">See how it works</a>
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
          <div class="stat-chip"><strong>50/30/20</strong><div class="small text-muted">smart budgeting</div></div>
          <div class="stat-chip"><strong>Charts</strong><div class="small text-muted">real spending insights</div></div>
          <div class="stat-chip"><strong>Goals</strong><div class="small text-muted">save with progress</div></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>Monthly overview</strong><span class="badge text-bg-success">Live demo</span>
          </div>
          <div class="row g-2 text-center">
            <div class="col-4"><div class="stat-chip"><div class="small text-muted">Income</div><strong>Rs. 50,000</strong></div></div>
            <div class="col-4"><div class="stat-chip"><div class="small text-muted">Expenses</div><strong>Rs. 27,000</strong></div></div>
            <div class="col-4"><div class="stat-chip"><div class="small text-muted">Saved</div><strong>Rs. 10,000</strong></div></div>
          </div>
          <div class="mt-3"><canvas id="demoChart" height="140"></canvas></div>
          <a href="register.php" class="btn btn-primary-bb w-100 mt-3">Create free account</a>
        </div>
      </div>
    </div>
  </div>
</header>

<section class="section-pad" id="features">
  <div class="container">
    <div class="eyebrow">Why BudgetBasics</div>
    <h2>Everything you need to budget with confidence</h2>
    <p class="text-muted">Built like a real SaaS product — not a classroom CRUD demo.</p>
    <div class="row g-3 mt-2">
      <?php $feats = [
        ['bi-arrow-down-up','Income tracking','#4f46e5','Log every source with dates, search and history.'],
        ['bi-receipt','Expense tracking','#ef4444','Categories, filters, sorting and full history.'],
        ['bi-pie-chart-fill','50/30/20 budgets','#0ea5e9','Auto-split income into Needs / Wants / Savings.'],
        ['bi-piggy-bank-fill','Savings goals','#16a34a','Named goals with progress bars and target dates.'],
        ['bi-bar-chart-fill','Reports & charts','#7c3aed','Category, monthly and income-vs-expense analytics.'],
        ['bi-mortarboard-fill','Financial education','#d97706','Articles and tips that teach real money skills.'],
      ]; foreach ($feats as [$ic,$t,$c,$d]): ?>
      <div class="col-md-6 col-lg-4"><div class="bb-card hover p-4 h-100">
        <span class="icon-tile mb-3" style="background:<?= $c ?>"><i class="bi <?= $ic ?>"></i></span>
        <h5><?= $t ?></h5><p class="text-muted mb-0"><?= $d ?></p>
      </div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-pad bg-white border-top border-bottom" id="rule">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <div class="eyebrow">The 50/30/20 rule</div>
        <h2>A simple split that works on any income</h2>
        <p class="text-muted">Enter any income in the app and BudgetBasics calculates your ideal allocation instantly.</p>
        <div class="bb-card p-4">
          <label class="form-label fw-semibold">Try it: monthly income (Rs.)</label>
          <input id="ruleIncome" type="number" class="form-control" value="50000" min="1">
          <div class="rule-bar mt-3"><span style="width:50%;background:#4f46e5"></span><span style="width:30%;background:#06b6d4"></span><span style="width:20%;background:#16a34a"></span></div>
          <div class="row text-center mt-3">
            <div class="col-4"><div class="small text-muted">Needs 50%</div><strong id="rNeeds">Rs. 25,000</strong></div>
            <div class="col-4"><div class="small text-muted">Wants 30%</div><strong id="rWants">Rs. 15,000</strong></div>
            <div class="col-4"><div class="small text-muted">Savings 20%</div><strong id="rSave">Rs. 10,000</strong></div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="bb-card p-4">
          <h5><i class="bi bi-receipt-cutoff text-primary"></i> Expense tracking preview</h5>
          <table class="table table-sm mt-2"><thead><tr><th>Category</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
          <tbody><tr><td>Housing</td><td>Rent</td><td class="text-end">Rs. 15,000</td></tr>
          <tr><td>Food</td><td>Groceries</td><td class="text-end">Rs. 6,000</td></tr>
          <tr><td>Transport</td><td>Fuel</td><td class="text-end">Rs. 3,500</td></tr></tbody></table>
          <h5 class="mt-3"><i class="bi bi-piggy-bank-fill text-success"></i> Savings goal preview</h5>
          <div class="d-flex justify-content-between small"><span>New Laptop — Rs. 50,000 / Rs. 150,000</span><strong>33%</strong></div>
          <div class="progress progress-bb mt-1"><div class="progress-bar" style="width:33%"></div></div>
          <a href="register.php" class="btn btn-primary-bb w-100 mt-3">Start tracking free</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-pad"><div class="container">
  <div class="eyebrow">Learn money skills</div><h2>Financial education built in</h2>
  <div class="row g-3 mt-2">
    <?php foreach ($arts as $a): ?>
    <div class="col-md-4"><div class="bb-card hover p-4 h-100">
      <span class="icon-tile mb-2" style="background:#4f46e5"><i class="bi <?= e($a['icon']) ?>"></i></span>
      <div class="small text-muted"><?= e($a['category']) ?> · <?= (int)$a['read_minutes'] ?> min read</div>
      <h5 class="mt-1"><?= e($a['title']) ?></h5><p class="text-muted small"><?= e($a['excerpt'] ?? '') ?></p>
      <a href="learning.php" class="fw-semibold">Read article →</a>
    </div></div>
    <?php endforeach; ?>
    <?php if (!$arts): ?><p class="text-muted">Articles coming soon.</p><?php endif; ?>
  </div>
  <div class="bb-card p-4 mt-4 d-flex flex-column flex-md-row align-items-md-center gap-3">
    <div><h4 class="mb-1">Ready to master your money?</h4><p class="text-muted mb-0">Join BudgetBasics free — track your first expense in under a minute.</p></div>
    <a href="register.php" class="btn btn-primary-bb btn-lg ms-md-auto">Start Budgeting</a>
  </div>
</div></section>

<?php
$extra_js = '<script>
const ri=document.getElementById("ruleIncome");
function updRule(){const s=calc503020(ri.value);
 document.getElementById("rNeeds").textContent="Rs. "+Number(s.needs).toLocaleString();
 document.getElementById("rWants").textContent="Rs. "+Number(s.wants).toLocaleString();
 document.getElementById("rSave").textContent="Rs. "+Number(s.savings).toLocaleString();}
ri.addEventListener("input",updRule);updRule();
new Chart(document.getElementById("demoChart"),{type:"bar",data:{labels:["Income","Expenses","Savings"],datasets:[{data:[50000,27000,10000],backgroundColor:["#4f46e5","#ef4444","#16a34a"],borderRadius:10}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>';
include __DIR__ . '/includes/footer.php';
