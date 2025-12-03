<h2>Repositories</h2>

<p><a href="/repos/new">Create new repository</a></p>

<?php if (empty($repos)): ?>
    <p>No repositories yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($repos as $repo): ?>
            <li>
                <a href="/repos/<?php echo htmlspecialchars($repo['slug'] ?? '', ENT_QUOTES); ?>">
                    <?php echo htmlspecialchars($repo['name'] ?? '', ENT_QUOTES); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
