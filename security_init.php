<?php
/**
 * Инициализация системы безопасности для страниц
 * Этот файл гарантированно подключает все необходимые зависимости
 */

// Подключаем автозагрузчик если он существует
if (file_exists(__DIR__ . '/autoload.php')) {
    require_once __DIR__ . '/autoload.php';
}

// Подключаем SecurityService напрямую
if (file_exists(__DIR__ . '/src/Service/SecurityService.php')) {
    require_once __DIR__ . '/src/Service/SecurityService.php';
}

// Подключаем PageSecurityHelper напрямую
if (file_exists(__DIR__ . '/src/Security/PageSecurityHelper.php')) {
    require_once __DIR__ . '/src/Security/PageSecurityHelper.php';
}

// Проверяем, что все классы доступны
if (!class_exists('App\Service\SecurityService')) {
    die('Ошибка: SecurityService не найден. Проверьте структуру файлов.');
}

if (!class_exists('App\Security\PageSecurityHelper')) {
    die('Ошибка: PageSecurityHelper не найден. Проверьте структуру файлов.');
}

/**
 * Быстрая функция для инициализации безопасности страницы
 * 
 * @param string $pageName Название страницы
 * @param bool $requireAdmin Требуются ли права администратора
 */
function initPageSecurity($pageName, $requireAdmin = false) {
    try {
        \App\Security\PageSecurityHelper::initializePageSecurity($pageName, $requireAdmin);
    } catch (Exception $e) {
        error_log("Ошибка инициализации безопасности страницы {$pageName}: " . $e->getMessage());
        
        // Fallback - базовая проверка авторизации
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['id'])) {
            header('Location: index.php');
            exit;
        }
        
        if ($requireAdmin && (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin'])) {
            header('Location: index.php');
            exit;
        }
    }
}

/**
 * Генерация клиентского скрипта безопасности
 * 
 * @param string $redirectUrl URL для перенаправления
 * @param int $checkInterval Интервал проверки в миллисекундах
 * @return string JavaScript код
 */
function generateSecurityScript($redirectUrl = 'index.php', $checkInterval = 300000) {
    try {
        // Инициализируем сессию только если заголовки ещё не отправлены
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        
        return \App\Security\PageSecurityHelper::generateClientSideSecurityScript($redirectUrl, $checkInterval);
    } catch (Exception $e) {
        error_log("Ошибка генерации скрипта безопасности: " . $e->getMessage());
        
        // Fallback - базовый скрипт с улучшенной обработкой ошибок
        $isLoggedIn = isset($_SESSION['user']['id']) ? 'true' : 'false';
        $userInfo = isset($_SESSION['user']) ? json_encode($_SESSION['user'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
        
        return "
<script>
// БАЗОВАЯ ПРОВЕРКА БЕЗОПАСНОСТИ (FALLBACK)
document.addEventListener('DOMContentLoaded', function() {
    try {
        const isLoggedIn = {$isLoggedIn};
        const userInfo = {$userInfo};
        
        // Если пользователь не авторизован
        if (!isLoggedIn || !userInfo) {
            console.warn('Пользователь не авторизован, перенаправление...');
            window.location.href = '{$redirectUrl}';
            return;
        }
        
        // Базовая проверка сессии каждые 5 минут
        setInterval(function() {
            fetch('front.php?c=auth&a=checkSession', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!data.success || !data.authenticated) {
                    console.warn('Сессия истекла');
                    if (typeof showNotification === 'function') {
                        showNotification('Ваша сессия истекла. Пожалуйста, войдите в систему заново.', 'warning');
                    } else {
                        alert('Ваша сессия истекла. Пожалуйста, войдите в систему заново.');
                    }
                    setTimeout(function() {
                        window.location.href = '{$redirectUrl}';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Ошибка проверки сессии:', error);
            });
        }, {$checkInterval});
        
        console.log('Базовая система безопасности инициализирована для пользователя:', userInfo.lastname || 'unknown');
        
    } catch (error) {
        console.error('Ошибка инициализации системы безопасности:', error);
        // В случае критической ошибки перенаправляем на главную
        window.location.href = '{$redirectUrl}';
    }
});
</script>";
    }
}
?>