# Архитектура AI-агента

Документ описывает текущую архитектуру AI-агента и сервисов, которые обеспечивают обработку естественно-языковых запросов к базе приборов.

Актуальность: 2026-05-09.

## 1. Цель модуля

AI-агент предоставляет пользователю деловой интерфейс для получения информации из базы данных:

- поиск приборов по серийному номеру и номеру корпуса;
- статистика по типам приборов;
- анализ ремонтов, испытаний и текущих статусов;
- ответы по контексту предыдущих сообщений;
- генерация read-only SQL для нестандартных запросов.

Агент не должен придумывать данные. Источник фактов - только база данных и результаты выполненных SQL-запросов.

## 2. Runtime-поток

```text
Пользователь
  -> ai_agent/js/ai_agent.js
  -> AiAgentController::chat() или chatStream()
  -> DatabaseQueryHandler
  -> QueryAnalyzer
       -> QueryExecutor / DeviceInfoManager
       -> или LLM-fallback через DatabaseSchemaService
  -> ResponseFormatter / ResponseTemplateManager
  -> ContextManager
  -> Ответ пользователю
```

Поток имеет два режима:

- локальная обработка типового запроса без LLM;
- LLM-fallback для нестандартного запроса с обязательной проверкой и выполнением только read-only SQL.

## 3. Компоненты

### `AiAgentController`

Файл: `src/Controller/AiAgentController.php`

Отвечает за HTTP-слой AI-агента:

- `chat()` - обычный JSON-ответ;
- `chatStream()` - SSE/streaming-ответ;
- вызов LLM;
- извлечение SQL из ответа модели;
- повторная попытка исправления SQL;
- сохранение логов в `logs/ai`;
- сохранение истории диалога в сессии.

Streaming-режим должен сохранять активную сессию до завершения ответа, чтобы `ContextManager` мог записать фреймы контекста.

### `DatabaseQueryHandler`

Файл: `src/Service/DatabaseQueryHandler.php`

Главный координатор:

- создаёт `ContextManager`, `QueryAnalyzer`, `QueryExecutor`, `DeviceInfoManager`, `ResponseFormatter`;
- передаёт запрос на анализ;
- выполняет типовые запросы;
- передаёт схему БД для LLM-fallback;
- сохраняет структурированные результаты в контекст.

### `QueryAnalyzer`

Файл: `src/Service/QueryAnalyzer.php`

Определяет intent запроса. Поддерживает:

- `count_devices_by_type`;
- `count_all_devices`;
- `count_repairs`;
- `list_devices_by_type`;
- `recent_repairs`;
- `last_repair`;
- `devices_in_repair`;
- `devices_in_testing`;
- `device_statistics`;
- `top_repaired_device`;
- `repair_reasons_by_device`;
- `device_info_by_serial`;
- `device_info_by_case`;
- `full_device_info_by_serial`;
- `full_device_info_by_case`;
- `about_agent`;
- `greeting`;
- `farewell`;
- `context_missing`;
- `flexible_device_search`;
- `unknown`.

Контекстные запросы анализируются через `ContextManager::resolveReferences()`. Если пользователь пишет «этот прибор», но в стеке нет подходящего прибора, возвращается `context_missing`; запрос не передаётся LLM, чтобы модель не подставляла случайные значения.

### `QueryExecutor`

Файл: `src/Service/QueryExecutor.php`

Выполняет предопределённые SQL-запросы для типовых intent-ов. Возвращает структурированные массивы, пригодные для сохранения в контекст:

- `devices` для списков приборов;
- `repairs` для ремонтов;
- `tests` для испытаний;
- `count`, `device_type`, `message` для статистики и ответа.

### `DeviceInfoManager`

Файл: `src/Service/DeviceInfoManager.php`

Собирает карточку прибора:

- основная запись из `device_identification`;
- состав из `device_composition`;
- связанные lifecycle-данные;
- история изменений.

Поиск поддерживает серийный номер и номер корпуса.

### `DatabaseSchemaService`

Файл: `src/Service/DatabaseSchemaService.php`

Формирует системный контекст схемы БД для LLM и выполняет read-only SQL. Этот сервис является runtime-источником правил для SQL-генерации:

- какие таблицы доступны;
- какие поля нельзя выбирать;
- как связывать lifecycle-таблицы;
- какие ошибки JOIN типичны;
- как обрабатывать даты и пустые значения.

### `ContextManager`

Файл: `src/Service/ContextManager.php`

Хранит контекст диалога в PHP-сессии:

- `current_device_context` - совместимый текущий прибор;
- `ai_context_frames` - стек структурированных фреймов.

Фрейм содержит:

- `kind` - источник результата;
- `topic` - тема (`devices`, `repairs`, `testing`, `inspection`, `termination`, `messages`, `users`);
- `title`;
- `query`;
- `created_at`, `expires_at`;
- `entities.devices`;
- `entities.device_types`;
- `entities.records`;
- `entities.sql`;
- `summary`.

Параметры:

- TTL фрейма: 30 минут;
- максимальный размер стека: 12 фреймов;
- в prompt LLM передаются последние 5 фреймов.

Поддерживаемые ссылки:

- «этот прибор», «этим прибором», «данный прибор», «по нему», «у него»;
- «первый», «второй», «третий» и числовые порядковые ссылки;
- «эти приборы», «данные приборы», «их»;
- «этот тип», если в предыдущем результате был тип прибора.

### `ResponseFormatter`

Файл: `src/Service/ResponseFormatter.php`

Форматирует технические ответы в Markdown. Стиль ответов должен быть нейтральным и корпоративным: заголовки, списки, таблицы, без эмодзи и декоративных вставок.

### `ResponseTemplateManager`

Файл: `src/Service/ResponseTemplateManager.php`

Рендерит шаблонные ответы из `ai_agent/config/response_templates.php` для известных сценариев.

## 4. LLM-fallback

Если `QueryAnalyzer` возвращает `unknown` или `flexible_device_search`, контроллер запускает двухэтапную схему:

1. LLM получает схему БД, контекст диалога и вопрос пользователя. Она должна вернуть `<sql>...</sql>` или `<no_sql/>`.
2. Приложение выполняет SQL через `DatabaseSchemaService::executeQueryForLLM()`.
3. При ошибке выполняется одна попытка исправления SQL.
4. Результаты SQL передаются LLM для финального ответа.
5. Успешные SQL-результаты сохраняются в `ContextManager::rememberSqlResults()`.

Ограничения:

- разрешены только `SELECT` или read-only `WITH ... SELECT`;
- запрещено выбирать `users.password` и `uploaded_files.file_data`;
- запрещены изменения данных;
- модель не должна делать выводы без результата SQL.

## 5. Контекстные фреймы

Контекстные фреймы решают проблему местоимений и ссылок на предыдущие ответы.

Пример:

```text
Пользователь: У какого прибора больше всего было ремонтов?
Агент: serial_number=105, case_number=002-2023, repairs_count=4

ContextManager сохраняет:
topic=repairs
devices[0]={serial_number:105, case_number:002-2023, device_type:LWD}

Пользователь: Какие проблемы были с этим прибором?
QueryAnalyzer резолвит "этим прибором" в 105 / 002-2023
Intent: repair_reasons_by_device
```

Если ссылка есть, но фрейм не найден, возвращается `context_missing` с просьбой уточнить серийный номер или номер корпуса.

## 6. Логи AI-агента

Каждый запрос логируется в `logs/ai/*.log`. Лог содержит:

- `timestamp`;
- `user`;
- `message`;
- `analysis`;
- `dbResult`;
- `response`;
- `llm_calls`, если использовался LLM;
- признак `streaming` для SSE-ответов.

При анализе ошибки проверяйте `analysis.type`, `dbResult`, SQL в `llm_calls`, а также наличие контекстного блока в системном prompt.

## 7. Добавление нового intent-а

1. Добавить распознавание в `QueryAnalyzer::analyzeQuery()`.
2. Добавить case в `QueryExecutor::executeQuery()`, если запрос можно обработать локально.
3. Вернуть структурированные поля (`devices`, `repairs`, `tests`, `device_type`, `message`), если результат должен попасть в контекст.
4. При необходимости обновить `ContextManager::inferTopic()`.
5. Обновить `ai_agent/ai_agent_instructions.md`.
6. Добавить запись в эту документацию.

## 8. Правила сопровождения

- Локальные intent-ы предпочтительнее LLM для часто используемых запросов.
- Контекстные запросы не должны попадать в LLM без разрешённой ссылки.
- Любой SQL от LLM должен оставаться read-only.
- При изменении схемы БД нужно обновлять `DatabaseSchemaService` и `ai_agent/ai_agent_instructions.md`.
- Ответы AI-агента должны соответствовать строгому корпоративному стилю.
