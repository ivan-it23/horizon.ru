<?php
namespace App\Service;

class SecurityService
{
    /**
     * Генерация CSRF токена
     */
    public static function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Валидация CSRF токена
     */
    public static function validateCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Безопасная очистка строки
     */
    public static function sanitizeString(string $input, int $maxLength = 255): string
    {
        return htmlspecialchars(trim(substr($input, 0, $maxLength)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Валидация email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Валидация целого числа
     */
    public static function validateInt($input, ?int $min = null, ?int $max = null)
    {
        $int = filter_var($input, FILTER_VALIDATE_INT);
        if ($int === false) return false;
        if ($min !== null && $int < $min) return false;
        if ($max !== null && $int > $max) return false;
        return $int;
    }

    /**
     * Rate limiting
     */
    public static function checkRateLimit(string $identifier, string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $key = "rate_limit_{$action}_{$identifier}";
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time()];
        
        // Сброс счетчика если прошло время
        if (time() - $attempts['first_attempt'] > $timeWindow) {
            $attempts = ['count' => 0, 'first_attempt' => time()];
        }
        
        if ($attempts['count'] >= $maxAttempts) {
            return false; // Лимит превышен
        }
        
        $attempts['count']++;
        $_SESSION[$key] = $attempts;
        return true;
    }

    /**
     * Валидация имени таблицы по whitelist
     */
    public static function validateTableName(string $tableName): bool
    {
        $allowedTables = [
            'additional_data', 'device_history', 'device_identification',
            'device_composition', 'device_composition_history', 'inspection_data',
            'production_discontinuation', 'repair_data', 'termination_data',
            'testing_data', 'tolerances_autonomous', 'tolerances_cartographer',
            'tolerances_lwd', 'users', 'messages'
        ];
        
        return in_array($tableName, $allowedTables);
    }

    /**
     * Безопасное выполнение SQL-запросов (только SELECT, SHOW, DESCRIBE)
     */
    public static function validateSQLQuery(string $query): bool
    {
        $allowedCommands = ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN'];
        
        $queryTrimmed = trim($query);
        $firstWord = strtoupper(explode(' ', $queryTrimmed)[0]);
        
        if (!in_array($firstWord, $allowedCommands)) {
            return false;
        }
        
        // Проверка на опасные конструкции
        $dangerousPatterns = [
            '/\bINTO\s+OUTFILE\b/i',
            '/\bLOAD_FILE\b/i',
            '/\bUNION\b.*\bSELECT\b/i',
            '/;\s*\w+/i', // Множественные запросы
            '/\bDROP\b/i',
            '/\bDELETE\b/i',
            '/\bUPDATE\b/i',
            '/\bINSERT\b/i',
            '/\bALTER\b/i',
            '/\bCREATE\b/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Установка безопасных HTTP заголовков
     */
    public static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // CSP заголовок (настройте под ваши нужды)
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data:; " .
               "connect-src 'self'";
        
        header("Content-Security-Policy: $csp");
    }

    /**
     * Логирование событий безопасности
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user']['id'] ?? null,
            'data' => $data
        ];
        
        $logFile = $logDir . '/security.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Проверка прав администратора
     */
    public static function requireAdmin(): void
    {
        // Инициализируем сессию если она не запущена
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
            self::logSecurityEvent('unauthorized_admin_access');
            header('Location: index.php');
            exit;
        }
    }

    /**
     * Проверка авторизации
     */
    public static function requireAuth(): void
    {
        // Инициализируем сессию если она не запущена
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['id'])) {
            header('Location: index.php');
            exit;
        }
    }
}