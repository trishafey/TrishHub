<?php
// $viewFile comes from BaseController::view
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
            <a href="/repos">Repositories</a> |
            <a href="/login">Login</a>
        </nav>
    </header>

    <main>
        <?php if (isset($viewFile) && file_exists($viewFile)) { include $viewFile; } ?>
    </main>
</body>
</html>
