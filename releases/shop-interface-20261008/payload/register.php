<?php
require __DIR__.'/auth.php';
require __DIR__.'/branding.php';
require __DIR__.'/accounts.php';
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
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create a retail account — NextGen</title><link rel="stylesheet" href="./assets/site.css"><link rel="stylesheet" href="./assets/shop-interface.css?v=1"></head><body><header class="topbar"><div class="wrap nav"><a class="brand" href="/"><img src="<?= brandEscape('logoUrl') ?>" alt="<?= brandEscape('storeName') ?>" class="brand-logo"><small>TECHNOLOGY · PNG</small></a><nav class="nav-links" aria-label="Main navigation"><a href="/">Home</a><a href="/b2c-preview/#catalogue">Shop</a><a href="https://store.nextgenpng.net/">Business store</a><?php if (retailSignedIn()): ?><button class="motion-button" id="cart-open" type="button">Basket <span id="cart-count">0</span></button><form method="post" action="/b2c-preview/logout.php"><input type="hidden" name="csrf" value="<?= retailEscape($_SESSION['csrf']) ?>"><button class="pill" type="submit">Sign out</button></form><?php else: ?><a class="pill" href="/b2c-preview/login.php">Sign in</a><a href="/b2c-preview/register.php">Create account</a><?php endif; ?></nav></div></header><main class="auth-shell"><section class="registration"><h1>Create your account</h1><p>View product details, search the catalogue and save a test basket.</p><?php if ($error): ?><p class="error" role="alert"><?= retailEscape($error) ?></p><?php endif; ?><form method="post" action="./register.php"><input type="hidden" name="csrf" value="<?= retailEscape($_SESSION['csrf']) ?>"><div class="field"><label for="name">Full name</label><input id="name" name="name" autocomplete="name" maxlength="120" required></div><div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" maxlength="190" required></div><div class="field"><label for="password">Password · at least 12 characters</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></div><div class="field"><label for="confirmation">Confirm password</label><input id="confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required></div><label class="terms"><input type="checkbox" name="terms" value="yes" required> I agree to the <a href="https://store.nextgenpng.net/terms">terms</a> and <a href="https://store.nextgenpng.net/privacy">privacy policy</a>.</label><button class="button" type="submit">Create account</button></form><p class="auth-help">Retail and business accounts are separate. Payments and ordering are not enabled yet.</p><a class="text-link" href="./">Back to products</a></section></main></body></html>
