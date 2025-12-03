<?php

declare(strict_types=1);

namespace Src\Controllers;

class HomeController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->redirect('/repos');
    }
}
