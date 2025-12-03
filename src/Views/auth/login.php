<?php
$error = $error ?? null;
$old = $old ?? ['username' => ''];
?>

<h2>Login</h2>

<?php if ($error): ?>
    <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></p>
<?php endif; ?>

<form method="post" action="/login">
    <div>
        <label>Email or username</label>
        <input
            type="text"
            name="username"
            value="<?php echo htmlspecialchars($old['username'] ?? '', ENT_QUOTES); ?>"
        >
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password">
    </div>
    <button type="submit">Sign in</button>
</form>

<p><a href="/signup">Create an account</a></p>
