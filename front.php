<?php
declare(strict_types=1);
// Enable error reporting for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Подключаем кастомный автозагрузчик классов
require __DIR__ . '/autoload.php';

use App\Controller\HomeController;

// Определяем контроллер и действие из GET-параметров
$controllerName = ucfirst($_GET['c'] ?? 'home');
$actionName     = $_GET['a'] ?? 'index';
$class = 'App\\Controller\\' . $controllerName . 'Controller';

if (class_exists($class)) {
    $controller = new $class();
    if (method_exists($controller, $actionName)) {
        $controller->{$actionName}();
    } else {
        http_response_code(404);
        echo "Action '{$actionName}' not found in controller '{$controllerName}'";
    }
} else {
    http_response_code(404);
    echo "Controller '{$controllerName}' not found.";
} 