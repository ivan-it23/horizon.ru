<?php
namespace App\Controller;

class BaseController
{
    /**
     * Рендерит шаблон из папки views/<view>.php с переданными данными
     * @param string $view - путь к виду относительно папки views без расширения
     * @param array $data - данные для вида
     */
    protected function render(string $view, array $data = []): void
    {
        // Извлекаем переменные для вида
        extract($data);
        // Путь к файлу вида
        $viewFile = __DIR__ . '/../../views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            http_response_code(500);
            echo "View '{${view}}' not found";
        }
    }
} 