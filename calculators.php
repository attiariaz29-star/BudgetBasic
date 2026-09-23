<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$page_title='Calculators'; $page_sub='Interactive 50/30/20, expense and savings math.'; $active='calculators.php';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
<div class="col-lg-4"><div class="bb-card p-4 h-100"><h5><i class="bi bi-pie-chart-fill text-primary"></i> 50/30/20 calculator</h5>
<label class="form-label fw-semibold mt-2">Monthly income (Rs.)</label><input id="cInc" type="number" class="form-control" value="50000" min="0">
<div class="rule-bar mt-3"><span style="width:50%;background:#4f46e5"></span><span style="width:30%;background:#06b6d4"></span><span style="width:20%;background:#16a34a"></span></div>
<div class="mt-2"><div>Needs (50%): <strong id="cN">—</strong></div><div>Wants (30%): <strong id="cW">—</strong></div><div>Savings (20%): <strong id="cS">—</strong></div></div></div></div>
<div class="col-lg-4"><div class="bb-card p-4 h-100"><h5><i class="bi bi-receipt text-danger"></i> Expense calculator</h5>
<label class="form-label fw-semibold mt-2">Income (Rs.)</label><input id="eInc" type="number" class="form-control" value="50000">
<label class="form-label fw-semibold mt-2">Total expenses (Rs.)</label><input id="eExp" type="number" class="form-control" value="27000">
<div class="mt-2"><div>Total expenses: <strong id="eT">—</strong></div><div>Remaining: <strong id="eR">—</strong></div></div></div></div>
<div class="col-lg-4"><div class="bb-card p-4 h-100"><h5><i class="bi bi-trophy text-success"></i> Savings goal calculator</h5>
<label class="form-label fw-semibold mt-2">Target (Rs.)</label><input id="gT" type="number" class="form-control" value="150000">
<label class="form-label fw-semibold mt-2">Saved (Rs.)</label><input id="gS" type="number" class="form-control" value="50000">
<div class="mt-2"><div>Progress: <strong id="gP">—</strong></div><div>Remaining: <strong id="gR">—</strong></div>
<div class="progress progress-bb mt-2"><div class="progress-bar" id="gBar" style="width:0%"></div></div></div></div></div>
</div>
<?php
$extra_js='<script>
function fmt(n){return "Rs. "+Number(n||0).toLocaleString();}
function u1(){const s=calc503020(document.getElementById("cInc").value);cN.textContent=fmt(s.needs);cW.textContent=fmt(s.wants);cS.textContent=fmt(s.savings);}
function u2(){const i=+eInc.value||0,e=+eExp.value||0;eT.textContent=fmt(e);eR.textContent=fmt(i-e);}
function u3(){const t=+gT.value||0,s=+gS.value||0,p=t>0?Math.min(100,(s/t*100)):0;gP.textContent=p.toFixed(1)+"%";gR.textContent=fmt(Math.max(0,t-s));gBar.style.width=Math.min(100,p)+"%";}
[cInc].forEach(x=>x.addEventListener("input",u1));[eInc,eExp].forEach(x=>x.addEventListener("input",u2));[gT,gS].forEach(x=>x.addEventListener("input",u3));u1();u2();u3();
</script>';
include __DIR__ . '/includes/footer.php';
