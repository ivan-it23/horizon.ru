# Сервисы AI-агента

Эта папка содержит модульную архитектуру для AI-агента системы учёта приборов АМК «Горизонт».

## Модули

### QueryAnalyzer
Анализирует естественно-языковые запросы пользователя и определяет тип запроса к базе данных.

### QueryExecutor  
Выполняет статистические запросы к базе данных (подсчёты, списки, статистика).

### DeviceInfoManager
Работает с информацией о приборах, их составе и связанных данных.

### ResponseFormatter
Форматирует ответы в читаемый Markdown-формат для отображения пользователю.

### ContextManager
Управляет контекстом диалога, сохраняя информацию о последнем найденном приборе.

### DatabaseQueryHandler
Главный координатор всех модулей, предоставляет единый интерфейс для работы с AI-агентом.

## Использование

```php
use App\Service\DatabaseQueryHandler;

$dbHandler = new DatabaseQueryHandler($connection);
$analysis = $dbHandler->analyzeQuery($userMessage);
$result = $dbHandler->executeQuery($analysis);
```

## Миграция

Старый файл `database_query_handler_improved.php` переименован в `.old` и больше не используется.

Подробная документация: [MODULAR_ARCHITECTURE.md](../../MODULAR_ARCHITECTURE.md) 