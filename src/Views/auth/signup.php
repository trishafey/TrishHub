<?php
$errors = $errors ?? [];
$old = $old ?? [
    'email'        => '',
    'username'     => '',
    'display_name' => '',
];
?>

<h2>Sign up</h2>

<?php if (!empty($errors)): ?>
    <ul class="error-list">
        <?php foreach ($errors as $message): ?>
            <li><?php echo htmlspecialchars($message, ENT_QUOTES); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="/signup">
    <div>
        <label>Email</label>
        <input
            type="email"
            name="email"
            required
            value="<?php echo htmlspecialchars($old['email'] ?? '', ENT_QUOTES); ?>"
        >
    </div>
    <div>
        <label>Username (optional)</label>
        <input
            type="text"
            name="username"
            value="<?php echo htmlspecialchars($old['username'] ?? '', ENT_QUOTES); ?>"
        >
    </div>
    <div>
        <label>Display name (optional)</label>
        <input
            type="text"
            name="display_name"
            value="<?php echo htmlspecialchars($old['display_name'] ?? '', ENT_QUOTES); ?>"
        >
    </div>
    <div>
        <label>Password</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <label>Confirm password</label>
        <input type="password" name="password_confirmation" required>
    </div>
    <button type="submit">Create account</button>
</form>
