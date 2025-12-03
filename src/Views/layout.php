<?php
// $viewFile comes from BaseController::view
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TrishHub</title>
</head>
<body>
    <header>
        <h1>TrishHub</h1>
        <nav>
            <a href="/repos">Repositories</a>
            <?php if ($isLoggedIn): ?>
                |
                <a href="/logout">Logout</a>
            <?php else: ?>
                |
                <a href="/signup">Sign up</a>
                |
                <a href="/login">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?php if (isset($viewFile) && file_exists($viewFile)) { include $viewFile; } ?>
    </main>
</body>
</html>
