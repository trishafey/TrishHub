<?php

use Src\Controllers\HomeController;
use Src\Controllers\AuthController;
use Src\Controllers\RepoController;

return [
    ['GET',  '/',                 [HomeController::class, 'index']],
    ['GET',  '/login',            [AuthController::class, 'showLogin']],
    ['POST', '/login',            [AuthController::class, 'login']],
    ['POST', '/logout',           [AuthController::class, 'logout']],

    ['GET',  '/repos',            [RepoController::class, 'index']],
    ['GET',  '/repos/new',        [RepoController::class, 'create']],
    ['POST', '/repos',            [RepoController::class, 'store']],
    ['GET',  '/repos/{name}',     [RepoController::class, 'show']],
    ['GET',  '/repos/{name}/tree',[RepoController::class, 'tree']],
    ['GET',  '/repos/{name}/file',[RepoController::class, 'file']],
    ['GET',  '/repos/{name}/commits',[RepoController::class, 'commits']],
];
