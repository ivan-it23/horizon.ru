# Сервисный слой

Директория `src/Service` содержит бизнес-логику, которая используется контроллерами и AI-агентом. Контроллеры должны оставаться тонкими: валидация запроса, вызов сервисов, формирование HTTP-ответа.

## Сервисы

| Файл | Назначение |
| --- | --- |
| `SecurityService.php` | Авторизация, CSRF, rate limiting, HTTP-заголовки безопасности, журналирование. |
| `QueryAnalyzer.php` | Распознавание intent-ов AI-агента и контекстных ссылок. |
| `QueryExecutor.php` | Предопределённые SQL-запросы AI-агента. |
| `DatabaseQueryHandler.php` | Координация сервисов AI-агента. |
| `DatabaseSchemaService.php` | Контекст схемы БД для LLM и выполнение read-only SQL. |
| `ContextManager.php` | Стек контекстных фреймов диалога. |
| `DeviceInfoManager.php` | Сбор карточки прибора и связанных данных. |
| `ResponseFormatter.php` | Markdown-форматирование ответов. |
| `ResponseTemplateManager.php` | Шаблонные ответы AI-агента. |

## Типовой вызов AI-агента

```php
use App\Service\DatabaseQueryHandler;

$handler = new DatabaseQueryHandler($conn);
$analysis = $handler->analyzeQuery($userMessage);
$result = $handler->executeQuery($analysis);
```

Если `executeQuery()` возвращает `needs_llm`, контроллер запускает LLM-fallback через `DatabaseSchemaService`.

## Контекст AI-агента

`ContextManager` хранит два уровня контекста:

- `current_device_context` - текущий прибор для обратной совместимости;
- `ai_context_frames` - стек структурированных результатов.

Фреймы используются для запросов вида «этот прибор», «первый из списка», «эти приборы», «по нему», «по этому типу». Успешные DB-результаты сохраняются через `rememberDatabaseResult()`, результаты SQL от LLM - через `rememberSqlResults()`.

## Правила изменения сервисов

- Не переносите HTTP-логику в сервисы.
- Не добавляйте SQL-конкатенацию с пользовательским вводом.
- Для новых AI-intent-ов обновляйте `QueryAnalyzer`, `QueryExecutor`, `ContextManager` и документацию.
- Для ответов AI-агента используйте деловой Markdown без эмодзи.
- Для данных жизненного цикла прибора используйте связь по `serial_number` и `case_number`.
