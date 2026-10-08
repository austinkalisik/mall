<?php
require __DIR__.'/auth.php';
require __DIR__.'/branding.php';
require __DIR__.'/accounts.php';
require __DIR__.'/admin-auth.php';
$error = '';
if (b2cAdminSignedIn()) { header('Location: /b2c-admin/#/login'); exit; }
if (retailSignedIn()) { header('Location: ./'); exit; }
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
                if (!empty($valid)) { header('Location: '.($admin ? '/b2c-admin/#/login' : './'), true, 303); exit; }
            }
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>B2C sign in — NextGen</title><link rel="stylesheet" href="./assets/site.css"><link rel="stylesheet" href="./assets/shop-interface.css?v=1"><script defer src="./assets/lottie.min.js"></script><script defer src="./assets/motion.js"></script></head><body>
<header class="topbar"><div class="wrap nav"><a class="brand" href="/"><img src="<?= brandEscape('logoUrl') ?>" alt="<?= brandEscape('storeName') ?>" class="brand-logo"><small>TECHNOLOGY · PNG</small></a><nav class="nav-links" aria-label="Main navigation"><a href="/">Home</a><a href="/b2c-preview/#catalogue">Shop</a><a href="https://store.nextgenpng.net/">Business store</a><?php if (retailSignedIn()): ?><button class="motion-button" id="cart-open" type="button">Basket <span id="cart-count">0</span></button><form method="post" action="/b2c-preview/logout.php"><input type="hidden" name="csrf" value="<?= retailEscape($_SESSION['csrf']) ?>"><button class="pill" type="submit">Sign out</button></form><?php else: ?><a class="pill" href="/b2c-preview/login.php">Sign in</a><a href="/b2c-preview/register.php">Create account</a><?php endif; ?></nav></div></header>
<main class="auth-shell"><section class="auth-panel"><aside class="auth-story"><div><span class="eyebrow">NextGen / Everyday accounts</span><h1>Your everyday.<br>Better connected.</h1><p>A little upgrade. A lot of possibility. Discover technology and essentials for your world.</p></div><div class="scene"><div class="fallback-art" aria-hidden="true">▣</div><div data-lottie="./assets/retail.json" aria-hidden="true"></div></div><div class="auth-promises"><span>01 &nbsp; Discover your next upgrade</span><span>02 &nbsp; Explore the retail catalogue</span><span>03 &nbsp; Keep your access protected</span></div></aside><div class="auth-form"><h2>Welcome back</h2><p class="subtext">Customers and store administrators sign in here.</p>
<?php if ($error): ?><p class="error" role="alert"><?= retailEscape($error) ?></p><?php endif; ?>
<form method="post" action="./login.php"><input type="hidden" name="csrf" value="<?= retailEscape($_SESSION['csrf']) ?>"><div class="field"><label for="username">Email address</label><input id="username" name="username" autocomplete="username" maxlength="190" placeholder="you@example.com" required></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" maxlength="1024" placeholder="Your password" required></div><button class="button" type="submit">Sign in securely</button></form><p class="auth-help">New customer? <a class="text-link" href="./register.php">Create an account</a></p><p class="auth-help"><a class="text-link" href="./">← Continue viewing the homepage</a></p><p class="auth-help">Administrators open the store dashboard. Customers continue to the shop. Business accounts sign in at store.nextgenpng.net.</p><button class="motion-button" data-motion-toggle type="button">Pause animations</button></div></section></main></body></html>
