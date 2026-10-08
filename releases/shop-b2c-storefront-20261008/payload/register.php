<?php
require __DIR__.'/auth.php';
require __DIR__.'/branding.php';
require_once __DIR__.'/accounts.php';
if (retailSignedIn()) { header('Location: ./'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? ''; $email = $_POST['email'] ?? ''; $password = $_POST['password'] ?? ''; $confirmation = $_POST['password_confirmation'] ?? '';
    if (!retailCsrfValid()) { http_response_code(419); $error = 'Refresh the page and try again.'; }
    elseif (!is_string($name) || strlen(trim($name)) < 2 || strlen($name) > 120 || !is_string($email) || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) || strlen($password) < 12 || strlen($password) > 72 || $password !== $confirmation || ($_POST['terms'] ?? '') !== 'yes') {
        http_response_code(422); $error = 'Enter your name, a valid email, matching passwords of 12–72 characters, and accept the terms.';
    } else {
        $root = getenv('NEXTGEN_RETAIL_PRIVATE_ROOT') ?: '/home/nextgenpng';
        $directory = $root.'/.b2c-auth-state';
        if (!is_dir($directory)) @mkdir($directory, 0700, true);
        $lock = @fopen($directory.'/register-'.hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown').'.json','c+');
        if (!$lock || !flock($lock, LOCK_EX)) { http_response_code(503); $error = 'Account setup is temporarily unavailable.'; }
        else {
            chmod($directory.'/register-'.hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown').'.json',0600);
            $state = json_decode(stream_get_contents($lock), true) ?: ['since'=>time(),'attempts'=>0];
            if (time()-(int)$state['since'] >= 3600) $state = ['since'=>time(),'attempts'=>0];
            if ($state['attempts'] >= 5) { http_response_code(429); header('Retry-After: 3600'); $error = 'Please try again in an hour.'; }
            else {
                $state['attempts']++;
                try {
                    $db = retailAccountsDb();
                    $query = $db->prepare('INSERT INTO nextgen_retail_accounts (name,email,password_hash,created_at) VALUES (?,?,?,?)');
                    $query->execute([trim($name),strtolower(trim($email)),password_hash($password,PASSWORD_BCRYPT,['cost'=>12]),gmdate('c')]);
                    startRetailAccountSession(['id'=>(int)$db->lastInsertId(),'name'=>trim($name)]);
                } catch (PDOException $failure) {
                    if (in_array((string)$failure->getCode(), ['23000','23505'], true)) { http_response_code(422); $error = 'Could not create this account. If you already have one, sign in.'; }
                    else { http_response_code(503); $error = 'Account setup is temporarily unavailable.'; }
                } catch (Throwable $failure) { http_response_code(503); $error = 'Account setup is temporarily unavailable.'; }
            }
            rewind($lock); ftruncate($lock,0); fwrite($lock,json_encode($state)); fflush($lock); flock($lock,LOCK_UN); fclose($lock);
            if (!$error) { header('Location: ./',true,303); exit; }
        }
    }
}
?>
<!doctype html><html lang="en"><head><title>Create your account | NextGen B2C</title><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="./assets/site.css"><link rel="stylesheet" href="./assets/product-studio.css"><link rel="stylesheet" href="./assets/b2c-storefront.css?v=1"><script defer src="./assets/b2c-shell.js"></script></head><body class="studio-store premium-ui"><?php require __DIR__.'/header.php'; ?><main class="premium-auth"><section class="auth-layout"><aside class="auth-brand"><p class="premium-eyebrow">NEXTGEN / B2C ACCOUNTS</p><h2>Your everyday.<br>Better connected.</h2><p>Discover technology, printers and supplies. Your dedicated retail store.</p><div class="commerce-scene" aria-hidden="true">
    <div class="scene-orbit"></div>
    <div class="scene-plinth"></div>
    <div class="scene-screen"><span class="scene-brand">NEXTGEN <small>B2C</small></span><div class="scene-screen-grid"><span></span><span></span><span></span></div><div class="scene-chart"><i></i><i></i><i></i><i></i><i></i></div><span class="scene-caption">Technology for your everyday</span></div>
    <div class="scene-note"><span class="scene-check">✓</span><div>Your everyday essentials<small>Browse · Discover · Shop</small></div></div>
    <div class="scene-chip"><span>NG</span><small>Built for everyday</small></div>
</div>
<div class="auth-promise"><span>01 &nbsp; Discover your next upgrade</span><span>02 &nbsp; Browse the retail catalogue</span><span>03 &nbsp; Keep your account secure</span></div></aside><div class="auth-form-panel"><img class="auth-logo" src="<?= brandEscape('logoUrl') ?>" alt="<?= brandEscape('storeName') ?>"><h1>Create your account</h1><p class="auth-intro">Create your B2C customer account.</p><?php if ($error): ?><p class="error" role="alert"><?= retailEscape($error) ?></p><?php endif; ?><form method="post" action="./register.php"><input type="hidden" name="csrf" value="<?= retailEscape($_SESSION['csrf']) ?>"><div class="field"><label for="name">Full name</label><input id="name" name="name" autocomplete="name" maxlength="120" required></div><div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" maxlength="190" required></div><div class="field"><label for="password">Password · at least 12 characters</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></div><div class="field"><label for="confirmation">Confirm password</label><input id="confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></div><label class="terms"><input type="checkbox" name="terms" value="yes" required> I agree to the <a href="./page.php?page=terms">terms</a> and <a href="./page.php?page=privacy">privacy policy</a>.</label><button class="button" type="submit">Create account</button></form><p>Already registered? <a href="./login.php">Sign in</a></p><a href="./">Back to products →</a></div></section></main><?php require __DIR__.'/footer.php'; ?></body></html>