<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
require __DIR__ . '/config/database.php';
$uid=(int)$user['id'];
$s=$pdo->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$uid]); $me=$s->fetch();
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $form=$_POST['form']??'';
    if($form==='profile'){
        $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
        if(strlen($name)<2)$errors[]='Name too short.';
        elseif(!valid_email($email))$errors[]='Invalid email.';
        else{
            $c=$pdo->prepare("SELECT id FROM users WHERE email=? AND id<>?"); $c->execute([$email,$uid]);
            if($c->fetch())$errors[]='Email already in use.';
            else{ $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?")->execute([$name,$email,$uid]);
                $_SESSION['user']['name']=$name; $_SESSION['user']['email']=$email;
                flash('success','Profile updated.'); redirect('profile.php'); }
        }
    } elseif($form==='password'){
        $cur=$_POST['current']??''; $new=$_POST['new']??''; $conf=$_POST['confirm']??'';
        if(!password_verify($cur,$me['password']))$errors[]='Current password is incorrect.';
        elseif(strlen($new)<8)$errors[]='New password must be at least 8 characters.';
        elseif($new!==$conf)$errors[]='New passwords do not match.';
        else{ $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$uid]); flash('success','Password changed.'); redirect('profile.php'); }
    }
    if($errors) foreach($errors as $er) flash('danger',$er);
    if($form==='password') redirect('profile.php');
}
$page_title='Profile'; $page_sub='Manage your account.'; $active='profile.php';
include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
<div class="col-lg-6"><div class="bb-card p-4"><h5>Profile information</h5>
<form method="post"><input type="hidden" name="form" value="profile">
<div class="mb-2"><label class="form-label fw-semibold">Name</label><input name="name" class="form-control" value="<?= e($me['name']) ?>" required></div>
<div class="mb-2"><label class="form-label fw-semibold">Email</label><input name="email" type="email" class="form-control" value="<?= e($me['email']) ?>" required></div>
<button class="btn btn-primary-bb">Save changes</button></form>
<hr><div class="small text-muted">Member since <?= e(date('M d, Y',strtotime($me['created_at']))) ?> · Role: <strong><?= e($me['role']) ?></strong></div></div></div>
<div class="col-lg-6"><div class="bb-card p-4"><h5>Change password</h5>
<form method="post"><input type="hidden" name="form" value="password">
<div class="mb-2"><label class="form-label fw-semibold">Current password</label><input name="current" type="password" class="form-control" required></div>
<div class="mb-2"><label class="form-label fw-semibold">New password</label><input name="new" type="password" class="form-control" required minlength="8"></div>
<div class="mb-2"><label class="form-label fw-semibold">Confirm new</label><input name="confirm" type="password" class="form-control" required></div>
<button class="btn btn-ghost">Update password</button></form></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
