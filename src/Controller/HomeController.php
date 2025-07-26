<?php
namespace App\Controller;

use App\Controller\BaseController;

class HomeController extends BaseController
{
    public function index(): void
    {
        // Rate limiting для главной страницы (100 запросов в минуту)
        if (!$this->checkRateLimit($this->getClientIP(), 'home_index', 100, 60)) {
            $this->handleError('Слишком много запросов к главной странице', [], 429);
        }

        // Логируем доступ к главной странице
        $this->logSecurityEvent('home_page_accessed', [
            'user_id' => $_SESSION['user']['id'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        echo '<h1>Добро пожаловать в Front Controller</h1>';
    }
} 