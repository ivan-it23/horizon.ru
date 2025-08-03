<?php
namespace App\Security;

// Подключаем автозагрузчик если он ещё не подключен
if (!class_exists('App\Service\SecurityService')) {
    require_once __DIR__ . '/../../autoload.php';
}

// Подключаем SecurityService напрямую для надёжности
if (!class_exists('App\Service\SecurityService')) {
    require_once __DIR__ . '/../Service/SecurityService.php';
}

use App\Service\SecurityService;

/**
 * Помощник для обеспечения безопасности страниц
 * Стандартизирует проверки авторизации и логирование для критически важных страниц
 */
class PageSecurityHelper
{
    /**
     * Инициализация безопасности для страницы, требующей авторизации
     * 
     * @param string $pageName Название страницы для логирования
     * @param bool $requireAdmin Требуется ли права администратора
     * @return void
     */
    public static function initializePageSecurity(string $pageName, bool $requireAdmin = false): void
    {
        // Инициализируем сессию если она не запущена и заголовки ещё не отправлены
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        // Проверяем авторизацию
        if ($requireAdmin) {
            SecurityService::requireAdmin();
        } else {
            SecurityService::requireAuth();
        }
        
        // Устанавливаем безопасные заголовки
        SecurityService::setSecurityHeaders();
        
        // Логируем доступ к странице
        SecurityService::logSecurityEvent('page_accessed', [
            'user_id' => $_SESSION['user']['id'],
            'page' => $pageName,
            'require_admin' => $requireAdmin,
            'user_is_admin' => $_SESSION['user']['is_admin'] ?? false
        ]);
    }
    
    /**
     * Генерирует JavaScript код для дополнительной проверки безопасности на клиентской стороне
     * 
     * @param string $redirectUrl URL для перенаправления при ошибке авторизации
     * @param int $sessionCheckInterval Интервал проверки сессии в миллисекундах (по умолчанию 5 минут)
     * @return string JavaScript код
     */
    public static function generateClientSideSecurityScript(string $redirectUrl = 'index.php', int $sessionCheckInterval = 300000): string
    {
        $isLoggedIn = isset($_SESSION['user']['id']) ? 'true' : 'false';
        $userInfo = isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null';
        
        return "
        <script>
        // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА БЕЗОПАСНОСТИ НА КЛИЕНТСКОЙ СТОРОНЕ
        document.addEventListener('DOMContentLoaded', function() {
            const isLoggedIn = {$isLoggedIn};
            const userInfo = {$userInfo};
            
            // Если пользователь не авторизован (дополнительная защита)
            if (!isLoggedIn || !userInfo) {
                console.warn('Обнаружена попытка доступа к странице без авторизации');
                window.location.href = '{$redirectUrl}';
                return;
            }
            
            // Проверяем валидность сессии периодически
            setInterval(function() {
                fetch('front.php?c=auth&a=checkSession', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.authenticated) {
                        console.warn('Сессия истекла или недействительна');
                        
                        // Показываем уведомление если функция доступна
                        if (typeof showNotification === 'function') {
                            showNotification('Ваша сессия истекла. Пожалуйста, войдите в систему заново.', 'warning');
                        } else {
                            alert('Ваша сессия истекла. Пожалуйста, войдите в систему заново.');
                        }
                        
                        // Перенаправляем на главную страницу через 3 секунды
                        setTimeout(function() {
                            window.location.href = '{$redirectUrl}';
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Ошибка проверки сессии:', error);
                });
            }, {$sessionCheckInterval});
            
            // Защита от манипуляций с DOM
            Object.freeze(window.location);
            
            // Логируем успешную загрузку страницы
            console.log('Страница успешно загружена для пользователя:', userInfo.lastname + ' ' + userInfo.firstname);
        });
        </script>";
    }
    
    /**
     * Проверяет, истекла ли сессия пользователя
     * 
     * @param int $maxInactiveTime Максимальное время неактивности в секундах (по умолчанию 1 час)
     * @return bool True если сессия истекла
     */
    public static function isSessionExpired(int $maxInactiveTime = 3600): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return false;
        }
        
        if (time() - $_SESSION['last_activity'] > $maxInactiveTime) {
            return true;
        }
        
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    /**
     * Принудительно завершает сессию и перенаправляет пользователя
     * 
     * @param string $reason Причина завершения сессии
     * @param string $redirectUrl URL для перенаправления
     * @return void
     */
    public static function forceLogout(string $reason, string $redirectUrl = 'index.php'): void
    {
        // Логируем принудительный выход
        SecurityService::logSecurityEvent('forced_logout', [
            'user_id' => $_SESSION['user']['id'] ?? null,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Уничтожаем сессию
        session_destroy();
        
        // Перенаправляем пользователя
        header("Location: {$redirectUrl}?logout_reason=" . urlencode($reason));
        exit;
    }
}