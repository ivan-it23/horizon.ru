<?php
namespace App\Controller;

use App\Service\SecurityService;

class BaseController
{
    /**
     * Конструктор - устанавливает базовые заголовки безопасности
     */
    public function __construct()
    {
        // Устанавливаем безопасные заголовки для всех контроллеров
        SecurityService::setSecurityHeaders();
    }

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
            echo "View '{$view}' not found";
        }
    }

    /**
     * Проверка авторизации пользователя
     */
    protected function requireAuth(): void
    {
        SecurityService::requireAuth();
    }

    /**
     * Проверка прав администратора
     */
    protected function requireAdmin(): void
    {
        SecurityService::requireAdmin();
    }

    /**
     * Валидация CSRF токена
     */
    protected function validateCSRF(string $token): bool
    {
        return SecurityService::validateCSRFToken($token);
    }

    /**
     * Генерация CSRF токена
     */
    protected function generateCSRF(): string
    {
        return SecurityService::generateCSRFToken();
    }

    /**
     * Безопасная очистка строки
     */
    protected function sanitizeString(string $input, int $maxLength = 255): string
    {
        return SecurityService::sanitizeString($input, $maxLength);
    }

    /**
     * Валидация целого числа
     */
    protected function validateInt($input, ?int $min = null, ?int $max = null)
    {
        return SecurityService::validateInt($input, $min, $max);
    }

    /**
     * Rate limiting
     */
    protected function checkRateLimit(string $identifier, string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        return SecurityService::checkRateLimit($identifier, $action, $maxAttempts, $timeWindow);
    }

    /**
     * Логирование событий безопасности
     */
    protected function logSecurityEvent(string $event, array $data = []): void
    {
        SecurityService::logSecurityEvent($event, $data);
    }

    /**
     * Получение IP адреса клиента
     */
    protected function getClientIP(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Безопасный JSON ответ
     */
    protected function jsonResponse(array $data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Обработка ошибок с логированием
     */
    protected function handleError(string $message, array $context = [], int $httpCode = 500): void
    {
        $this->logSecurityEvent('controller_error', array_merge($context, [
            'message' => $message,
            'controller' => get_class($this),
            'method' => debug_backtrace()[1]['function'] ?? 'unknown'
        ]));

        if ($httpCode >= 400) {
            http_response_code($httpCode);
        }

        // В зависимости от типа запроса возвращаем JSON или HTML
        // Проверяем AJAX запрос по заголовкам или Content-Type
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
                  (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                  (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isAjax) {
            $this->jsonResponse(['error' => $message], $httpCode);
        } else {
            echo htmlspecialchars($message);
            exit;
        }
    }

    /**
     * Безопасная отправка файла для скачивания
     */
    protected function sendFileDownload(string $fileName, string $fileData, string $mimeType = 'application/octet-stream'): void
    {
        // Безопасное имя файла
        $safeFileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($fileName));
        
        // Очищаем все буферы вывода безопасно
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Устанавливаем заголовки для скачивания файла
        header("Content-Type: " . $mimeType);
        header("Content-Disposition: attachment; filename=\"" . $safeFileName . "\"");
        header("Content-Length: " . strlen($fileData));
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        header("Expires: 0");
        
        // Отключаем буферизацию для больших файлов
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', '0');

        // Выводим данные файла
        echo $fileData;
        exit;
    }
} 