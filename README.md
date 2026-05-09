# Система учёта приборов АМК «Горизонт»

Веб-приложение для ведения базы геофизических приборов, их состава, испытаний, ремонтов, контроля, файлов и внутренней переписки. Проект ориентирован на корпоративную эксплуатацию: строгий интерфейс, серверная валидация, централизованная авторизация, журналирование и AI-агент для запросов к данным на естественном языке.

Документация актуализирована: 2026-05-09.

## Назначение

Система хранит полную карточку прибора и связанные события жизненного цикла:

- идентификационные и технические параметры прибора;
- состав прибора и история изменений состава;
- производство, испытания, ремонты, неразрушающий контроль и прекращение эксплуатации;
- дополнительные сведения и прикреплённые файлы;
- внутренние сообщения пользователей;
- запросы к данным через AI-агента.

Основной пользовательский интерфейс расположен в `AccountingOfDevices2.php`. Маршрутизируемые AJAX-операции выполняются через `front.php?c={controller}&a={action}`.

## Технологический стек

- Backend: PHP 7.4+, mysqli, собственная PSR-4-совместимая автозагрузка без Composer.
- Database: MySQL 5.7+ или совместимая версия.
- Frontend: HTML, CSS, JavaScript ES6.
- UI: модульные CSS-файлы в `assests/css`, Font Awesome, Marked.js для Markdown-ответов AI-агента.
- AI: `AiAgentController`, `DatabaseQueryHandler`, `DatabaseSchemaService`, Polza.ai API.

## Архитектура

Проект использует фронт-контроллер и набор специализированных контроллеров:

```text
front.php
  -> App\Controller\{Name}Controller::{action}()
     -> App\Service\*
     -> db_connection.php / mysqli
```

Ключевые директории:

```text
src/Controller/      HTTP-обработчики и AJAX-endpoints
src/Service/         бизнес-логика, AI-агент, безопасность, форматирование
src/Security/        помощники защиты страниц
assests/js/          клиентские модули основного интерфейса
assests/css/         дизайн-система, компоненты и стили страниц
ai_agent/            шаблон, CSS, JS и инструкции AI-агента
logs/                системные, security- и AI-логи
icons/, images/      статические ресурсы
```

Основные входные файлы:

- `index.php` - стартовая страница.
- `AccountingOfDevices2.php` - рабочее окно учёта приборов.
- `profile.php` - профиль и внутренние сообщения.
- `admin_panel.php` - административные функции.
- `backup.php` - резервное копирование.
- `front.php` - маршрутизатор контроллеров.

## Основные модули

### Контроллеры

- `AuthController` - вход, выход, проверка сессии.
- `DeviceController` - поиск, фильтрация, состав, история и экспорт карточки прибора.
- `DeviceDataController` - создание, обновление и история идентификационных данных.
- `TestingController`, `RepairController`, `InspectionController`, `ProductionController`, `TerminationController`, `AdditionalDataController` - события жизненного цикла прибора.
- `FileController` - загрузка, список, скачивание, удаление и переименование файлов.
- `MessageController` - внутренняя переписка.
- `ToleranceController` - чтение и сохранение допусков испытаний.
- `AiAgentController` - синхронный и streaming-чат AI-агента.

### Сервисы AI-агента

- `QueryAnalyzer` - распознаёт типовые запросы и контекстные ссылки.
- `QueryExecutor` - выполняет безопасные предопределённые запросы.
- `DatabaseQueryHandler` - координирует анализ, выполнение и контекст.
- `DatabaseSchemaService` - формирует описание схемы БД и выполняет read-only SQL для LLM.
- `ContextManager` - хранит стек контекстных фреймов диалога.
- `DeviceInfoManager` - собирает карточку прибора и связанные данные.
- `ResponseFormatter` - форматирует ответы в Markdown.
- `ResponseTemplateManager` - рендерит шаблонные ответы.

Контекст AI-агента хранится в сессии как стек структурированных фреймов. Это позволяет корректно обрабатывать фразы «этот прибор», «первый из списка», «эти приборы», «по нему», «по этому типу».

## База данных

Ключевые таблицы:

- `device_identification` - основная карточка прибора.
- `device_history` - история изменений карточки.
- `device_composition`, `device_composition_history` - текущий состав и история состава.
- `production_discontinuation`, `testing_data`, `repair_data`, `inspection_data`, `termination_data`, `additional_data` - жизненный цикл.
- `tolerances_lwd`, `tolerances_cartographer`, `tolerances_autonomous` - допуски по типам приборов.
- `uploaded_files` - прикреплённые документы.
- `users`, `messages` - пользователи и внутренняя переписка.

Для lifecycle-таблиц связь с прибором выполняется по бизнес-ключам `serial_number` и `case_number`. Не следует предполагать наличие `device_id` в этих таблицах.

## Безопасность

Базовый слой безопасности сосредоточен в `BaseController`, `SecurityService`, `PageSecurityHelper` и `security_init.php`.

Используются:

- проверка авторизации и роли администратора;
- CSRF-токены;
- rate limiting;
- очистка и валидация входных данных;
- подготовленные SQL-запросы;
- безопасные HTTP-заголовки;
- bcrypt для паролей;
- журналирование событий в `logs/security.log`.

Подробности: [SECURITY_OVERVIEW.md](SECURITY_OVERVIEW.md) и [SECURITY_USAGE_GUIDE.md](SECURITY_USAGE_GUIDE.md).

## Frontend

Основной интерфейс построен на модульных JS-менеджерах в `assests/js`:

- `accounting.js` - инициализация рабочего окна;
- `deviceSearchManager.js`, `deviceViewer.js`, `deviceFormManager.js`, `deviceUpdater.js`, `deviceHistoryManager.js`, `deviceClipboard.js` - работа с карточками приборов;
- `testingDataManager.js`, `repairManager.js`, `toleranceManager.js`, `fileManager.js` - специализированные операции;
- `modalManager.js`, `notifications.js`, `uiHelper.js`, `utils.js` - общие UI-утилиты;
- `themeManager.js`, `tabManager.js`, `tabModeManager.js` - тема и вкладки.

CSS организован по слоям:

- `assests/css/tokens.css` - дизайн-токены;
- `assests/css/components/*` - базовые компоненты;
- `assests/css/pages/*` - стили страниц;
- `ai_agent/css/ai_agent.css` - отдельные стили AI-drawer.

## Конфигурация

Основные настройки находятся в `config.php`:

- параметры подключения к БД;
- режим разработки/production;
- настройки сессии;
- лимиты загрузки файлов;
- API-ключ AI-провайдера;
- пути для логов и uploads.

Конфиденциальные значения в production должны храниться вне репозитория или передаваться через защищённые переменные окружения. При изменении конфигурации не публикуйте реальные пароли и API-ключи в документации, логах и публичных коммитах.

## Логи

- `logs/error.log` - ошибки приложения.
- `logs/security.log` - события безопасности.
- `logs/ai/*.log` - запросы, анализ, SQL-результаты и ответы AI-агента.

Логи AI-агента используются для диагностики маршрутизации запросов, ошибок LLM и проблем с контекстом.

## Документация

- [documentation.md](documentation.md) - техническая документация проекта.
- [MODULAR_ARCHITECTURE.md](MODULAR_ARCHITECTURE.md) - архитектура AI-агента и сервисов.
- [src/Service/README.md](src/Service/README.md) - краткая карта сервисного слоя.
- [SECURITY_OVERVIEW.md](SECURITY_OVERVIEW.md) - обзор модели безопасности.
- [SECURITY_USAGE_GUIDE.md](SECURITY_USAGE_GUIDE.md) - практическое руководство по защите новых страниц и контроллеров.
- [SECURITY_INTEGRATION_COMPLETE_REPORT.md](SECURITY_INTEGRATION_COMPLETE_REPORT.md) - состояние интеграции security-слоя.
- [REFACTORING_REPORT.md](REFACTORING_REPORT.md) - сведения о рефакторинге AI-агента.
- [PROJECT_CLEANUP_REPORT.md](PROJECT_CLEANUP_REPORT.md) - состояние очистки и структуры документации.
- [ai_agent/ai_agent_instructions.md](ai_agent/ai_agent_instructions.md) - runtime-инструкции AI-агента.

## Правила разработки

- Новые серверные операции добавляются как методы контроллеров в `src/Controller`.
- Общая бизнес-логика выносится в `src/Service`.
- Все пользовательские входные данные проходят валидацию и очистку.
- SQL выполняется через подготовленные выражения, кроме строго контролируемых read-only запросов AI-агента.
- Для lifecycle-данных используйте связь по `serial_number` и `case_number`.
- Ответы AI-агента должны оставаться деловыми, без эмодзи и декоративного текста.
- Документация должна обновляться вместе с изменениями архитектуры, БД и публичных endpoint-ов.
