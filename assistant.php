<?php
// Budget Assistant — rule-based v1, LLM-ready.
// To integrate an AI API later: replace assistant_reply() in includes/functions.php
// with an HTTP POST to your provider, keeping the same signature.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid=(int)$user['id'];
$i=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=?"); $i->execute([$uid]); $inc=(float)$i->fetchColumn();
$e=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=?"); $e->execute([$uid]); $exp=(float)$e->fetchColumn();
$answer='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $q=trim($_POST['q']??'');
    if($q==='') flash('danger','Please type a question.');
    else $answer=assistant_reply($q,['income'=>$inc,'expenses'=>$exp]);
}
$page_title='Budget Assistant'; $page_sub='Rule-based helper — swap in any AI API later.'; $active='assistant.php';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-7"><div class="bb-card p-4">
<div class="d-flex gap-2 small mb-2 flex-wrap"><span class="badge text-bg-primary">Income <?= money($inc) ?></span><span class="badge text-bg-danger">Expenses <?= money($exp) ?></span><span class="badge text-bg-success">Balance <?= money($inc-$exp) ?></span></div>
<div class="chat-box mb-2" id="chat">
<div class="chat-bubble bot">👋 Hi <?= e($user['name']) ?>! Ask me about 50/30/20, saving more, or overspending. Try <em>“Split 60000 by 50/30/20”</em>.</div>
<?php if($answer): ?><div class="chat-bubble me"><?= e($_POST['q']) ?></div><div class="chat-bubble bot"><?= e($answer) ?></div><?php endif; ?>
</div>
<form method="post" class="d-flex gap-2"><input name="q" class="form-control" placeholder="Ask about budgeting…" value="<?= e($_POST['q']??'') ?>" required><button class="btn btn-primary-bb">Ask</button></form>
<div class="small text-muted mt-2">Architecture note: <code>assistant_reply($question,$ctx)</code> in <code>includes/functions.php</code> is the single seam — replace its body with an LLM API call. Client mirror lives in <code>assets/js/main.js</code>.</div>
</div></div></div>
<?php $extra_js='<script>document.getElementById("chat").scrollTop=99999;</script>'; include __DIR__ . '/includes/footer.php'; ?>
