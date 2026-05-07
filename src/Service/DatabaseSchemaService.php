<?php
namespace App\Service;

/**
 * Сервис для получения структуры базы данных
 * Предоставляет контекст для LLM о схеме БД
 */
class DatabaseSchemaService
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Получает полный контекст базы данных для LLM
     *
     * Контекст вшивается в системный промпт LLM на каждом «сложном» запросе
     * (см. AiAgentController::callLLMWithDatabaseContext / streamLLMWithDatabaseContext).
     * Цель — дать модели достаточно знаний о реальной схеме БД, чтобы она могла
     * самостоятельно генерировать корректные SELECT-запросы для нетривиальных вопросов.
     */
    public function getDatabaseContextForLLM(): string
    {
        $context  = $this->renderDomainOverview();
        $context .= $this->renderTablesReference();
        $context .= $this->renderRelations();
        $context .= $this->renderEnumerations();
        $context .= $this->renderForbidden();
        $context .= $this->renderJoinAntipatterns();
        $context .= $this->renderSearchRules();
        $context .= $this->renderQueryPatterns();
        return $context;
    }

    /**
     * Краткое описание предметной области
     */
    private function renderDomainOverview(): string
    {
        $out  = "=== ОБЛАСТЬ И НАЗНАЧЕНИЕ БД ===\n\n";
        $out .= "База данных: ivanbohg_account. Корпоративная система учёта приборов индукционного каротажа АМК ГОРИЗОНТ.\n";
        $out .= "Хранит весь жизненный цикл прибора: идентификация -> состав плат -> испытания (с замерами и допусками) ->\n";
        $out .= "неразрушающий контроль корпуса -> ремонты -> выход из производства -> прекращение эксплуатации.\n";
        $out .= "Дополнительно: пользователи системы, личные сообщения, прикреплённые файлы, исторические снимки изменений.\n";
        $out .= "Основные «бизнес-ключи» прибора — пара (serial_number, case_number); большинство lifecycle-таблиц связаны с\n";
        $out .= "device_identification именно по этим строковым полям, а не по device_id.\n\n";
        return $out;
    }

    /**
     * Полный справочник таблиц с типами полей и пояснениями
     */
    private function renderTablesReference(): string
    {
        $out = "=== СПРАВОЧНИК ТАБЛИЦ ===\n\n";

        foreach ($this->getSchemaDefinition() as $tableName => $info) {
            $out .= "ТАБЛИЦА: {$tableName}\n";
            $out .= "Назначение: {$info['description']}\n";
            if (!empty($info['notes'])) {
                $out .= "Особенности: {$info['notes']}\n";
            }
            $out .= "Поля:\n";
            foreach ($info['fields'] as $field => $description) {
                $out .= "  - {$field}: {$description}\n";
            }
            $out .= "\n";
        }

        return $out;
    }

    /**
     * Структурное описание схемы — единый источник правды для контекста
     */
    private function getSchemaDefinition(): array
    {
        return [
            'device_identification' => [
                'description' => 'Основная карточка прибора. Каждая запись — один физический прибор.',
                'notes' => 'Числовые характеристики (length_mm, diameter_mm, frequency*_khz, *_base*_mm, transmitter_count, nominal_diameter) фактически хранятся как varchar — при необходимости числовых сравнений используйте CAST(... AS DECIMAL) или CAST(... AS UNSIGNED). Связи: hard FK от device_composition.device_id -> id (ON DELETE CASCADE); soft-связи со всеми lifecycle-таблицами идут по бизнес-ключу serial_number и/или case_number, без формальных FK.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT — внутренний идентификатор',
                    'device_type' => "varchar, NOT NULL — тип прибора, значения: 'LWD', 'Картограф', 'Автономный'",
                    'nominal_diameter' => 'varchar, NOT NULL — номинальный диаметр (например, "90", "120")',
                    'transmitter_count' => 'varchar, NOT NULL — число передатчиков ("3", "4", "5")',
                    'serial_number' => 'varchar — серийный номер прибора (например, "109", "415", "151001")',
                    'case_number' => 'varchar — номер корпуса формата "NNN-YYYY" (например, "008-2024", "003-2023")',
                    'length_mm' => 'varchar — длина прибора в мм (числовое значение в виде строки)',
                    'diameter_mm' => 'varchar — диаметр в мм',
                    'upper_thread' => 'varchar — верхняя резьба (например, "МК-90")',
                    'lower_thread' => 'varchar — нижняя резьба',
                    'frequency1_khz' => 'varchar — рабочая частота 1 в кГц (например, "399.00")',
                    'frequency2_khz' => 'varchar — рабочая частота 2 в кГц (например, "1960.00")',
                    'record_date' => 'date — дата записи карточки',
                    'creator_lastname' => 'varchar — фамилия создателя записи',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP — момент создания',
                    'receiver_base1_mm' => 'varchar — база приёмника №1 в мм',
                    'receiver_base2_mm' => 'varchar — база приёмника №2',
                    'receiver_base3_mm' => 'varchar — база приёмника №3',
                    'changed_at' => 'datetime — момент последнего изменения карточки',
                    'transmitter_base1_mm' => 'varchar — база передатчика №1 в мм',
                    'transmitter_base2_mm' => 'varchar — база передатчика №2',
                    'transmitter_base3_mm' => 'varchar — база передатчика №3',
                    'transmitter_base4_mm' => 'varchar — база передатчика №4',
                    'transmitter_base5_mm' => 'varchar — база передатчика №5',
                    'additional_info' => 'text — свободные технические заметки',
                ],
            ],
            'device_history' => [
                'description' => 'Снимки device_identification на момент каждого изменения (журнал версий идентификации).',
                'notes' => 'Связана с device_identification только логически по device_id (FK не объявлен). record_date здесь имеет тип varchar (а не date, как в device_identification). Используйте для ответов на вопросы «когда у прибора менялись параметры» / «какая была частота в начале».',
                'fields' => [
                    'history_id' => 'int, PK, AUTO_INCREMENT',
                    'device_id' => 'int — ссылка на device_identification.id (без FK)',
                    'device_type' => 'varchar — тип прибора в момент снимка',
                    'nominal_diameter' => 'varchar',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'length_mm' => 'varchar',
                    'diameter_mm' => 'varchar',
                    'upper_thread' => 'varchar',
                    'lower_thread' => 'varchar',
                    'frequency1_khz' => 'varchar',
                    'frequency2_khz' => 'varchar',
                    'receiver_base1_mm' => 'varchar',
                    'receiver_base2_mm' => 'varchar',
                    'receiver_base3_mm' => 'varchar',
                    'record_date' => 'varchar — дата записи (хранится как строка)',
                    'creator_lastname' => 'varchar',
                    'changed_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP — момент фиксации снимка',
                    'transmitter_base1_mm' => 'varchar',
                    'transmitter_base2_mm' => 'varchar',
                    'transmitter_base3_mm' => 'varchar',
                    'transmitter_base4_mm' => 'varchar',
                    'transmitter_base5_mm' => 'varchar',
                    'additional_info' => 'text',
                ],
            ],
            'device_composition' => [
                'description' => 'Текущий состав прибора — список плат/модулей.',
                'notes' => 'Жёсткая связь device_id -> device_identification.id (ON DELETE CASCADE). creator_id — логически ссылается на users.id (FK не объявлен), creator_name — денормализованная копия ФИО.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'device_id' => 'int, NOT NULL — FK -> device_identification.id (ON DELETE CASCADE)',
                    'board_name' => 'varchar — название платы (например, "УВЧ приёмника", "Транзитная", "1DDS")',
                    'type' => 'varchar — тип/исполнение платы',
                    'version' => 'varchar — версия',
                    'serial_number' => 'varchar — серийный номер платы (часто "-")',
                    'creator_id' => 'int — ID пользователя-создателя записи (логически -> users.id)',
                    'creator_name' => 'varchar — ФИО создателя записи',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'device_composition_history' => [
                'description' => 'Снимки состава прибора при изменениях (журнал версий состава).',
                'notes' => 'Связана с device_identification по device_id логически (без FK).',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'device_id' => 'int, NOT NULL',
                    'board_name' => 'varchar',
                    'type' => 'varchar',
                    'version' => 'varchar',
                    'serial_number' => 'varchar',
                    'creator_id' => 'int — логически -> users.id',
                    'creator_name' => 'varchar',
                    'changed_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP — момент снимка',
                ],
            ],
            'repair_data' => [
                'description' => 'Записи о ремонтах прибора.',
                'notes' => 'creator_id — FK -> users.id (ON DELETE SET NULL, ON UPDATE CASCADE). repair_creator — денормализованная копия ФИО автора. С прибором связана по бизнес-ключу serial_number/case_number (без FK на device_identification). Признак «прибор ещё в ремонте» — release_date IS NULL OR release_date = "".',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'repair_reason' => 'text — причина обращения в ремонт',
                    'failure_reason' => 'text — причина неисправности (диагноз)',
                    'admission_date' => 'date — дата поступления в ремонт',
                    'release_date' => 'date — дата выпуска из ремонта (NULL/"" если ещё в ремонте)',
                    'actions_description' => 'text — выполненные действия',
                    'repair_creator' => 'varchar — ФИО ответственного за ремонт',
                    'creator_id' => 'int — FK -> users.id (SET NULL on delete, CASCADE on update)',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                    'serial_number' => 'varchar — соответствует device_identification.serial_number',
                    'case_number' => 'varchar — соответствует device_identification.case_number',
                ],
            ],
            'repair_data_history' => [
                'description' => 'Журнал изменений записей о ремонтах.',
                'notes' => 'repair_data_id — FK -> repair_data.id (ON DELETE/UPDATE CASCADE). changed_by_user_id — FK -> users.id (ON DELETE SET NULL, ON UPDATE CASCADE).',
                'fields' => [
                    'history_id' => 'int, PK, AUTO_INCREMENT',
                    'repair_data_id' => 'int, NOT NULL — FK -> repair_data.id (CASCADE)',
                    'repair_reason' => 'text',
                    'failure_reason' => 'text',
                    'admission_date' => 'date',
                    'release_date' => 'date',
                    'actions_description' => 'text',
                    'changed_by_user_id' => 'int — FK -> users.id (SET NULL)',
                    'changed_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'testing_data' => [
                'description' => 'Записи об испытаниях прибора с подробными замерами по каналам, передатчикам и частотам.',
                'notes' => 'Дата испытания фактически хранится в created_at (отдельного поля test_date НЕТ). Автор записи — поле creator (varchar), а не creator_surname. Кроме обзорных полей содержит ~60 числовых столбцов замеров (float). Имена столбцов замеров построены по правилу <metric>_<channel>_<frequency>: metric in {noise_sko_x, noise_sko_y, noise_sko_z, mds_transmitter, second_harmonic, third_harmonic, non_harmonic_components, phase_difference_drift}; channel — {1,2} для noise_sko_* (два набора шумовых каналов), {1..5} для остальных (по передатчикам); frequency in {400, 2000} (кГц). Имена этих столбцов совпадают с tolerances_*.column_name — благодаря этому можно сравнивать замер с допуском соединением через column_name.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'serial_number' => 'varchar(50), NOT NULL — серийный номер прибора',
                    'case_number' => 'varchar(50), NOT NULL — номер корпуса',
                    'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP — фактическая дата испытания',
                    'creator' => 'varchar, NOT NULL — ФИО ответственного за испытание',
                    'temperature_test' => 'text — описание/результаты термоиспытаний',
                    'vibration_test' => 'text — описание/результаты виброиспытаний',
                    'test_result' => 'varchar — итоговый результат («годен», «не годен», текстовая сводка)',
                    'noise_sko_<x|y|z><1|2>_<400|2000>' => 'float — СКО шумов по осям X/Y/Z, два канала, две частоты (12 столбцов)',
                    'mds_transmitter_<1..5>_<400|2000>' => 'float — МДС передатчика 1..5 на частотах 400/2000 (10 столбцов)',
                    'second_harmonic_<1..5>_<400|2000>' => 'float — вторая гармоника по передатчикам 1..5 (10 столбцов)',
                    'third_harmonic_<1..5>_<400|2000>' => 'float — третья гармоника (10 столбцов)',
                    'non_harmonic_components_<1..5>_<400|2000>' => 'float — негармонические компоненты (10 столбцов)',
                    'phase_difference_drift_<1..5>_<400|2000>' => 'float — дрейф фазы (10 столбцов)',
                ],
            ],
            'tolerances_lwd' => [
                'description' => 'Допуски по параметрам испытаний для приборов типа LWD.',
                'notes' => 'column_name — это имя соответствующего числового столбца в testing_data (например, "noise_sko_x1_400"). Используйте JOIN testing_data t ON t.<column_name из tolerances> для проверки соответствия замеров допускам.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'parameter' => 'varchar, NOT NULL — обобщённое название параметра (например, "noise_sko", "mds")',
                    'frequency' => 'int, NOT NULL — частота, к которой относится допуск (400 или 2000)',
                    'column_name' => 'varchar, NOT NULL — имя столбца в testing_data, которому соответствует допуск',
                    'min_value' => 'decimal(10,2) — нижняя граница допуска (NULL если не задана)',
                    'max_value' => 'decimal(10,2) — верхняя граница допуска (NULL если не задана)',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                    'updated_at' => 'timestamp ON UPDATE CURRENT_TIMESTAMP',
                ],
            ],
            'tolerances_cartographer' => [
                'description' => 'Допуски по параметрам испытаний для приборов типа «Картограф». Структура полей идентична tolerances_lwd.',
                'notes' => 'Применяется только к приборам с device_type = "Картограф".',
                'fields' => [
                    'id' => 'int, PK',
                    'parameter' => 'varchar',
                    'frequency' => 'int',
                    'column_name' => 'varchar — соответствует столбцу в testing_data',
                    'min_value' => 'decimal(10,2)',
                    'max_value' => 'decimal(10,2)',
                    'created_at' => 'timestamp',
                    'updated_at' => 'timestamp',
                ],
            ],
            'tolerances_autonomous' => [
                'description' => 'Допуски по параметрам испытаний для приборов типа «Автономный». Структура полей идентична tolerances_lwd.',
                'notes' => 'Применяется только к приборам с device_type = "Автономный".',
                'fields' => [
                    'id' => 'int, PK',
                    'parameter' => 'varchar',
                    'frequency' => 'int',
                    'column_name' => 'varchar',
                    'min_value' => 'decimal(10,2)',
                    'max_value' => 'decimal(10,2)',
                    'created_at' => 'timestamp',
                    'updated_at' => 'timestamp',
                ],
            ],
            'inspection_data' => [
                'description' => 'Записи о неразрушающем контроле корпуса прибора.',
                'notes' => 'Связь с прибором — по serial_number/case_number (без FK).',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'inspection_date' => 'date — дата контроля',
                    'inspection_result' => 'text — результат / описание',
                    'creator_surname' => 'varchar — фамилия создателя записи',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                ],
            ],
            'production_discontinuation' => [
                'description' => 'Записи о выходе прибора из производства.',
                'notes' => 'Связь с прибором — по serial_number/case_number (без FK). Один прибор может иметь несколько записей при возвратах.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'discontinuation_date' => 'date — дата выхода из производства',
                    'creator_surname' => 'varchar — фамилия создателя',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'termination_data' => [
                'description' => 'Записи о прекращении эксплуатации прибора.',
                'notes' => 'Связь с прибором — по serial_number/case_number (без FK).',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'termination_date' => 'date — дата прекращения эксплуатации',
                    'termination_reason' => 'text — причина',
                    'creator_surname' => 'varchar',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                ],
            ],
            'additional_data' => [
                'description' => 'Дополнительные сведения / свободные заметки по прибору.',
                'notes' => 'Связь с прибором — по serial_number/case_number (без FK).',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'additional_date' => 'date',
                    'additional_content' => 'text',
                    'creator_surname' => 'varchar',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'uploaded_files' => [
                'description' => 'Прикреплённые файлы (документы, фото) для конкретных приборов.',
                'notes' => 'Поле file_data (LONGBLOB) НИКОГДА не выбирайте в SELECT — это двоичные данные. Связь с прибором по serial_number/case_number/device_type (без FK).',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'field_name' => 'varchar — техническое имя поля формы',
                    'file_name' => 'varchar — оригинальное имя файла',
                    'file_data' => 'longblob — двоичные данные (НЕ выбирать в SELECT)',
                    'serial_number' => 'varchar',
                    'case_number' => 'varchar',
                    'device_type' => 'varchar — копия типа прибора',
                    'created_at' => 'datetime DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'users' => [
                'description' => 'Пользователи системы.',
                'notes' => 'Поле password — bcrypt-хэш ($2y$10$...), его НЕЛЬЗЯ выбирать в SELECT и упоминать в ответах. Признак администратора — is_admin = 1.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'username' => 'varchar, UNIQUE — логин',
                    'password' => 'varchar — bcrypt-хэш (ЗАПРЕЩЕНО выбирать)',
                    'firstname' => 'varchar — имя',
                    'lastname' => 'varchar — фамилия',
                    'middlename' => 'varchar — отчество',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                    'is_admin' => 'tinyint(1), NOT NULL DEFAULT 0 — 1 = администратор, 0 = обычный пользователь',
                ],
            ],
            'messages' => [
                'description' => 'Личные сообщения между пользователями.',
                'notes' => 'sender_id и receiver_id — FK -> users.id (ON DELETE CASCADE). Содержимое (message) — личная переписка; раскрывайте только когда пользователь явно запрашивает свои собственные сообщения. is_read: 0 = не прочитано, 1 = прочитано.',
                'fields' => [
                    'id' => 'int, PK, AUTO_INCREMENT',
                    'sender_id' => 'int, NOT NULL — FK -> users.id (CASCADE)',
                    'receiver_id' => 'int, NOT NULL — FK -> users.id (CASCADE)',
                    'message' => 'text, NOT NULL — содержимое сообщения',
                    'is_read' => 'tinyint(1) NOT NULL DEFAULT 0 — 0 не прочитано, 1 прочитано',
                    'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
        ];
    }

    /**
     * Связи между таблицами (жёсткие FK + бизнес-ключевые soft-связи)
     */
    private function renderRelations(): string
    {
        $out  = "=== СВЯЗИ МЕЖДУ ТАБЛИЦАМИ ===\n\n";

        $out .= "Жёсткие связи (FOREIGN KEY):\n";
        $out .= "  - device_composition.device_id            -> device_identification.id (ON DELETE CASCADE)\n";
        $out .= "  - messages.sender_id                      -> users.id (ON DELETE CASCADE)\n";
        $out .= "  - messages.receiver_id                    -> users.id (ON DELETE CASCADE)\n";
        $out .= "  - repair_data.creator_id                  -> users.id (ON DELETE SET NULL, ON UPDATE CASCADE)\n";
        $out .= "  - repair_data_history.repair_data_id      -> repair_data.id (ON DELETE CASCADE, ON UPDATE CASCADE)\n";
        $out .= "  - repair_data_history.changed_by_user_id  -> users.id (ON DELETE SET NULL, ON UPDATE CASCADE)\n\n";

        $out .= "Логические связи (без FK), используемые при JOIN:\n";
        $out .= "  - device_composition_history.device_id  -> device_identification.id\n";
        $out .= "  - device_history.device_id              -> device_identification.id\n";
        $out .= "  - device_composition.creator_id         -> users.id\n";
        $out .= "  - device_composition_history.creator_id -> users.id\n\n";

        $out .= "Связь по бизнес-ключу (serial_number и/или case_number) — основной способ собрать «жизненный цикл» прибора.\n";
        $out .= "Эти таблицы соединяются с device_identification через ON di.serial_number = t.serial_number AND di.case_number = t.case_number:\n";
        $out .= "  - repair_data, repair_data_history (через repair_data)\n";
        $out .= "  - testing_data\n";
        $out .= "  - inspection_data\n";
        $out .= "  - production_discontinuation\n";
        $out .= "  - termination_data\n";
        $out .= "  - additional_data\n";
        $out .= "  - uploaded_files (дополнительно дублируется device_type)\n\n";

        $out .= "Связь testing_data <-> tolerances_*:\n";
        $out .= "  - Имя числового столбца testing_data (например, noise_sko_x1_400) совпадает с tolerances_<type>.column_name.\n";
        $out .= "  - Выбор таблицы допусков зависит от device_identification.device_type:\n";
        $out .= "      'LWD'        -> tolerances_lwd\n";
        $out .= "      'Картограф'  -> tolerances_cartographer\n";
        $out .= "      'Автономный' -> tolerances_autonomous\n\n";

        return $out;
    }

    /**
     * Перечисления значений (de-facto enum'ы)
     */
    private function renderEnumerations(): string
    {
        $out  = "=== ПЕРЕЧИСЛЕНИЯ ЗНАЧЕНИЙ ===\n\n";
        $out .= "device_identification.device_type:       'LWD' | 'Картограф' | 'Автономный'\n";
        $out .= "device_identification.transmitter_count: '3' | '4' | '5' (хранится строкой)\n";
        $out .= "device_identification.nominal_diameter:  '90' | '120' | ... (хранится строкой)\n";
        $out .= "tolerances_*.frequency:                  400 | 2000 (int, кГц)\n";
        $out .= "users.is_admin:                          0 | 1\n";
        $out .= "messages.is_read:                        0 (не прочитано) | 1 (прочитано)\n";
        $out .= "repair_data.release_date IS NULL OR = '': прибор всё ещё в ремонте\n\n";
        return $out;
    }

    /**
     * Запреты и ограничения, обязательные при формировании SQL и ответов
     */
    private function renderForbidden(): string
    {
        $out  = "=== ЗАПРЕТЫ И ОГРАНИЧЕНИЯ ===\n\n";
        $out .= "1. Разрешены ТОЛЬКО SELECT-запросы. Никаких INSERT/UPDATE/DELETE/DDL.\n";
        $out .= "2. ЗАПРЕЩЕНО включать в SELECT-список и в ответы пользователю:\n";
        $out .= "   - users.password (bcrypt-хэш)\n";
        $out .= "   - uploaded_files.file_data (LONGBLOB, двоичные данные файла)\n";
        $out .= "3. messages.message — личная переписка. Не раскрывайте сообщения сторонних пользователей; обращайтесь к ним только если сам пользователь явно спрашивает свою переписку.\n";
        $out .= "4. При выводе пользователей используйте только публичные поля: id, username, firstname, lastname, middlename, is_admin, created_at.\n";
        $out .= "5. Никогда не придумывайте данные. Если по запросу ничего не найдено — так и сообщите и попросите уточнения.\n\n";
        return $out;
    }

    /**
     * Антипаттерны JOIN, из-за которых аналитические запросы возвращают 0
     * (или Unknown column). Lifecycle-таблицы соединяются с device_identification
     * ТОЛЬКО по бизнес-ключу (serial_number, case_number) — у них нет device_id
     * и нет device_type.
     */
    private function renderJoinAntipatterns(): string
    {
        $out  = "=== ТИПИЧНЫЕ ОШИБКИ JOIN (НЕ ПОВТОРЯТЬ) ===\n\n";

        $out .= "Lifecycle-таблицы НЕ ИМЕЮТ колонок device_id и device_type.\n";
        $out .= "К ним относятся: repair_data, repair_data_history, testing_data,\n";
        $out .= "inspection_data, production_discontinuation, termination_data,\n";
        $out .= "additional_data, uploaded_files. Связь с device_identification —\n";
        $out .= "ТОЛЬКО по бизнес-ключу (serial_number, case_number).\n\n";

        $out .= "ЗАПРЕЩЕННЫЕ JOIN (вернут 0 строк или 'Unknown column'):\n";
        $out .= "  - LEFT JOIN repair_data r ON r.device_id = di.id\n";
        $out .= "  - LEFT JOIN repair_data r ON r.id = di.id\n";
        $out .= "  - LEFT JOIN repair_data r ON r.device_type = di.device_type\n";
        $out .= "  - WHERE r.device_type = 'LWD'  -- такого поля в repair_data нет\n";
        $out .= "  - JOIN testing_data t ON t.device_id = di.id\n\n";

        $out .= "ЕДИНСТВЕННО ВЕРНЫЙ JOIN для разреза \"ремонты по типу прибора\":\n";
        $out .= "  SELECT di.device_type, COUNT(r.id) AS repairs_count\n";
        $out .= "  FROM device_identification di\n";
        $out .= "  LEFT JOIN repair_data r\n";
        $out .= "         ON r.serial_number = di.serial_number\n";
        $out .= "        AND r.case_number   = di.case_number\n";
        $out .= "  GROUP BY di.device_type;\n\n";

        $out .= "Исключение — device_composition: для НЕЁ JOIN идёт по device_id:\n";
        $out .= "  device_composition.device_id -> device_identification.id (FK).\n";
        $out .= "Только эта приборная таблица имеет hard-FK по id.\n\n";

        $out .= "Если LEFT JOIN на lifecycle-таблицу даёт 0 совпадений на ВСЕХ группах,\n";
        $out .= "это почти всегда означает, что использован неверный ключ соединения.\n";
        $out .= "Перепроверьте JOIN-условие против блока === СВЯЗИ МЕЖДУ ТАБЛИЦАМИ ===.\n\n";

        return $out;
    }

    /**
     * Правила поиска прибора и сборки полного жизненного цикла
     */
    private function renderSearchRules(): string
    {
        $out  = "=== ПРАВИЛА ПОИСКА ===\n\n";
        $out .= "1. Прибор однозначно определяется парой (serial_number, case_number). Если пользователь указал только один из них, ищите по нему.\n";
        $out .= "2. Состав прибора — JOIN по device_composition.device_id = device_identification.id (это единственная hard-FK для приборных таблиц).\n";
        $out .= "3. Все остальные lifecycle-таблицы соединяются с device_identification по бизнес-ключу:\n";
        $out .= "      ON t.serial_number = di.serial_number AND t.case_number = di.case_number\n";
        $out .= "4. История идентификации/состава — таблицы device_history и device_composition_history по device_id.\n";
        $out .= "5. История ремонта по конкретному ремонту — repair_data_history по repair_data_id.\n";
        $out .= "6. Чтобы оценить «прошёл ли прибор испытания по допускам»: возьмите device_type, выберите соответствующую таблицу tolerances_*, соедините по column_name с числовыми столбцами testing_data, проверьте попадание значения в [min_value; max_value].\n";
        $out .= "7. Числовые поля device_identification (length_mm, frequency*_khz, *_base*_mm, transmitter_count) хранятся строкой. Для числовых сравнений используйте CAST: например, WHERE CAST(frequency1_khz AS DECIMAL(10,2)) >= 399.\n\n";
        return $out;
    }

    /**
     * Расширенные примеры SQL — образцы для типовых сложных вопросов
     */
    private function renderQueryPatterns(): string
    {
        $out  = "=== ПРИМЕРЫ SQL ===\n\n";

        $out .= "-- 1) Количество приборов по типам:\n";
        $out .= "SELECT device_type, COUNT(*) AS count\n";
        $out .= "FROM device_identification\n";
        $out .= "GROUP BY device_type\n";
        $out .= "ORDER BY count DESC;\n\n";

        $out .= "-- 2) Базовая карточка прибора по серийному номеру:\n";
        $out .= "SELECT id, device_type, serial_number, case_number, nominal_diameter,\n";
        $out .= "       transmitter_count, length_mm, diameter_mm,\n";
        $out .= "       frequency1_khz, frequency2_khz, creator_lastname, record_date\n";
        $out .= "FROM device_identification\n";
        $out .= "WHERE serial_number = '109';\n\n";

        $out .= "-- 3) Состав прибора:\n";
        $out .= "SELECT dc.board_name, dc.type, dc.version, dc.serial_number AS board_serial,\n";
        $out .= "       dc.creator_name, dc.created_at\n";
        $out .= "FROM device_composition dc\n";
        $out .= "INNER JOIN device_identification di ON dc.device_id = di.id\n";
        $out .= "WHERE di.serial_number = '109'\n";
        $out .= "ORDER BY dc.id;\n\n";

        $out .= "-- 4) История ремонтов прибора (через бизнес-ключ):\n";
        $out .= "SELECT r.admission_date, r.release_date, r.repair_reason, r.failure_reason,\n";
        $out .= "       r.actions_description, r.repair_creator\n";
        $out .= "FROM repair_data r\n";
        $out .= "WHERE r.serial_number = '109' AND r.case_number = '008-2024'\n";
        $out .= "ORDER BY r.admission_date DESC;\n\n";

        $out .= "-- 5) Приборы, находящиеся в ремонте сейчас (release_date не проставлена):\n";
        $out .= "SELECT r.serial_number, r.case_number, r.repair_reason, r.admission_date, r.repair_creator\n";
        $out .= "FROM repair_data r\n";
        $out .= "WHERE r.release_date IS NULL OR r.release_date = ''\n";
        $out .= "ORDER BY r.admission_date DESC;\n\n";

        $out .= "-- 6) Сколько ремонтов было у каждого прибора (топ-10 «проблемных»):\n";
        $out .= "SELECT di.serial_number, di.case_number, di.device_type, COUNT(r.id) AS repairs_count\n";
        $out .= "FROM device_identification di\n";
        $out .= "LEFT JOIN repair_data r\n";
        $out .= "       ON r.serial_number = di.serial_number AND r.case_number = di.case_number\n";
        $out .= "GROUP BY di.id, di.serial_number, di.case_number, di.device_type\n";
        $out .= "ORDER BY repairs_count DESC, di.serial_number\n";
        $out .= "LIMIT 10;\n\n";

        $out .= "-- 7) Последнее испытание прибора (created_at используется как дата испытания):\n";
        $out .= "SELECT t.id, t.serial_number, t.case_number, t.creator, t.created_at AS test_date,\n";
        $out .= "       t.test_result, t.temperature_test, t.vibration_test\n";
        $out .= "FROM testing_data t\n";
        $out .= "WHERE t.serial_number = '109' AND t.case_number = '008-2024'\n";
        $out .= "ORDER BY t.created_at DESC\n";
        $out .= "LIMIT 1;\n\n";

        $out .= "-- 8) Допуски для приборов LWD на частоте 400 кГц (для проверки соответствия испытаний):\n";
        $out .= "SELECT tl.parameter, tl.frequency, tl.column_name, tl.min_value, tl.max_value\n";
        $out .= "FROM tolerances_lwd tl\n";
        $out .= "WHERE tl.frequency = 400\n";
        $out .= "ORDER BY tl.parameter, tl.column_name;\n";
        $out .= "-- Затем сравнить min_value/max_value со значением соответствующего\n";
        $out .= "-- столбца в testing_data (например, t.noise_sko_x1_400) для конкретной записи испытания.\n\n";

        $out .= "-- 9) Полный жизненный цикл прибора одним запросом (агрегаты по lifecycle-таблицам):\n";
        $out .= "SELECT di.serial_number, di.case_number, di.device_type,\n";
        $out .= "       (SELECT COUNT(*) FROM repair_data r\n";
        $out .= "          WHERE r.serial_number = di.serial_number\n";
        $out .= "            AND r.case_number = di.case_number) AS repairs_count,\n";
        $out .= "       (SELECT COUNT(*) FROM testing_data t\n";
        $out .= "          WHERE t.serial_number = di.serial_number\n";
        $out .= "            AND t.case_number = di.case_number) AS tests_count,\n";
        $out .= "       (SELECT COUNT(*) FROM inspection_data i\n";
        $out .= "          WHERE i.serial_number = di.serial_number\n";
        $out .= "            AND i.case_number = di.case_number) AS inspections_count,\n";
        $out .= "       (SELECT MAX(termination_date) FROM termination_data tr\n";
        $out .= "          WHERE tr.serial_number = di.serial_number\n";
        $out .= "            AND tr.case_number = di.case_number) AS last_termination_date\n";
        $out .= "FROM device_identification di\n";
        $out .= "WHERE di.serial_number = '109';\n\n";

        $out .= "-- 10) История изменений идентификации прибора:\n";
        $out .= "SELECT dh.changed_at, dh.serial_number, dh.case_number,\n";
        $out .= "       dh.length_mm, dh.diameter_mm, dh.frequency1_khz, dh.frequency2_khz,\n";
        $out .= "       dh.creator_lastname\n";
        $out .= "FROM device_history dh\n";
        $out .= "WHERE dh.device_id = (SELECT id FROM device_identification WHERE serial_number = '109' LIMIT 1)\n";
        $out .= "ORDER BY dh.changed_at;\n\n";

        $out .= "-- 11) Кто из пользователей зарегистрировал больше всего ремонтов:\n";
        $out .= "SELECT u.id, u.lastname, u.firstname, u.middlename, COUNT(r.id) AS repairs_logged\n";
        $out .= "FROM users u\n";
        $out .= "INNER JOIN repair_data r ON r.creator_id = u.id\n";
        $out .= "GROUP BY u.id, u.lastname, u.firstname, u.middlename\n";
        $out .= "ORDER BY repairs_logged DESC\n";
        $out .= "LIMIT 10;\n\n";

        $out .= "-- 12) Прикреплённые файлы по прибору (БЕЗ file_data):\n";
        $out .= "SELECT id, field_name, file_name, device_type, created_at\n";
        $out .= "FROM uploaded_files\n";
        $out .= "WHERE serial_number = '109' AND case_number = '008-2024'\n";
        $out .= "ORDER BY created_at DESC;\n\n";

        return $out;
    }

    /**
     * Получает статистику БД для контекста
     */
    public function getDatabaseStats(): array
    {
        $stats = [];

        try {
            // Количество приборов
            $result = $this->conn->query("SELECT COUNT(*) as count FROM device_identification");
            $stats['total_devices'] = $result->fetch_assoc()['count'];

            // Количество по типам
            $result = $this->conn->query("SELECT device_type, COUNT(*) as count FROM device_identification GROUP BY device_type");
            $stats['by_type'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['by_type'][$row['device_type']] = $row['count'];
            }

            // Количество ремонтов
            $result = $this->conn->query("SELECT COUNT(*) as count FROM repair_data");
            $stats['total_repairs'] = $result->fetch_assoc()['count'];

            // Диапазон серийных номеров
            $result = $this->conn->query("SELECT MIN(CAST(serial_number AS UNSIGNED)) as min_serial, MAX(CAST(serial_number AS UNSIGNED)) as max_serial FROM device_identification WHERE serial_number REGEXP '^[0-9]+$'");
            $row = $result->fetch_assoc();
            $stats['serial_range'] = $row['min_serial'] . ' - ' . $row['max_serial'];

        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Выполняет SQL запрос и возвращает результат для LLM
     */
    public function executeQueryForLLM(string $sql): array
    {
        try {
            $result = $this->conn->query($sql);
            if (!$result) {
                return ['success' => false, 'error' => $this->conn->error];
            }

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return ['success' => true, 'data' => $data, 'count' => count($data)];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
