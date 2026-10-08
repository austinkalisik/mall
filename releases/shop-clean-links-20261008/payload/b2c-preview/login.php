<?php
require __DIR__.'/auth.php';
require __DIR__.'/branding.php';
require_once __DIR__.'/accounts.php';
require __DIR__.'/admin-auth.php';
$error = '';
if (b2cAdminSignedIn()) { header('Location: /b2c-admin/#/login'); exit; }
if (retailSignedIn()) { header('Location: /'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!retailCsrfValid()) {
        http_response_code(419); $error = 'Your session expired. Refresh this page and try again.';
    } else {
        $privateRoot = getenv('NEXTGEN_RETAIL_PRIVATE_ROOT') ?: '/home/nextgenpng';
        $stateDir = $privateRoot.'/.b2c-auth-state';
        if (!is_dir($stateDir) && !@mkdir($stateDir, 0700, true) && !is_dir($stateDir)) {
            http_response_code(503); $error = 'Sign-in is temporarily unavailable.';
        } else {
            // Persistent per-address throttle; never trust client forwarded headers.
            $stateFile = $stateDir.'/'.hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown').'.json';
            $lock = @fopen($stateFile, 'c+');
            if (!$lock || !flock($lock, LOCK_EX)) {
                http_response_code(503); $error = 'Sign-in is temporarily unavailable.';
            } else {
                chmod($stateFile, 0600);
                $state = json_decode(stream_get_contents($lock), true) ?: ['since' => time(), 'attempts' => 0];
                if (time() - (int)$state['since'] >= 900) $state = ['since' => time(), 'attempts' => 0];
                if ((int)$state['attempts'] >= 8) {
                    header('Retry-After: 900'); http_response_code(429); $error = 'Too many attempts. Please try again in 15 minutes.';
                } else {
                    $user = $_POST['username'] ?? '';
                    $password = $_POST['password'] ?? '';
                    $hash = @file_get_contents($privateRoot.'/.b2c-preview-password.hash');
                    $account = null;
                    $admin = null;
                    $valid = is_string($user) && is_string($password) && strlen($password) <= 72 && $hash && password_verify($password, trim($hash)) && hash_equals('b2ctest', $user);
                    if (!$valid && !$error && is_string($user) && is_string($password) && strlen($password) <= 72 && filter_var($user, FILTER_VALIDATE_EMAIL)) {
                        try { $admin = b2cAdminLogin(trim($user), $password); $valid = $admin !== null; }
                        catch (Throwable $failure) { http_response_code(503); $error = 'Sign-in is temporarily unavailable.'; }
                    }
                    if (!$valid && is_string($user) && is_string($password) && strlen($password) <= 72 && filter_var($user, FILTER_VALIDATE_EMAIL)) {
                        try { $account = retailAccountLogin($user, $password); $valid = $account !== null; }
                        catch (Throwable $failure) { http_response_code(503); $error = 'Customer sign-in is temporarily unavailable.'; }
                    }
                    if ($valid) {
                        $state = ['since' => time(), 'attempts' => 0];
                        session_regenerate_id(true);
                        if ($admin) startB2cAdminSession($admin);
                        elseif ($account) startRetailAccountSession($account);
                        else { unset($_SESSION['retail_account_id']); $_SESSION['retail_user'] = 'b2ctest'; }
                        $_SESSION['retail_until'] = time() + 1800;
                        $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    } else {
                        $state['attempts']++;
                        if (!$error) { http_response_code(401); $error = 'Email or password is incorrect.'; }
                    }
                }
                rewind($lock); ftruncate($lock, 0); fwrite($lock, json_encode($state)); fflush($lock); flock($lock, LOCK_UN); fclose($lock);
                if (!empty($valid)) { header('Location: '.($admin ? '/b2c-admin/#/login' : '/'), true, 303); exit; }
            }
        }
    }
}
?>
<!doctype html><html lang="en"><head><title>Sign in | NextGen B2C</title><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/b2c-preview/assets/site.css"><link rel="stylesheet" href="/b2c-preview/assets/product-studio.css"><link rel="stylesheet" href="/b2c-preview/assets/b2c-storefront.css?v=1"><script defer src="/b2c-preview/assets/b2c-shell.js"></script></head><body class="studio-store premium-ui"><?php require __DIR__.'/header.php'; ?><main class="premium-auth"><section class="auth-layout"><aside class="auth-brand"><p class="premium-eyebrow">NEXTGEN / B2C ACCOUNTS</p><h2>Your everyday.<br>Better connected.</h2><p>Discover technology, printers and supplies. Your dedicated retail store.</p><div class="commerce-scene" aria-hidden="true">
    <div class="scene-orbit"></div>
    <div class="scene-plinth"></div>
    <div class="scene-screen"><span class="scene-brand">NEXTGEN <small>B2C</small></span><div class="scene-screen-grid"><span></span><span></span><span></span></div><div class="scene-chart"><i></i><i></i><i></i><i></i><i></i></div><span class="scene-caption">Technology for your everyday</span></div>
    <div class="scene-note"><span class="scene-check">✓</span><div>Your everyday essentials<small>Browse · Discover · Shop</small></div></div>
    <div class="scene-chip"><span>NG</span><small>Built for everyday</small></div>
</div>
<div class="auth-promise"><span>01 &nbsp; Discover your next upgrade</span><span>02 &nbsp; Browse the retail catalogue</span><span>03 &nbsp; Keep your account secure</span></div></aside><div class="auth-form-panel"><img class="auth-logo" src="<?= brandEscape('logoUrl') ?>" alt="<?= brandEscape('storeName') ?>"><h1>Sign in</h1><p class="auth-intro">Customers and store administrators sign in here.</p><?php if ($error): ?><p class="error" role="alert"><?= retailEscape($error) ?></p><?php endif; ?>
<form method="post" action="/login"><input type="hidden" name="csrf" value="<?= retailEscape($_SESSION['csrf']) ?>"><div class="field"><label for="username">Email address</label><input id="username" name="username" autocomplete="username" maxlength="190" placeholder="you@example.com" required></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="1024" placeholder="Your password" required></div><button class="password-toggle" type="button" data-password-toggle aria-controls="password">Show password</button><button class="button" type="submit">Sign in securely</button></form><p>New customer? <a href="/register">Create an account</a></p><a href="/">Back to products →</a></div></section></main><?php require __DIR__.'/footer.php'; ?></body></html>