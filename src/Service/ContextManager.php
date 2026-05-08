<?php
namespace App\Service;

/**
 * Управляет контекстом диалога ИИ-агента.
 *
 * Контекст хранится не как произвольный текст ответа, а как стек структурированных
 * фреймов: успешный ответ кладет в сессию тему, найденные приборы, типы приборов,
 * SQL-результаты и краткое описание. Это позволяет последующим запросам ссылаться
 * на "данный прибор", "первый из списка", "эти приборы", "этот тип" и т.п.
 */
class ContextManager
{
    private const DEVICE_CONTEXT_KEY = 'current_device_context';
    private const FRAMES_KEY = 'ai_context_frames';
    private const FRAME_TTL = 1800;
    private const MAX_FRAMES = 12;

    /**
     * Старый fallback: извлекает прибор из текста и кладет его в структурный фрейм.
     */
    public function extractAndSaveContext($responseText): bool
    {
        $serialNumber = null;
        $caseNumber = null;

        if (preg_match('/Серийный номер:\s*([\w\-]+)/ui', $responseText, $matches)) {
            $serialNumber = $matches[1];
        }

        if (preg_match('/Номер корпуса:\s*([\w\-]+)/ui', $responseText, $matches)) {
            $caseNumber = $matches[1];
        }

        if (!$serialNumber && !$caseNumber) {
            return false;
        }

        $device = $this->normalizeDevice([
            'serial_number' => $serialNumber,
            'case_number' => $caseNumber,
        ]);

        if (!$device) {
            return false;
        }

        $this->setContext($device['serial_number'] ?? null, $device['case_number'] ?? null, $device['device_type'] ?? null);
        $this->pushFrame([
            'kind' => 'extracted_from_answer',
            'topic' => 'devices',
            'title' => 'Прибор из ответа агента',
            'entities' => ['devices' => [$device]],
        ]);

        return true;
    }

    /**
     * Возвращает текущий прибор: сначала явный current_device_context, затем
     * ближайший свежий фрейм с прибором.
     */
    public function getCurrentContext(): ?array
    {
        $this->ensureSessionStarted();

        if (isset($_SESSION[self::DEVICE_CONTEXT_KEY])) {
            $context = $_SESSION[self::DEVICE_CONTEXT_KEY];
            if (!isset($context['timestamp']) || time() - (int)$context['timestamp'] <= self::FRAME_TTL) {
                return $context;
            }
            unset($_SESSION[self::DEVICE_CONTEXT_KEY]);
        }

        foreach ($this->getFrames() as $frame) {
            $device = $frame['entities']['devices'][0] ?? null;
            if ($device) {
                $this->setContext($device['serial_number'] ?? null, $device['case_number'] ?? null, $device['device_type'] ?? null);
                return $_SESSION[self::DEVICE_CONTEXT_KEY] ?? null;
            }
        }

        return null;
    }

    public function setContext(?string $serialNumber, ?string $caseNumber, ?string $deviceType = null): void
    {
        $this->ensureSessionStarted();

        $_SESSION[self::DEVICE_CONTEXT_KEY] = [
            'serial_number' => $serialNumber,
            'case_number' => $caseNumber,
            'device_type' => $deviceType,
            'timestamp' => time(),
        ];
    }

    public function clearContext(): void
    {
        $this->ensureSessionStarted();
        unset($_SESSION[self::DEVICE_CONTEXT_KEY], $_SESSION[self::FRAMES_KEY]);
    }

    public function hasContext(): bool
    {
        return $this->getCurrentContext() !== null || !empty($this->getFrames());
    }

    public function getSerialNumber(): ?string
    {
        $context = $this->getCurrentContext();
        return $context['serial_number'] ?? null;
    }

    public function getCaseNumber(): ?string
    {
        $context = $this->getCurrentContext();
        return $context['case_number'] ?? null;
    }

    public function updateContextTimestamp(): void
    {
        $this->ensureSessionStarted();
        if (isset($_SESSION[self::DEVICE_CONTEXT_KEY])) {
            $_SESSION[self::DEVICE_CONTEXT_KEY]['timestamp'] = time();
        }
    }

    /**
     * Кладет фрейм наверх стека. Возвращает нормализованный фрейм.
     */
    public function pushFrame(array $frame): array
    {
        $this->ensureSessionStarted();

        $now = time();
        $entities = $frame['entities'] ?? [];
        $devices = $this->normalizeDevices($entities['devices'] ?? []);
        $deviceTypes = $this->normalizeDeviceTypes($entities['device_types'] ?? []);
        $records = array_slice($entities['records'] ?? [], 0, 20);
        $sql = array_slice($entities['sql'] ?? [], 0, 5);

        $normalized = [
            'id' => uniqid('ctx_', true),
            'kind' => $frame['kind'] ?? 'result',
            'topic' => $frame['topic'] ?? 'general',
            'title' => $frame['title'] ?? 'Результат запроса',
            'query' => $frame['query'] ?? null,
            'created_at' => $now,
            'expires_at' => $now + self::FRAME_TTL,
            'entities' => [
                'devices' => $devices,
                'device_types' => $deviceTypes,
                'records' => $records,
                'sql' => $sql,
            ],
            'summary' => $frame['summary'] ?? null,
        ];

        $frames = $this->getFrames();
        array_unshift($frames, $normalized);
        $_SESSION[self::FRAMES_KEY] = array_slice($frames, 0, self::MAX_FRAMES);

        if (!empty($devices)) {
            $device = $devices[0];
            $this->setContext($device['serial_number'] ?? null, $device['case_number'] ?? null, $device['device_type'] ?? null);
        }

        return $normalized;
    }

    /**
     * Возвращает свежие фреймы, автоматически удаляя устаревшие.
     */
    public function getFrames(): array
    {
        $this->ensureSessionStarted();

        $frames = $_SESSION[self::FRAMES_KEY] ?? [];
        $now = time();
        $fresh = [];

        foreach ($frames as $frame) {
            if (($frame['expires_at'] ?? 0) >= $now) {
                $fresh[] = $frame;
            }
        }

        $_SESSION[self::FRAMES_KEY] = array_slice($fresh, 0, self::MAX_FRAMES);
        return $_SESSION[self::FRAMES_KEY];
    }

    public function getLatestFrame(?string $topic = null): ?array
    {
        foreach ($this->getFrames() as $frame) {
            if ($topic === null || ($frame['topic'] ?? null) === $topic) {
                return $frame;
            }
        }

        return null;
    }

    /**
     * Создает фрейм из результата обычного DB-обработчика.
     */
    public function rememberDatabaseResult(string $userQuery, array $queryAnalysis, array $dbResult): ?array
    {
        if (empty($dbResult['success'])) {
            return null;
        }

        $topic = $this->inferTopic($userQuery, $queryAnalysis['type'] ?? '', '');
        $devices = [];
        $deviceTypes = [];
        $records = [];

        if (!empty($dbResult['device']) && is_array($dbResult['device'])) {
            $devices[] = $dbResult['device'];
        }
        if (!empty($dbResult['devices']) && is_array($dbResult['devices'])) {
            $devices = array_merge($devices, $dbResult['devices']);
        }
        if (!empty($dbResult['repair']) && is_array($dbResult['repair'])) {
            $devices[] = $dbResult['repair'];
            $records[] = $dbResult['repair'];
        }
        if (!empty($dbResult['repairs']) && is_array($dbResult['repairs'])) {
            $devices = array_merge($devices, $dbResult['repairs']);
            $records = array_merge($records, $dbResult['repairs']);
        }
        if (!empty($dbResult['tests']) && is_array($dbResult['tests'])) {
            $devices = array_merge($devices, $dbResult['tests']);
            $records = array_merge($records, $dbResult['tests']);
        }
        if (!empty($dbResult['data']['device']) && is_array($dbResult['data']['device'])) {
            $devices[] = $dbResult['data']['device'];
        }
        if (!empty($dbResult['device_type'])) {
            $deviceTypes[] = $dbResult['device_type'];
        }
        foreach ($devices as $device) {
            if (!empty($device['device_type'])) {
                $deviceTypes[] = $device['device_type'];
            }
        }

        if (empty($devices) && empty($deviceTypes) && empty($records)) {
            return null;
        }

        return $this->pushFrame([
            'kind' => $queryAnalysis['type'] ?? 'database_result',
            'topic' => $topic,
            'title' => $this->makeFrameTitle($queryAnalysis['type'] ?? 'database_result', $topic),
            'query' => $userQuery,
            'entities' => [
                'devices' => $devices,
                'device_types' => $deviceTypes,
                'records' => $records,
            ],
            'summary' => $dbResult['message'] ?? null,
        ]);
    }

    /**
     * Создает фрейм из SQL-результатов LLM.
     */
    public function rememberSqlResults(string $userQuery, array $executionResults, string $responseText = ''): ?array
    {
        $devices = [];
        $deviceTypes = [];
        $records = [];
        $sqlList = [];
        $sqlText = '';

        foreach ($executionResults as $item) {
            $sql = (string)($item['sql'] ?? '');
            $sqlList[] = $sql;
            $sqlText .= "\n" . $sql;

            $result = $item['result'] ?? [];
            if (empty($result['success']) || empty($result['data']) || !is_array($result['data'])) {
                continue;
            }

            foreach ($result['data'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $records[] = $row;
                $device = $this->normalizeDevice($row);
                if ($device) {
                    $devices[] = $device;
                }
                foreach ($this->extractDeviceTypesFromRow($row) as $type) {
                    $deviceTypes[] = $type;
                }
            }
        }

        $topic = $this->inferTopic($userQuery . ' ' . $responseText, 'llm_sql_result', $sqlText);

        if (empty($devices) && empty($deviceTypes) && empty($records)) {
            return null;
        }

        return $this->pushFrame([
            'kind' => 'llm_sql_result',
            'topic' => $topic,
            'title' => 'SQL-результат LLM',
            'query' => $userQuery,
            'entities' => [
                'devices' => $devices,
                'device_types' => $deviceTypes,
                'records' => $records,
                'sql' => $sqlList,
            ],
            'summary' => $responseText !== '' ? mb_substr(strip_tags($responseText), 0, 500) : null,
        ]);
    }

    /**
     * Разрешает ссылки пользователя на предыдущий контекст.
     */
    public function resolveReferences(string $userQuery): array
    {
        $query = mb_strtolower($userQuery);
        $ordinal = $this->detectOrdinal($query);
        $hasReference = $ordinal !== null || preg_match(
            '/\b(этот|этого|этому|этом|данный|данного|данному|данном|него|нему|ним|нему|по нему|у него|эти|их|данные|предыдущий|последний)\b/ui',
            $query
        );

        $result = [
            'has_reference' => (bool)$hasReference,
            'ordinal' => $ordinal,
            'topic' => $this->inferTopic($userQuery, '', ''),
            'device' => null,
            'devices' => [],
            'device_type' => null,
            'frame' => null,
        ];

        if (!$hasReference) {
            return $result;
        }

        $frames = $this->getFrames();
        foreach ($frames as $frame) {
            $devices = $frame['entities']['devices'] ?? [];
            if (!empty($devices)) {
                $result['frame'] = $frame;
                if ($ordinal !== null && isset($devices[$ordinal - 1])) {
                    $result['device'] = $devices[$ordinal - 1];
                    $result['devices'] = [$devices[$ordinal - 1]];
                } elseif (preg_match('/\b(эти|их|данные)\b/ui', $query)) {
                    $result['devices'] = $devices;
                    $result['device'] = $devices[0] ?? null;
                } else {
                    $result['device'] = $devices[0];
                    $result['devices'] = [$devices[0]];
                }
                $result['topic'] = $result['topic'] ?: ($frame['topic'] ?? null);
                break;
            }
        }

        foreach ($frames as $frame) {
            $types = $frame['entities']['device_types'] ?? [];
            if (!empty($types)) {
                $result['device_type'] = $types[0];
                $result['topic'] = $result['topic'] ?: ($frame['topic'] ?? null);
                break;
            }
        }

        return $result;
    }

    /**
     * Короткое текстовое представление стека для системного промпта LLM.
     */
    public function getContextPrompt(int $maxFrames = 5): string
    {
        $frames = array_slice($this->getFrames(), 0, $maxFrames);
        if (empty($frames)) {
            return '';
        }

        $out = "\n\n=== КОНТЕКСТ ДИАЛОГА ===\n";
        $out .= "Используйте этот структурированный контекст для ссылок пользователя вроде \"данный прибор\", \"первый из списка\", \"эти приборы\", \"этот тип\".\n";
        $out .= "Если текущая тема — ремонты, фразы \"причины ухода\", \"почему уходил\" и похожие означают причины отправки в ремонт, если пользователь явно не сказал \"прекращение эксплуатации\".\n\n";

        foreach ($frames as $idx => $frame) {
            $out .= 'Фрейм ' . ($idx + 1) . ": {$frame['title']} | тема: {$frame['topic']} | тип: {$frame['kind']}\n";
            if (!empty($frame['query'])) {
                $out .= "  Запрос: {$frame['query']}\n";
            }

            $devices = $frame['entities']['devices'] ?? [];
            if (!empty($devices)) {
                $out .= "  Приборы в порядке выдачи:\n";
                foreach (array_slice($devices, 0, 8) as $deviceIdx => $device) {
                    $label = $deviceIdx + 1;
                    $serial = $device['serial_number'] ?? 'не указан';
                    $case = $device['case_number'] ?? 'не указан';
                    $type = $device['device_type'] ?? 'тип не указан';
                    $out .= "    {$label}) serial_number={$serial}, case_number={$case}, device_type={$type}\n";
                }
            }

            $types = $frame['entities']['device_types'] ?? [];
            if (!empty($types)) {
                $out .= '  Типы приборов: ' . implode(', ', array_slice($types, 0, 8)) . "\n";
            }

            $out .= "\n";
        }

        $current = $this->getCurrentContext();
        if ($current) {
            $out .= "Текущий прибор по умолчанию: serial_number={$current['serial_number']}, case_number={$current['case_number']}";
            if (!empty($current['device_type'])) {
                $out .= ", device_type={$current['device_type']}";
            }
            $out .= "\n";
        }

        $out .= "=== КОНЕЦ КОНТЕКСТА ДИАЛОГА ===\n";
        return $out;
    }

    public function inferTopic(string $text, string $intent = '', string $sql = ''): string
    {
        $haystack = mb_strtolower($text . ' ' . $intent . ' ' . $sql);

        if (preg_match('/termination|прекращен|эксплуатац|списан/ui', $haystack)) {
            return 'termination';
        }
        if (preg_match('/repair|ремонт|неисправ|полом|причин[аы]?.*уход|уходил/ui', $haystack)) {
            return 'repairs';
        }
        if (preg_match('/testing|испытан|допуск|замер/ui', $haystack)) {
            return 'testing';
        }
        if (preg_match('/inspection|контрол|ндк|неразруша/ui', $haystack)) {
            return 'inspection';
        }
        if (preg_match('/message|сообщен|письм/ui', $haystack)) {
            return 'messages';
        }
        if (preg_match('/user|пользоват/ui', $haystack)) {
            return 'users';
        }

        return 'devices';
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    private function normalizeDevices(array $rows): array
    {
        $devices = [];
        $seen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $device = $this->normalizeDevice($row);
            if (!$device) {
                continue;
            }
            $key = ($device['serial_number'] ?? '') . '|' . ($device['case_number'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $devices[] = $device;
        }

        return $devices;
    }

    private function normalizeDevice(array $row): ?array
    {
        $serial = $this->firstNonEmpty($row, ['serial_number', 'device_serial_number', 'serial', 'серийный_номер']);
        $case = $this->firstNonEmpty($row, ['case_number', 'device_case_number', 'case', 'корпус', 'номер_корпуса']);
        $type = $this->firstNonEmpty($row, ['device_type', 'type_name', 'тип_прибора']);

        if ($serial === null && $case === null) {
            return null;
        }

        $device = [
            'serial_number' => $serial,
            'case_number' => $case,
            'device_type' => $type,
        ];

        foreach (['id', 'device_id', 'repairs_count', 'repair_count', 'count', 'nominal_diameter', 'transmitter_count'] as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                $device[$key] = $row[$key];
            }
        }

        return $device;
    }

    private function normalizeDeviceTypes(array $types): array
    {
        $normalized = [];
        foreach ($types as $type) {
            if (is_array($type)) {
                foreach ($this->extractDeviceTypesFromRow($type) as $rowType) {
                    $normalized[] = $rowType;
                }
                continue;
            }
            if ($type !== null && trim((string)$type) !== '') {
                $normalized[] = trim((string)$type);
            }
        }

        return array_values(array_unique($normalized));
    }

    private function extractDeviceTypesFromRow(array $row): array
    {
        $types = [];
        foreach (['device_type', 'type_name', 'тип_прибора'] as $key) {
            if (!empty($row[$key])) {
                $types[] = (string)$row[$key];
            }
        }
        return $types;
    }

    private function firstNonEmpty(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                return trim((string)$row[$key]);
            }
        }
        return null;
    }

    private function detectOrdinal(string $query): ?int
    {
        $map = [
            1 => 'перв',
            2 => 'втор',
            3 => 'трет',
            4 => 'четверт',
            5 => 'пят',
            6 => 'шест',
            7 => 'седьм',
            8 => 'восьм',
            9 => 'девят',
            10 => 'десят',
        ];

        foreach ($map as $number => $prefix) {
            if (preg_match('/\b' . $prefix . '\w*/ui', $query)) {
                return $number;
            }
        }

        if (preg_match('/\b(\d{1,2})\s*(?:-?й|-?я|-?ое|из списка|позици)/ui', $query, $matches)) {
            $number = (int)$matches[1];
            return $number > 0 ? $number : null;
        }

        return null;
    }

    private function makeFrameTitle(string $intent, string $topic): string
    {
        $titles = [
            'device_info_by_serial' => 'Карточка прибора',
            'device_info_by_case' => 'Карточка прибора',
            'full_device_info_by_serial' => 'Полная карточка прибора',
            'full_device_info_by_case' => 'Полная карточка прибора',
            'list_devices_by_type' => 'Список приборов',
            'recent_repairs' => 'Последние ремонты',
            'last_repair' => 'Последний ремонт',
            'devices_in_repair' => 'Приборы в ремонте',
            'devices_in_testing' => 'Последние испытания',
            'top_repaired_device' => 'Приборы по частоте ремонтов',
            'repair_reasons_by_device' => 'Причины ремонтов прибора',
        ];

        return $titles[$intent] ?? 'Контекст: ' . $topic;
    }
}
