# Документация по системе учёта приборов АМК «Горизонт»

## 1. Общая архитектура

Проект реализован по паттерну MVC с Front Controller и современной системой безопасности:
- **Точка входа:** `front.php?c={Controller}&a={action}`
- **Контроллеры:** `src/Controller/*Controller.php` (16 контроллеров)
- **Сервисы:** `src/Service/*` (модульная архитектура)
- **Безопасность:** `src/Security/*` (централизованная система защиты)
- **Автозагрузка:** PSR-4 через `autoload.php`
- **Инициализация безопасности:** `security_init.php`

## 2. Контроллеры

### AuthController
- login (POST `/front.php?c=auth&a=login`)
- logout (POST `/front.php?c=auth&a=logout`)

### MessageController
- send (POST `/front.php?c=message&a=send`)
- sent (GET/POST `/front.php?c=message&a=sent`)
- received (GET/POST `/front.php?c=message&a=received`)
- markRead (POST `/front.php?c=message&a=markRead`)
- users (GET `/front.php?c=message&a=users`)
- role (GET `/front.php?c=message&a=role`)

### DeviceController
- search (POST `/front.php?c=device&a=search`)
- filter (POST `/front.php?c=device&a=filter`)
- composition (POST `/front.php?c=device&a=composition`)
- history (POST `/front.php?c=device&a=history`)
- export (GET `/front.php?c=device&a=export&device_id=...&serial_number=...&case_number=...`)

## 3. JavaScript-модули (в `assests/js/`)

- `accounting.js` — инициализация и координация модулей
- `deviceStorage.js` — localStorage для выбранного прибора
- `deviceViewer.js` — отображение и экспорт данных прибора
- `deviceFormManager.js` — создание/валидация форм приборов
- `deviceSearchManager.js` — поиск приборов
- `deviceUpdater.js` — сохранение изменений приборов
- `deviceHistoryManager.js` — показ истории изменений
- `deviceClipboard.js` — копирование/вставка приборов
- `tabManager.js`, `tabModeManager.js` — управление вкладками
- `testingDataManager.js`, `toleranceManager.js`, `repairManager.js`, `inspectionManager.js`, `term​inationManager.js`, `additionalDataManager.js`, `fileManager.js` — модули жизненного цикла приборов
- `messageManager.js` — модуль мессенджера (авторизация через контроллер)
- `uiHelper.js`, `utils.js` — вспомогательные функции

Все AJAX-запросы выполняются через `fetch('front.php?c=...&a=...')`.

## 4. Экспорт данных прибора

При нажатии «Скачать данные для прибора» (`exportDeviceData`) открывается ссылка на `DeviceController::export`, который собирает:
1. Идентификационные данные
2. Состав прибора
3. Историю параметров и состава
4. Сведения о жизненном цикле (производство, испытания, ремонт, инспекция, прекращение эксплуатации, доп. сведения)
5. Таблицы допусков (`tolerances_autonomous`, `tolerances_cartographer`, `tolerances_lwd`) по `device_type`

Результат экспортируется в Excel (HTML-таблицы в XLS).

## 5. База данных

Основные таблицы:
- `device_identification`
- `device_composition`, `device_composition_history`
- `device_history`
- `production_discontinuation`, `testing_data`, `repair_data`, `inspection_data`, `termination_data`, `additional_data`
- `tolerances_autonomous`, `tolerances_cartographer`, `tolerances_lwd`
- `messages`, `users`
- `uploaded_files`

За работу с БД отвечает `db_connection.php` (mysqli) и вспомогательные функции `fetchResults` и `executeQuery`.

## 6. Интерфейс и стили

- Используется корпоративный стиль моделей—модалок вместо `alert()`
- CSS-файлы: `corporate_style.css`, `StyleAccountingOfDevices.css`, `StyleForProfile.css`, `accounting_ai_agent.css`
- Модальные окна управляются через JS-функции `showModal`, `hideModal`

## 7. Безопасность и требования

### Технические требования:
- **PHP:** 7.4+
- **MySQL:** 5.7+
- **Браузер:** с поддержкой ES6

### Система безопасности:
- ✅ **SecurityService** - централизованная система безопасности
- ✅ **PageSecurityHelper** - защита страниц от несанкционированного доступа
- ✅ **Rate limiting** - защита от брутфорс атак и злоупотреблений
- ✅ **CSRF защита** - токены для защиты от межсайтовых запросов
- ✅ **Подготовленные SQL-запросы** - защита от SQL-инъекций
- ✅ **Безопасные HTTP заголовки** - CSP, XSS защита, clickjacking защита
- ✅ **Хеширование паролей** - bcrypt с автообновлением MD5 → bcrypt
- ✅ **Логирование событий** - детальные логи безопасности в `logs/security.log`
- ✅ **Валидация и очистка данных** - все входные данные проходят валидацию

## 8. Рекомендации по развитию

1. Внедрить DI-контейнер, сервисы, репозитории
2. Настроить тестирование (PHPUnit) и CI/CD
3. Оптимизировать производительность, добавить кэш
4. Адаптивный SPA-фронтенд (Vue/React)
5. Мониторинг и контейнеризация (Docker)
6. Расширить AI-агент: ML-сервис для аналитики 