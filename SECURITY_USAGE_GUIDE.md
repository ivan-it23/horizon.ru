# Руководство по использованию security-слоя

Документ описывает практические правила для разработчиков при добавлении страниц, контроллеров и AJAX-операций.

Актуальность: 2026-05-09.

## 1. Новый контроллер

Контроллер должен наследовать `BaseController`.

```php
<?php
namespace App\Controller;

class ExampleController extends BaseController
{
    public function create(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!$this->validateCSRF($csrf)) {
            $this->logSecurityEvent('csrf_failed', ['controller' => 'example']);
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        $name = $this->sanitizeString($_POST['name'] ?? '', 255);

        // Выполнить бизнес-логику через сервис.

        $this->jsonResponse(['success' => true]);
    }
}
```

## 2. Административный метод

```php
public function adminAction(): void
{
    $this->requireAuth();
    $this->requireAdmin();

    // Административная логика.
}
```

## 3. Rate limiting

Используйте `checkRateLimit()` для операций, которые могут быть использованы для перебора или высокой нагрузки.

```php
$ip = $this->getClientIP();

if (!$this->checkRateLimit($ip, 'example_action', 10, 60)) {
    $this->logSecurityEvent('rate_limit_exceeded', ['action' => 'example_action']);
    $this->jsonResponse(['error' => 'Too many requests'], 429);
}
```

Рекомендуемые лимиты:

| Операция | Лимит |
| --- | --- |
| Авторизация | 5 попыток за 5 минут |
| AI-чат | 20 запросов за минуту |
| Загрузка файлов | 10 операций за минуту |
| Отправка сообщений | 10 сообщений за минуту |

## 4. Валидация входных данных

Строки:

```php
$value = $this->sanitizeString($input, 255);
```

Целые числа:

```php
$id = $this->validateInt($_POST['id'] ?? null, 1);
if ($id === false) {
    $this->jsonResponse(['error' => 'Invalid id'], 400);
}
```

JSON:

```php
$payload = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $this->jsonResponse(['error' => 'Invalid JSON'], 400);
}
```

## 5. SQL

Используйте prepared statements.

```php
$stmt = $conn->prepare('SELECT * FROM device_identification WHERE serial_number = ?');
$stmt->bind_param('s', $serialNumber);
$stmt->execute();
$result = $stmt->get_result();
```

Не используйте конкатенацию SQL с пользовательским вводом.

Для динамических таблиц применяйте whitelist:

```php
if (!\App\Service\SecurityService::validateTableName($tableName)) {
    $this->jsonResponse(['error' => 'Invalid table'], 400);
}
```

## 6. Защита standalone-страниц

Страницы верхнего уровня должны подключать `security_init.php`.

```php
<?php
require_once __DIR__ . '/security_init.php';
initPageSecurity('AccountingOfDevices2.php');
?>
```

Для административной страницы:

```php
initPageSecurity('admin_panel.php', true);
```

В конце HTML при необходимости подключается клиентская часть защиты:

```php
echo generateSecurityScript();
```

## 7. Файловые операции

При загрузке файлов проверяйте:

- авторизацию пользователя;
- принадлежность файла к прибору;
- размер файла;
- имя файла после очистки;
- MIME/type, если операция зависит от типа;
- права на скачивание, удаление и переименование.

Не отдавайте содержимое файла напрямую из SQL без endpoint-а, который выставляет корректные заголовки скачивания.

## 8. AI-агент

Для новых AI-сценариев:

1. Сначала добавьте локальный intent в `QueryAnalyzer`, если запрос типовой.
2. SQL выполняйте в `QueryExecutor` через prepared statements.
3. Если результат должен быть доступен в следующей реплике, возвращайте структурированные поля: `devices`, `repairs`, `tests`, `device_type`.
4. Не передавайте контекстные запросы в LLM, если `ContextManager` не нашёл объект ссылки.
5. Не включайте в SQL-подсказки секретные поля и бинарные данные.

## 9. Логирование

Пример:

```php
$this->logSecurityEvent('device_update', [
    'device_id' => $deviceId,
    'serial_number' => $serialNumber,
]);
```

В логи нельзя писать:

- пароли;
- API-ключи;
- CSRF-токены;
- содержимое файлов;
- персональные данные сверх необходимого для аудита.

## 10. Чек-лист перед merge

- Метод закрыт через `requireAuth()`.
- Для администратора используется `requireAdmin()`.
- Все входные параметры валидируются.
- Изменяющие запросы защищены CSRF.
- SQL использует prepared statements.
- Есть rate limit для чувствительных действий.
- Ошибки возвращаются в едином JSON-формате.
- Значимые события логируются.
- Документация обновлена, если изменился публичный контракт.
