<?php
/** @var array $results */
/** @var string $migrationsDir */
$applied = $results['applied'] ?? [];
$skipped = $results['skipped'] ?? [];
?>

<h2>Installation / Migrations</h2>

<p>Migration directory: <code><?php echo htmlspecialchars($migrationsDir, ENT_QUOTES); ?></code></p>

<?php if (empty($applied) && empty($skipped)): ?>
    <p>No migration files were found.</p>
<?php else: ?>
    <?php if (!empty($applied)): ?>
        <h3>Applied migrations</h3>
        <ul>
            <?php foreach ($applied as $file): ?>
                <li><?php echo htmlspecialchars($file, ENT_QUOTES); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($skipped)): ?>
        <h3>Skipped (already applied)</h3>
        <ul>
            <?php foreach ($skipped as $file): ?>
                <li><?php echo htmlspecialchars($file, ENT_QUOTES); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<p><a href="/login">Go to login</a></p>
