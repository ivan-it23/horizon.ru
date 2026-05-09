# Техническая документация

Документ описывает текущую структуру приложения учёта приборов АМК «Горизонт» и предназначен для разработчиков, которые сопровождают backend, frontend и AI-агента.

Актуальность: 2026-05-09.

## 1. Общая схема приложения

Приложение построено как PHP-проект без внешнего фреймворка. Маршрутизация AJAX-запросов выполняется через `front.php`:

```text
GET/POST /front.php?c=device&a=search
  -> App\Controller\DeviceController::search()
```

Автозагрузка классов реализована в `autoload.php` для namespace `App\` и директории `src/`.

Основные слои:

- страницы верхнего уровня: `index.php`, `AccountingOfDevices2.php`, `profile.php`, `admin_panel.php`;
- контроллеры: `src/Controller`;
- сервисы: `src/Service`;
- security-помощники: `src/Security` и `security_init.php`;
- клиентские модули: `assests/js`;
- стили: `assests/css`, `ai_agent/css`;
- AI-agent UI: `ai_agent/templates`, `ai_agent/js`, `ai_agent/css`.

## 2. Контроллеры

| Контроллер | Методы | Ответственность |
| --- | --- | --- |
| `AuthController` | `login`, `logout`, `checkSession` | Аутентификация и контроль активности сессии. |
| `HomeController` | `index` | Стартовая маршрутизируемая страница. |
| `DeviceController` | `search`, `filter`, `index`, `composition`, `history`, `export` | Поиск, фильтры, состав, история и экспорт данных прибора. |
| `DeviceDataController` | `create`, `update`, `history` | Создание и изменение основной карточки прибора. |
| `DeviceHistoryController` | `history` | Получение истории изменений карточки. |
| `TestingController` | `create`, `history`, `update` | Испытания прибора. |
| `RepairController` | `create`, `update`, `history`, `editHistory`, `delete` | Ремонты и история изменений ремонта. |
| `InspectionController` | `create`, `history` | Неразрушающий контроль корпуса. |
| `ProductionController` | `create`, `history` | Выход прибора из производства. |
| `TerminationController` | `create`, `history` | Прекращение эксплуатации. |
| `AdditionalDataController` | `create`, `history` | Дополнительные сведения. |
| `FileController` | `upload`, `list`, `download`, `delete`, `rename` | Управление прикреплёнными файлами. |
| `MessageController` | `send`, `sent`, `markRead`, `received`, `users`, `role` | Внутренняя переписка. |
| `ToleranceController` | `get`, `save` | Управление допусками испытаний. |
| `AiAgentController` | `chat`, `chatStream` | AI-агент, синхронные и streaming-ответы. |

Все контроллеры наследуют `BaseController`, где сосредоточены общие операции: `requireAuth`, `requireAdmin`, `validateCSRF`, `generateCSRF`, `sanitizeString`, `validateInt`, `checkRateLimit`, `logSecurityEvent`, `jsonResponse`, `handleError`, `sendFileDownload`.

## 3. Сервисный слой

| Сервис | Назначение |
| --- | --- |
| `SecurityService` | Проверка авторизации, CSRF, rate limiting, заголовки безопасности, журналирование. |
| `QueryAnalyzer` | Определение типа пользовательского запроса к AI-агенту. |
| `QueryExecutor` | Выполнение предопределённых запросов AI-агента к БД. |
| `DatabaseQueryHandler` | Координация анализа, выполнения, LLM-fallback и контекста. |
| `DatabaseSchemaService` | Описание схемы БД для LLM и выполнение read-only SQL. |
| `ContextManager` | Стек контекстных фреймов диалога AI-агента. |
| `DeviceInfoManager` | Сбор карточки прибора и связанных данных. |
| `ResponseFormatter` | Форматирование ответов в Markdown. |
| `ResponseTemplateManager` | Шаблонные ответы для типовых сценариев. |

## 4. База данных

Подключение выполняется через `db_connection.php` и `mysqli`. Конфигурация хранится в `config.php`.

Основные таблицы:

- `device_identification` - основная карточка прибора.
- `device_history` - история карточки.
- `device_composition` и `device_composition_history` - состав прибора.
- `testing_data` - результаты испытаний.
- `repair_data` и `repair_data_history` - ремонты и история изменений ремонтов.
- `inspection_data` - неразрушающий контроль.
- `production_discontinuation` - выход из производства.
- `termination_data` - прекращение эксплуатации.
- `additional_data` - дополнительные сведения.
- `tolerances_lwd`, `tolerances_cartographer`, `tolerances_autonomous` - допуски по типам приборов.
- `uploaded_files` - прикреплённые файлы.
- `users`, `messages` - пользователи и сообщения.

Важное правило для запросов: таблицы жизненного цикла связываются с прибором по `serial_number` и `case_number`. В этих таблицах нельзя полагаться на `device_id`.

## 5. Основные пользовательские сценарии

### Поиск и карточка прибора

Клиентский модуль `deviceSearchManager.js` отправляет запросы в `DeviceController`. Результаты отображаются через `deviceViewer.js`; состав, история и экспорт запрашиваются отдельными endpoint-ами.

### Создание и изменение прибора

`deviceFormManager.js` собирает форму, `DeviceDataController` выполняет серверную валидацию и запись. Изменения фиксируются в истории.

### Жизненный цикл

Испытания, ремонты, контроль, прекращение эксплуатации и дополнительные сведения имеют отдельные контроллеры. На frontend их обслуживают специализированные менеджеры.

### Файлы

`fileManager.js` работает с `FileController`. Данные файлов хранятся в `uploaded_files`. При выборках для AI-агента содержимое `file_data` не должно включаться в SELECT без явной необходимости.

### Сообщения

`messageManager.js` использует `MessageController` для отправки, чтения входящих/исходящих и отметки о прочтении.

## 6. AI-агент

AI-агент доступен через drawer-интерфейс из `ai_agent/templates/accounting_ai_interface.php`.

Обработка запроса:

1. `AiAgentController::chat()` или `chatStream()` принимает сообщение.
2. `DatabaseQueryHandler` вызывает `QueryAnalyzer`.
3. Если запрос типовой, `QueryExecutor` или `DeviceInfoManager` возвращает результат без LLM.
4. Если запрос нестандартный, `DatabaseSchemaService` формирует контекст схемы для LLM.
5. LLM генерирует только read-only SQL или сообщает, что SQL не нужен.
6. Результаты SQL форматируются в ответ пользователю.
7. `ContextManager` сохраняет структурированный фрейм результата.

Контекстные фреймы содержат:

- тему (`devices`, `repairs`, `testing`, `termination` и др.);
- найденные приборы;
- типы приборов;
- строки SQL-результатов;
- краткое описание ответа;
- срок жизни фрейма.

Благодаря этому агент понимает ссылки на предыдущие результаты: «данный прибор», «этим прибором», «первый из списка», «эти приборы», «по этому типу».

## 7. Frontend-структура

Клиентский код организован по функциональным менеджерам:

| Файл | Назначение |
| --- | --- |
| `accounting.js` | Инициализация основного интерфейса. |
| `deviceStorage.js` | Сохранение выбранного прибора в браузере. |
| `deviceSearchManager.js` | Поиск и фильтрация приборов. |
| `deviceViewer.js` | Отображение данных прибора. |
| `deviceFormManager.js` | Формы создания и редактирования. |
| `deviceUpdater.js` | Сохранение изменений. |
| `deviceHistoryManager.js` | История изменений. |
| `deviceClipboard.js` | Копирование и вставка данных прибора. |
| `testingDataManager.js` | Испытания. |
| `repairManager.js` | Ремонты. |
| `toleranceManager.js` | Допуски. |
| `fileManager.js` | Файлы. |
| `messageManager.js` | Сообщения. |
| `modalManager.js` | Модальные окна. |
| `notifications.js` | Уведомления. |
| `themeManager.js` | Тема интерфейса. |
| `tabManager.js`, `tabModeManager.js` | Вкладки и режимы вкладок. |

Стили разделены на design tokens, компоненты и страницы. Новые компоненты следует добавлять в `assests/css/components`, а page-specific стили - в `assests/css/pages`.

## 8. Безопасность

Новые endpoint-ы должны использовать возможности `BaseController`:

- обязательный вызов `requireAuth()` для закрытых действий;
- `requireAdmin()` для административных функций;
- проверка CSRF для изменяющих операций;
- `sanitizeString()` и `validateInt()` для пользовательского ввода;
- `checkRateLimit()` для операций с риском перебора или злоупотребления;
- подготовленные выражения mysqli;
- `jsonResponse()` для единообразных API-ответов.

Подробные правила описаны в `SECURITY_USAGE_GUIDE.md`.

## 9. Логирование и диагностика

Логи:

- `logs/error.log` - ошибки PHP и приложения;
- `logs/security.log` - события безопасности;
- `logs/ai/*.log` - AI-запросы, анализ, SQL, ответы и debug-данные LLM.

При диагностике AI-агента сначала проверяйте:

- `analysis.type` в AI-логе;
- `dbResult`;
- SQL, сгенерированный LLM;
- наличие блока контекста диалога;
- сохранение `ai_context_frames` в сессии.

## 10. Правила сопровождения

- Обновляйте документацию вместе с изменением контроллеров, сервисов, БД и публичных маршрутов.
- Не добавляйте в документацию реальные секреты, пароли, токены и приватные ключи.
- Не используйте эмодзи и рекламные формулировки в технических документах.
- Для AI-агента фиксируйте новые intent-ы в `MODULAR_ARCHITECTURE.md` и `ai_agent/ai_agent_instructions.md`.
- Для новых lifecycle-таблиц явно указывайте ключ связи с прибором.
