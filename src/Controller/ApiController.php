<?php
namespace App\Controller;

use App\Controller\BaseController;
use App\Service\ApiTokenService;

/**
 * API для приложения «Визуализация данных».
 *
 * Отличие от остальных контроллеров: авторизация только по токену, и любая
 * неудача возвращается как JSON с корректным кодом ответа. Сессионный
 * requireAuth() здесь неприменим — он делает редирект на index.php, и
 * настольный клиент получил бы HTML вместо ошибки.
 *
 * Маршруты: front.php?c=Api&a=<действие>
 */
class ApiController extends BaseController
{
    /** Предел размера загружаемого файла. */
    const MAX_UPLOAD_BYTES = 20971520; // 20 МБ

    /**
     * Приложение отправляет только файлы данных прибора и файлы метрологии.
     * Отчёты и документы по-прежнему загружаются через сайт.
     */
    const ALLOWED_EXTENSIONS = ['bin', 'biv', 'dev'];

    /** Разделы карточки прибора, куда допускается загрузка. */
    const ALLOWED_FIELDS = ['thermoprofile', 'metrology', 'calibration', 'kis'];

    /**
     * Соответствие типов прибора: код приложения → значение в базе.
     * Картограф распознаётся приложением как LWD-совместимый, поэтому тип
     * обязан приходить отдельным кодом, а не выводиться из вида данных.
     */
    const DEVICE_TYPE_MAP = [
        'autonom'    => 'Автономный',
        'автоном'    => 'Автономный',
        'автономный' => 'Автономный',
        'lwd'        => 'LWD',
        'cartograph' => 'Картограф',
        'картограф'  => 'Картограф',
    ];

    /** @var array|null строка действующего токена вместе с данными пользователя */
    private $tokenRow = null;

    /** @var \mysqli|null */
    private $connection = null;

    // ------------------------------------------------------------------
    // Действия
    // ------------------------------------------------------------------

    /**
     * Проверка связи и токена. Приложение показывает имя пользователя,
     * чтобы было видно, под чьей учётной записью пойдут загрузки.
     */
    public function whoami(): void
    {
        $this->requireToken();

        $this->jsonResponse([
            'success' => true,
            'user' => [
                'id'        => (int)$this->tokenRow['user_id'],
                'username'  => (string)$this->tokenRow['username'],
                'full_name' => ApiTokenService::describeUser($this->tokenRow),
                'is_admin'  => (bool)$this->tokenRow['is_admin'],
            ],
            'token' => [
                'name'       => (string)$this->tokenRow['name'],
                'created_at' => (string)$this->tokenRow['created_at'],
            ],
            'server_time' => date('c'),
        ]);
    }

    /**
     * Справочники, чтобы приложение не хранило собственную копию соответствий.
     */
    public function dictionaries(): void
    {
        $this->requireToken();

        $this->jsonResponse([
            'success' => true,
            'device_types' => [
                'Autonom'    => 'Автономный',
                'Lwd'        => 'LWD',
                'Cartograph' => 'Картограф',
            ],
            'file_kinds' => [
                'thermoprofile' => 'Термопрофиль + Воздух',
                'metrology'     => 'Метрология',
                'calibration'   => 'Калибровка',
                'kis'           => 'КИС',
            ],
            'max_upload_bytes'   => self::MAX_UPLOAD_BYTES,
            'allowed_extensions' => self::ALLOWED_EXTENSIONS,
        ]);
    }

    /**
     * Определение прибора по серийному номеру.
     *
     * Серийный номер уникален, поэтому приложению достаточно его и типа.
     * Номер корпуса в приборе не хранится и берётся только отсюда: если
     * подставить его самостоятельно, файл попадёт в базу под тройкой, которой
     * нет ни на одной карточке, и на сайте виден не будет.
     */
    public function deviceResolve(): void
    {
        $this->requireToken();
        $this->requirePost();

        $serial = $this->plainString($_POST['serial_number'] ?? '');
        if ($serial === '') {
            $this->fail('bad_request', 'Не указан серийный номер.', 400);
        }

        $requestedType = $this->normalizeDeviceType($_POST['device_type'] ?? '');
        $requestedTx   = isset($_POST['transmitter_count'])
            ? (int)$_POST['transmitter_count']
            : 0;

        $conn = $this->db();
        $stmt = $conn->prepare(
            "SELECT id, device_type, serial_number, case_number, nominal_diameter,
                    transmitter_count, length_mm, frequency1_khz, frequency2_khz
             FROM device_identification
             WHERE TRIM(serial_number) = ?
             ORDER BY id
             LIMIT 20"
        );
        if (!$stmt) {
            $this->fail('server_error', 'Не удалось выполнить запрос к базе.', 500);
        }

        $stmt->bind_param('s', $serial);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (count($rows) === 0) {
            $this->fail(
                'device_not_found',
                'Прибор с серийным номером ' . $serial . ' не найден в базе.',
                404
            );
        }

        if (count($rows) > 1) {
            $this->jsonResponse([
                'success' => false,
                'code'    => 'device_ambiguous',
                'message' => 'В базе найдено несколько приборов с серийным номером ' . $serial . '.',
                'devices' => array_map([$this, 'formatDevice'], $rows),
            ], 409);
        }

        $device = $rows[0];
        $warnings = [];

        if ($requestedType !== '' && $device['device_type'] !== $requestedType) {
            $warnings[] = 'Тип прибора в базе — «' . $device['device_type'] .
                '», прибор сообщает «' . $requestedType . '».';
        }

        if ($requestedTx > 0 && (int)$device['transmitter_count'] !== $requestedTx) {
            $warnings[] = 'Число передатчиков в базе — ' . (int)$device['transmitter_count'] .
                ', прибор сообщает ' . $requestedTx . '.';
        }

        $this->jsonResponse([
            'success'  => true,
            'device'   => $this->formatDevice($device),
            'warnings' => $warnings,
        ]);
    }

    /**
     * Поиск приборов по типу и числу передатчиков.
     *
     * Нужен для ранее записанных файлов данных: серийного номера в кадре
     * измерений нет, есть только сигнатура с типом и числом передатчиков.
     */
    public function deviceSearch(): void
    {
        $this->requireToken();
        $this->requirePost();

        $type = $this->normalizeDeviceType($_POST['device_type'] ?? '');
        $tx   = isset($_POST['transmitter_count']) ? (int)$_POST['transmitter_count'] : 0;

        $conn = $this->db();
        $sql = "SELECT id, device_type, serial_number, case_number, nominal_diameter,
                       transmitter_count, length_mm, frequency1_khz, frequency2_khz
                FROM device_identification";
        $conditions = [];
        $params = [];
        $types = '';

        if ($type !== '') {
            $conditions[] = 'device_type = ?';
            $params[] = $type;
            $types .= 's';
        }

        if ($tx > 0) {
            $conditions[] = 'CAST(transmitter_count AS UNSIGNED) = ?';
            $params[] = $tx;
            $types .= 'i';
        }

        if (count($conditions) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY device_type, serial_number LIMIT 200';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $this->fail('server_error', 'Не удалось выполнить запрос к базе.', 500);
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $this->jsonResponse([
            'success' => true,
            'devices' => array_map([$this, 'formatDevice'], $rows),
        ]);
    }

    /**
     * Загрузка файла в карточку прибора.
     */
    public function fileUpload(): void
    {
        $this->requireToken();
        $this->requirePost();

        $fieldName = $this->plainString($_POST['field_name'] ?? '', 32);
        if (!in_array($fieldName, self::ALLOWED_FIELDS, true)) {
            $this->fail('bad_request', 'Недопустимый раздел карточки прибора.', 400);
        }

        $serial     = $this->plainString($_POST['serial_number'] ?? '');
        $caseNumber = $this->plainString($_POST['case_number'] ?? '');
        $deviceType = $this->normalizeDeviceType($_POST['device_type'] ?? '');

        if ($serial === '' || $caseNumber === '' || $deviceType === '') {
            $this->fail('bad_request', 'Не указан прибор: нужны серийный номер, номер корпуса и тип.', 400);
        }

        if (!isset($_FILES['file'])) {
            $this->fail('bad_request', 'Файл не передан.', 400);
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->fail('upload_error', $this->describeUploadError((int)$file['error']), 400);
        }

        if ((int)$file['size'] > self::MAX_UPLOAD_BYTES) {
            $this->fail(
                'file_too_large',
                'Размер файла превышает ' . (int)(self::MAX_UPLOAD_BYTES / 1048576) . ' МБ.',
                413
            );
        }

        $fileName = $this->sanitizeFileName($_POST['file_name'] ?? $file['name']);
        if ($fileName === '') {
            $this->fail('bad_request', 'Недопустимое имя файла.', 400);
        }

        $extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->fail(
                'bad_extension',
                'Через приложение загружаются только файлы данных прибора: ' .
                implode(', ', self::ALLOWED_EXTENSIONS) . '.',
                400
            );
        }

        $fileData = file_get_contents($file['tmp_name']);
        if ($fileData === false) {
            $this->fail('server_error', 'Не удалось прочитать переданный файл.', 500);
        }

        if (strlen($fileData) === 0) {
            $this->fail('bad_request', 'Передан пустой файл.', 400);
        }

        $sha256 = hash('sha256', $fileData);
        $declared = strtolower($this->plainString($_POST['sha256'] ?? '', 64));
        if ($declared !== '' && $declared !== $sha256) {
            $this->fail('checksum_mismatch', 'Контрольная сумма не совпала: файл повреждён при передаче.', 400);
        }

        $conn = $this->db();
        $this->ensureUploadedFilesSchema($conn);

        // Повтор той же кнопки, возврат к тому же файлу через неделю, отправка
        // метрологии после каждой из нескольких калибровок подряд — всё это
        // штатные сценарии, и каждый из них иначе создавал бы полную копию.
        $existing = $this->findDuplicate($conn, $sha256, $serial, $caseNumber, $deviceType, $fieldName);
        $force = isset($_POST['force']) && (string)$_POST['force'] === '1';
        if ($existing !== null && !$force) {
            $this->jsonResponse([
                'success'  => false,
                'code'     => 'duplicate',
                'message'  => 'Такой файл уже загружен ' . $this->formatDate($existing['created_at']) . '.',
                'existing' => [
                    'id'         => (int)$existing['id'],
                    'file_name'  => (string)$existing['file_name'],
                    'created_at' => (string)$existing['created_at'],
                ],
            ], 409);
        }

        if (!ApiTokenService::consumeRateLimit($conn, (int)$this->tokenRow['id'])) {
            $this->fail(
                'rate_limited',
                'Превышен лимит загрузок: не более ' . ApiTokenService::RATE_LIMIT_PER_HOUR . ' файлов в час.',
                429
            );
        }

        $stmt = $conn->prepare(
            "INSERT INTO uploaded_files
                (field_name, file_name, file_data, serial_number, case_number, device_type, sha256, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmt) {
            $this->fail('server_error', 'Не удалось подготовить запрос на сохранение файла.', 500);
        }

        $null = null;
        $stmt->bind_param('sssssss', $fieldName, $fileName, $null, $serial, $caseNumber, $deviceType, $sha256);
        $stmt->send_long_data(2, $fileData);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            $this->logSecurityEvent('api_file_upload_failed', [
                'user_id'   => (int)$this->tokenRow['user_id'],
                'file_name' => $fileName,
                'error'     => $error,
            ]);
            $this->fail('server_error', 'Не удалось сохранить файл в базу.', 500);
        }

        $insertedId = (int)$conn->insert_id;
        $stmt->close();

        ApiTokenService::touch($conn, (int)$this->tokenRow['id']);
        $this->logSecurityEvent('api_file_uploaded', [
            'user_id'       => (int)$this->tokenRow['user_id'],
            'field_name'    => $fieldName,
            'file_name'     => $fileName,
            'file_size'     => strlen($fileData),
            'serial_number' => $serial,
            'case_number'   => $caseNumber,
            'device_type'   => $deviceType,
        ]);

        $this->jsonResponse([
            'success' => true,
            'file' => [
                'id'         => $insertedId,
                'file_name'  => $fileName,
                'field_name' => $fieldName,
                'size'       => strlen($fileData),
                'sha256'     => $sha256,
            ],
            'message' => 'Файл загружен в карточку прибора.',
        ]);
    }

    /**
     * Список уже загруженных файлов раздела — история для окна загрузки.
     */
    public function fileList(): void
    {
        $this->requireToken();
        $this->requirePost();

        $fieldName  = $this->plainString($_POST['field_name'] ?? '', 32);
        $serial     = $this->plainString($_POST['serial_number'] ?? '');
        $caseNumber = $this->plainString($_POST['case_number'] ?? '');
        $deviceType = $this->normalizeDeviceType($_POST['device_type'] ?? '');

        if (!in_array($fieldName, self::ALLOWED_FIELDS, true)) {
            $this->fail('bad_request', 'Недопустимый раздел карточки прибора.', 400);
        }

        if ($serial === '' || $caseNumber === '' || $deviceType === '') {
            $this->fail('bad_request', 'Не указан прибор.', 400);
        }

        $conn = $this->db();
        $this->ensureUploadedFilesSchema($conn);

        $stmt = $conn->prepare(
            "SELECT id, file_name, created_at, sha256, LENGTH(file_data) AS size
             FROM uploaded_files
             WHERE serial_number = ? AND case_number = ? AND device_type = ? AND field_name = ?
             ORDER BY created_at DESC
             LIMIT 50"
        );
        if (!$stmt) {
            $this->fail('server_error', 'Не удалось выполнить запрос к базе.', 500);
        }

        $stmt->bind_param('ssss', $serial, $caseNumber, $deviceType, $fieldName);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $files = [];
        foreach ($rows as $row) {
            $files[] = [
                'id'         => (int)$row['id'],
                'file_name'  => (string)$row['file_name'],
                'created_at' => (string)$row['created_at'],
                'size'       => (int)$row['size'],
                'sha256'     => (string)($row['sha256'] ?? ''),
            ];
        }

        $this->jsonResponse(['success' => true, 'files' => $files]);
    }

    // ------------------------------------------------------------------
    // Внутренние помощники
    // ------------------------------------------------------------------

    /**
     * Авторизация по токену. В отличие от сессионной, всегда отвечает JSON.
     */
    private function requireToken(): void
    {
        $token = ApiTokenService::extractToken();
        if ($token === '') {
            $this->fail('unauthorized', 'Не передан токен доступа.', 401);
        }

        $row = ApiTokenService::findValid($this->db(), $token);
        if ($row === null) {
            $this->logSecurityEvent('api_invalid_token', ['ip' => $this->getClientIP()]);
            $this->fail('unauthorized', 'Токен недействителен или отозван.', 401);
        }

        $this->tokenRow = $row;
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->fail('bad_method', 'Требуется метод POST.', 405);
        }
    }

    /**
     * @return \mysqli
     */
    private function db()
    {
        if ($this->connection === null) {
            require_once __DIR__ . '/../../db_connection.php';
            // require_once не выполняет файл повторно, поэтому $conn в этой
            // области может отсутствовать — тогда открываем соединение сами.
            if (isset($conn) && $conn instanceof \mysqli) {
                $this->connection = $conn;
            } elseif (function_exists('createDatabaseConnection')) {
                $this->connection = createDatabaseConnection();
            } else {
                $this->fail('server_error', 'Нет подключения к базе данных.', 500);
            }
        }

        return $this->connection;
    }

    private function fail(string $code, string $message, int $httpCode): void
    {
        $this->jsonResponse([
            'success' => false,
            'code'    => $code,
            'message' => $message,
        ], $httpCode);
    }

    /**
     * Добавляет колонку контрольной суммы, если её ещё нет.
     */
    private function ensureUploadedFilesSchema(\mysqli $conn): void
    {
        $result = $conn->query("SHOW COLUMNS FROM `uploaded_files` LIKE 'sha256'");
        if ($result && $result->num_rows > 0) {
            return;
        }

        $conn->query("ALTER TABLE `uploaded_files` ADD COLUMN `sha256` char(64) DEFAULT NULL");
        $conn->query("ALTER TABLE `uploaded_files` ADD INDEX `idx_sha256` (`sha256`)");
    }

    /**
     * @return array|null
     */
    private function findDuplicate(
        \mysqli $conn,
        string $sha256,
        string $serial,
        string $caseNumber,
        string $deviceType,
        string $fieldName
    ) {
        $stmt = $conn->prepare(
            "SELECT id, file_name, created_at
             FROM uploaded_files
             WHERE sha256 = ? AND serial_number = ? AND case_number = ?
               AND device_type = ? AND field_name = ?
             ORDER BY created_at DESC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('sssss', $sha256, $serial, $caseNumber, $deviceType, $fieldName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private function formatDevice(array $row): array
    {
        return [
            'id'                => (int)$row['id'],
            'device_type'       => (string)$row['device_type'],
            'serial_number'     => (string)$row['serial_number'],
            'case_number'       => (string)$row['case_number'],
            'nominal_diameter'  => (string)($row['nominal_diameter'] ?? ''),
            'transmitter_count' => (string)($row['transmitter_count'] ?? ''),
            'length_mm'         => (string)($row['length_mm'] ?? ''),
            'frequency1_khz'    => (string)($row['frequency1_khz'] ?? ''),
            'frequency2_khz'    => (string)($row['frequency2_khz'] ?? ''),
        ];
    }

    /**
     * Приводит код типа прибора к значению, которое хранится в базе.
     * Неизвестное значение возвращается как есть — база остаётся источником
     * истины, а приложение получит понятное сообщение о ненайденном приборе.
     */
    private function normalizeDeviceType($value): string
    {
        $raw = $this->plainString($value);
        if ($raw === '') {
            return '';
        }

        $key = function_exists('mb_strtolower') ? mb_strtolower($raw, 'UTF-8') : strtolower($raw);

        return isset(self::DEVICE_TYPE_MAP[$key]) ? self::DEVICE_TYPE_MAP[$key] : $raw;
    }

    /**
     * Обрезка и очистка от управляющих символов без HTML-экранирования:
     * значения сравниваются с тем, что уже лежит в базе.
     */
    private function plainString($value, int $maxLength = 255): string
    {
        if (!is_string($value)) {
            return '';
        }

        $clean = (string)preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $clean = function_exists('mb_substr')
            ? mb_substr($clean, 0, $maxLength, 'UTF-8')
            : substr($clean, 0, $maxLength);

        return trim($clean);
    }

    private function sanitizeFileName($value): string
    {
        $name = basename($this->plainString($value, 255));
        if ($name === '' || $name === '.' || $name === '..') {
            return '';
        }

        if (!preg_match('/^[\p{L}\p{N}\s\.\-_()\[\]]+$/u', $name)) {
            return '';
        }

        return $name;
    }

    private function describeUploadError(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'Файл превышает допустимый размер загрузки на сервере.';
            case UPLOAD_ERR_PARTIAL:
                return 'Файл передан не полностью.';
            case UPLOAD_ERR_NO_FILE:
                return 'Файл не передан.';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                return 'Сервер не смог сохранить временный файл.';
            default:
                return 'Ошибка передачи файла (код ' . $code . ').';
        }
    }

    private function formatDate($value): string
    {
        $timestamp = strtotime((string)$value);

        return $timestamp ? date('d.m.Y H:i', $timestamp) : (string)$value;
    }
}
