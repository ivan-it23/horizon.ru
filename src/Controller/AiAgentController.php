<?php
namespace App\Controller;

use App\Service\ResponseTemplateManager;

class AiAgentController extends BaseController
{
    /**
     * Дебаг-информация о вызовах LLM для логирования
     */
    private array $llmDebug = [];

    /**
     * Кэш содержимого ai_agent_instructions.md (читается один раз за процесс).
     * null — ещё не читали, '' — файл отсутствует или пуст.
     */
    private static ?string $agentInstructionsCache = null;

    /**
     * Возвращает текст руководства агента из ai_agent_instructions.md.
     * Файл подгружается в системный промпт LLM, чтобы поведенческие правила
     * (формат ответов, запреты, шаблоны SQL и т. п.) реально влияли на ответ.
     * При отсутствии или нечитаемости файла возвращает пустую строку, не падая.
     */
    private function getAgentInstructions(): string
    {
        if (self::$agentInstructionsCache !== null) {
            return self::$agentInstructionsCache;
        }

        $path = __DIR__ . '/../../ai_agent/ai_agent_instructions.md';
        if (!is_file($path) || !is_readable($path)) {
            error_log('[AI Agent] ai_agent_instructions.md not found or not readable: ' . $path);
            self::$agentInstructionsCache = '';
            return self::$agentInstructionsCache;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            error_log('[AI Agent] Failed to read ai_agent_instructions.md');
            self::$agentInstructionsCache = '';
            return self::$agentInstructionsCache;
        }

        self::$agentInstructionsCache = trim($content);
        return self::$agentInstructionsCache;
    }

    /**
     * Оборачивает текст руководства агента в маркированный блок для системного промпта.
     * Если файл недоступен — возвращает пустую строку (системный промпт не разрастается).
     */
    private function getAgentInstructionsBlock(): string
    {
        $instructions = $this->getAgentInstructions();
        if ($instructions === '') {
            return '';
        }
        return "=== РУКОВОДСТВО ИИ-АГЕНТА (ai_agent_instructions.md) ===\n"
            . $instructions
            . "\n=== КОНЕЦ РУКОВОДСТВА ===\n\n";
    }

    /**
     * Обрабатывает AJAX-запросы к ИИ-агенту
     */
    public function chat(): void
    {
        // Увеличиваем лимит времени выполнения для двух последовательных вызовов LLM
        set_time_limit(300);
        
        // Начинаем буферизацию и подавляем отображение ошибок
        ini_set('display_errors', '0');
        ob_start();
        
        try {
            // Проверка авторизации
            $this->requireAuth();
            
            // Rate limiting для AI-агента (не более 20 запросов в минуту)
            if (!$this->checkRateLimit($this->getClientIP(), 'ai_chat', 20, 60)) {
                ob_clean();
                $this->jsonResponse(['error' => 'Слишком много запросов к AI-агенту. Попробуйте через минуту.'], 429);
            }

            // Очищаем буфер от возможного HTML-выхода
            ob_clean();

            // Разрешаем только POST-запросы
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['error' => 'Method Not Allowed'], 405);
            }

            $input = file_get_contents('php://input');
            $data  = json_decode($input, true) ?: [];
            
            // Валидация JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logSecurityEvent('ai_invalid_json', [
                    'user_id' => $_SESSION['user']['id'],
                    'json_error' => json_last_error_msg()
                ]);
                $this->jsonResponse(['error' => 'Invalid JSON format'], 400);
            }

            // Очистка истории диалога
            if (isset($data['clear_history']) && $data['clear_history'] === true) {
                $_SESSION['conversation_history'] = [];
                ob_clean();
                echo json_encode([
                    'choices'            => [
                        ['message' => ['content' => 'История беседы очищена. Начинаем новый диалог.']]
                    ],
                    'conversation_length' => 0,
                    'log_link'            => ''
                ]);
                return;
            }

            // Загрузка соединения и обработчика БД
            require_once __DIR__ . '/../../db_connection.php';

            if (!isset($conn)) {
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed']);
                return;
            }

            // Получаем и валидируем сообщение от пользователя
            $userMessage = $this->sanitizeString($data['message'] ?? '', 2000); // Ограничиваем длину сообщения
            if (empty($userMessage)) {
                $this->jsonResponse(['error' => 'No message provided'], 400);
            }

            // Логируем запрос к AI-агенту
            $this->logSecurityEvent('ai_chat_request', [
                'user_id' => $_SESSION['user']['id'],
                'message_length' => strlen($userMessage),
                'has_clear_history' => isset($data['clear_history'])
            ]);

            // Анализируем запрос и выполняем при необходимости SQL
            $dbHandler    = new \App\Service\DatabaseQueryHandler($conn);
            $analysis     = $dbHandler->analyzeQuery($userMessage);
            $dbResult     = $dbHandler->executeQuery($analysis);

            // Подготовка шаблонизатора
            $templateManager = new ResponseTemplateManager();
            $timestamp       = date('Y-m-d H:i:s');
            $userName        = $_SESSION['user']['lastname'] ?? ($_SESSION['user']['username'] ?? 'Guest');
            // Инициализация ссылки на лог (будет сгенерирована ниже)
            $logLink = '';

            // Формируем ответ
            $responseText = '';
            $isDbQuery = false;
            $isDialog = false;

            // 1. Обработка диалоговых запросов (about, greeting, farewell)
            if (!empty($dbResult['type']) && $dbResult['type'] === 'dialog') {
                $isDialog = true;
                $responseText = $this->getDialogResponse($dbResult['dialog_type'] ?? 'unknown');
            }
            // 2. Обработка успешных DB-запросов
            elseif (!empty($dbResult['success']) && empty($dbResult['type'])) {
                $isDbQuery = true;
                $context = [
                    'timestamp' => $timestamp,
                    'user_name' => $userName,
                    'log_link'  => $logLink
                ];
                $templateKey = 'generic';
                
                switch ($analysis['type']) {
                    case 'count_devices_by_type':
                        $templateKey = 'count_devices_by_type';
                        $context['count']       = $dbResult['count'] ?? 0;
                        $context['device_type'] = $dbResult['device_type'] ?? '';
                        break;
                    case 'count_all_devices':
                        $templateKey = 'count_all_devices';
                        $context['count'] = $dbResult['count'] ?? 0;
                        break;
                    case 'count_repairs':
                        $templateKey = 'count_repairs';
                        $context['count'] = $dbResult['count'] ?? 0;
                        break;
                    case 'last_repair':
                    case 'devices_in_repair':
                    case 'devices_in_testing':
                    case 'repair_analysis':
                        // Для этих типов используем message напрямую
                        $responseText = $dbResult['message'] ?? '';
                        break;
                    default:
                        $context['message'] = $dbResult['message'] ?? '';
                }
                
                // Используем шаблонизатор только если ответ еще не сформирован
                if (empty($responseText)) {
                    $responseText = $templateManager->render($templateKey, $context);
                }
            }
            // 3. Обработка нераспознанных запросов через LLM с контекстом БД
            else {
                $responseText = $this->callLLMWithDatabaseContext($userMessage, $dbHandler, $dbResult);
            }

            // Генерация лога диалога
            $logDir = ROOT_PATH . '/logs/ai';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logId   = uniqid('', true);
            $logFile = "{$logDir}/{$logId}.log";
            $logData = [
                'timestamp' => $timestamp,
                'user'      => $userName,
                'message'   => $userMessage,
                'analysis'  => $analysis,
                'dbResult'  => $dbResult,
                'response'  => $responseText
            ];
            if (!$isDbQuery && !empty($this->llmDebug)) {
                $logData['llm_calls'] = $this->llmDebug;
            }
            file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $logLink = '/logs/ai/' . $logId . '.log';

            // Сохраняем диалог в сессии
            $_SESSION['conversation_history'][] = [
                'user'              => $userMessage,
                'assistant'         => $responseText,
                'timestamp'         => $timestamp,
                'is_database_query' => $isDbQuery
            ];
            if (count($_SESSION['conversation_history']) > 50) {
                $_SESSION['conversation_history'] = array_slice($_SESSION['conversation_history'], -50);
            }

            // Отдаем JSON-ответ
            ob_clean();
            echo json_encode([
                'choices'             => [[ 'message' => ['content' => $responseText] ]],
                'conversation_length' => count($_SESSION['conversation_history']),
                'log_link'            => $logLink
            ]);
        } catch (\Throwable $e) {
            // При ошибке возвращаем JSON с ошибкой
            ob_clean();
            echo json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Возвращает ответ на диалоговый запрос
     */
    private function getDialogResponse(string $dialogType): string
    {
        $responses = [
            'about' => "# Я — ИИ-агент системы учёта приборов АМК «Горизонт»\n\n" .
                "Я помогаю работать с базой данных геофизических приборов индукционного каротажа.\n\n" .
                "## Что я умею:\n\n" .
                "• **Поиск приборов** — по серийному номеру или номеру корпуса\n" .
                "• **Статистика** — количество приборов, ремонтов, типы\n" .
                "• **Информация о ремонтах** — последние ремонты, приборы в ремонте\n" .
                "• **История приборов** — полная информация из всех таблиц\n" .
                "• **Контекстные диалоги** — помню о каком приборе мы говорим\n\n" .
                "## Примеры вопросов:\n\n" .
                "• «Сколько приборов типа LWD?»\n" .
                "• «Покажи информацию о приборе 109»\n" .
                "• «Какой был последний ремонт?»\n" .
                "• «Какие приборы сейчас в ремонте?»\n" .
                "• «Полная информация о приборе с корпусом 008-2024»\n\n" .
                "Просто спросите меня о приборах естественным языком!",

            'greeting' => "# Привет\n\n" .
                "Я ИИ-агент системы учёта приборов АМК «Горизонт». Готов помочь вам с:\n\n" .
                "• Поиском приборов по серийным номерам\n" .
                "• Получением статистики и аналитики\n" .
                "• Информацией о ремонтах и испытаниях\n" .
                "• Ответами на вопросы о базе данных\n\n" .
                "**Чем могу помочь?**",

            'farewell' => "# До свидания\n\n" .
                "Был рад помочь! Если понадобится информация о приборах — я всегда здесь.\n" .
                "Хорошего дня.",

            'unknown' => "Я готов помочь! Задайте вопрос о приборах или спросите «кто ты», чтобы узнать больше о моих возможностях."
        ];

        return $responses[$dialogType] ?? $responses['unknown'];
    }

    /**
     * Вызывает LLM с контекстом базы данных для нераспознанных запросов
     * Двухступенчатая обработка: 1) Генерация SQL, 2) Выполнение и форматирование
     */
    private function callLLMWithDatabaseContext(string $userMessage, \App\Service\DatabaseQueryHandler $dbHandler, array $dbResult): string
    {
        // Получаем контекст базы данных для LLM
        $dbContext = $dbHandler->getDatabaseContextForLLM();
        
        // Получаем текущий контекст устройства (если есть)
        $deviceContext = $dbHandler->getCurrentContext();
        $deviceContextStr = '';
        if ($deviceContext) {
            $deviceContextStr = "\n\n=== ТЕКУЩИЙ КОНТЕКСТ ===\n";
            $deviceContextStr .= "Сейчас мы обсуждаем прибор с серийным номером: {$deviceContext['serial_number']}\n";
            if ($deviceContext['case_number']) {
                $deviceContextStr .= "Номер корпуса: {$deviceContext['case_number']}\n";
            }
        }

        // ШАГ 1: Просим LLM сгенерировать SQL-запросы (короткий промпт, низкая температура)
        $instructionsBlock = $this->getAgentInstructionsBlock();
        $step1SystemContext = $instructionsBlock .
            "Вы составляете SQL-запросы для БД АМК «Горизонт».\n\n" .
            "Структура БД:\n{$dbContext}{$deviceContextStr}\n\n" .
            "ПРАВИЛА:\n" .
            "1. Каждый запрос оборачивайте в тег: <sql>ЗАПРОС</sql>\n" .
            "2. ТОЛЬКО SELECT-запросы или read-only WITH ... SELECT.\n" .
            "3. Минимум текста до/после запросов.\n" .
            "4. Если вопрос не требует БД — отвечайте кратко без SQL.\n" .
            "5. Соблюдайте запреты из руководства (поля users.password и uploaded_files.file_data в SELECT не включать).\n" .
            "6. Для DATE/DATETIME столбцов не сравнивайте дату с пустой строкой ''; используйте IS NULL и YEAR(date_column) = 0 для zero-date. NULLIF(..., '') применяйте только к varchar/text датам.\n" .
            "7. ВНИМАНИЕ: lifecycle-таблицы (repair_data, testing_data, inspection_data, termination_data, production_discontinuation, additional_data, uploaded_files) соединяются с device_identification ТОЛЬКО по (serial_number, case_number). У них НЕТ колонок device_id и device_type. См. блок === ТИПИЧНЫЕ ОШИБКИ JOIN ===.\n\n" .
            "Пример:\n<sql>SELECT device_type, COUNT(*) FROM device_identification GROUP BY device_type</sql>";

        $step1Messages = [
            ['role' => 'system', 'content' => $step1SystemContext],
            ['role' => 'user', 'content' => $userMessage]
        ];

        // Step 1: короткий ответ (только SQL), низкая температура для стабильности
        $step1Response = $this->callLLM($step1Messages, 800, 0.1);

        // Проверяем, есть ли в ответе SQL-запросы
        $sqlQueries = $this->extractSQLQueries($step1Response);

        if (empty($sqlQueries)) {
            // SQL не найден — возвращаем ответ как есть (диалоговый или уточняющий)
            return $step1Response;
        }

        // ШАГ 2: Выполняем SQL-запросы и при ошибке один раз просим LLM исправить SQL
        $executionResults = $this->executeLLMQueriesWithRetry($sqlQueries, $dbHandler, $dbContext, $deviceContextStr, $userMessage);

        // ШАГ 3: Отправляем результаты обратно к LLM для форматирования
        $resultsContext = $this->buildSQLResultsContext($executionResults);

        $step2SystemContext = "Ты ИИ-агент АМК ГОРИЗОНТ. Отвечай коротко и по делу на основе данных из БД.\n" .
            "ПРАВИЛА:\n" .
            "1. Используй Markdown (заголовки, списки, таблицы).\n" .
            "2. НЕ ПРИДУМЫВАЙ данные.\n" .
            "3. Не дублируй SQL-запросы в ответе.\n" .
            "4. Если SQL после повторной попытки всё равно вернул ошибку, не делай фактических выводов из предположений и не советуй чистить БД — сообщи, что данные не получены.\n" .
            "5. Будь лаконичен: только нужные данные и краткий вывод.";

        $step2Messages = [
            ['role' => 'system', 'content' => $step2SystemContext],
            ['role' => 'user', 'content' => "Вопрос: {$userMessage}\n\n{$resultsContext}"]
        ];

        // Step 2: финальный ответ с форматированием
        return $this->callLLM($step2Messages, 3000, 0.5);
    }

    /**
     * Выполняет прямой вызов к LLM (polza.ai)
     * @param array $messages Сообщения для LLM
     * @param int $maxTokens Максимальное количество токенов в ответе
     * @param float $temperature Температура генерации (0.0-1.0)
     */
    private function callLLM(array $messages, int $maxTokens = 4000, float $temperature = 0.7): string
    {
        $payload = [
            'model' => 'deepseek/deepseek-v3.2-exp',
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens
        ];

        $ch = curl_init('https://polza.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . POLZA_AI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);

        $startTime = microtime(true);
        $apiResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration = round(microtime(true) - $startTime, 2);
        curl_close($ch);

        // Сохраняем debug-информацию для лога
        $debugEntry = [
            'request' => $payload,
            'http_code' => $httpCode,
            'duration_sec' => $duration,
            'curl_error' => $curlError ?: null,
            'raw_response' => $apiResponse
        ];

        if ($curlError) {
            error_log("[AI Agent] cURL Error: " . $curlError);
            $this->llmDebug[] = $debugEntry;
            return 'Ошибка связи с ИИ-сервисом: ' . $curlError;
        }

        $decoded = json_decode($apiResponse, true);
        $debugEntry['decoded_response'] = $decoded;
        $this->llmDebug[] = $debugEntry;

        if (isset($decoded['choices'][0]['message']['content'])) {
            $content = $decoded['choices'][0]['message']['content'];
            // Проверяем, не оборвался ли ответ по лимиту
            $finishReason = $decoded['choices'][0]['finish_reason'] ?? '';
            if ($finishReason === 'length') {
                error_log("[AI Agent] Response truncated by max_tokens limit");
            }
            return $content;
        } elseif (isset($decoded['error'])) {
            error_log("[AI Agent] LLM Error: " . json_encode($decoded['error'], JSON_UNESCAPED_UNICODE));
            return 'Ошибка ИИ-сервиса: ' . ($decoded['error']['message'] ?? 'Unknown');
        } else {
            error_log("[AI Agent] Unexpected Response: " . $apiResponse);
            return 'Не удалось получить ответ от ИИ-агента.';
        }
    }

    /**
     * Извлекает SQL-запросы из текста ответа LLM
     */
    private function extractSQLQueries(string $text): array
    {
        $queries = [];
        
        // Ищем запросы в тегах <sql>...</sql>
        if (preg_match_all('/<sql>(.*?)<\/sql>/is', $text, $matches)) {
            foreach ($matches[1] as $sql) {
                $sql = trim($sql);
                // Проверяем, что это read-only запрос
                if (preg_match('/^\s*(SELECT|WITH)\b/i', $sql)) {
                    $queries[] = $sql;
                }
            }
        }
        
        // Если тегов нет, ищем запросы в markdown code blocks ```sql
        if (empty($queries)) {
            if (preg_match_all('/```sql\s*(.*?)```/is', $text, $matches)) {
                foreach ($matches[1] as $sql) {
                    $sql = trim($sql);
                    if (preg_match('/^\s*(SELECT|WITH)\b/i', $sql)) {
                        $queries[] = $sql;
                    }
                }
            }
        }
        
        // Если и code blocks нет, ищем строки начинающиеся с SELECT
        if (empty($queries)) {
            if (preg_match_all('/(?:SELECT|WITH)\s+.*?;/is', $text, $matches)) {
                foreach ($matches[0] as $sql) {
                    $sql = trim($sql);
                    // Убираем точку с запятой в конце если есть
                    $sql = rtrim($sql, ';');
                    $queries[] = $sql;
                }
            }
        }
        
        return $queries;
    }

    /**
     * Выполняет SQL от LLM и один раз просит модель исправить запросы при ошибке.
     */
    private function executeLLMQueriesWithRetry(
        array $sqlQueries,
        \App\Service\DatabaseQueryHandler $dbHandler,
        string $dbContext,
        string $deviceContextStr,
        string $userMessage
    ): array {
        $executionResults = [];

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $executionResults = [];
            foreach ($sqlQueries as $sql) {
                $executionResults[] = [
                    'sql' => $sql,
                    'result' => $dbHandler->executeQueryForLLM($sql)
                ];
            }

            if (!$this->hasFailedSQLExecution($executionResults) || $attempt === 2) {
                return $executionResults;
            }

            $retrySystemContext = $this->getAgentInstructionsBlock() .
                "Вы исправляете SQL-запросы для БД АМК «Горизонт».\n\n" .
                "Структура БД:\n{$dbContext}{$deviceContextStr}\n\n" .
                "ПРАВИЛА:\n" .
                "1. Верните только исправленные запросы в тегах <sql>...</sql>.\n" .
                "2. Разрешены только SELECT-запросы или read-only WITH ... SELECT.\n" .
                "3. Не советуйте чистить или менять данные — исправьте SQL так, чтобы он корректно читал текущую БД.\n" .
                "4. Для DATE/DATETIME столбцов не сравнивайте дату с пустой строкой ''. Используйте IS NULL и YEAR(date_column) = 0 для zero-date.\n" .
                "5. NULLIF(date_column, '') применяйте только если фактический тип столбца varchar/text, а не DATE/DATETIME.\n" .
                "6. Lifecycle-таблицы соединяйте с device_identification по serial_number и case_number.\n" .
                "7. Соблюдайте запреты из руководства: users.password и uploaded_files.file_data не запрашивать.\n";

            $retryMessages = [
                ['role' => 'system', 'content' => $retrySystemContext],
                ['role' => 'user', 'content' => "Вопрос пользователя: {$userMessage}\n\nОшибки предыдущих SQL-запросов:\n" . $this->buildSQLResultsContext($executionResults)]
            ];

            $retryResponse = $this->callLLM($retryMessages, 1800, 0.1);
            $retryQueries = $this->extractSQLQueries($retryResponse);
            if (empty($retryQueries)) {
                return $executionResults;
            }

            $sqlQueries = $retryQueries;
        }

        return $executionResults;
    }

    /**
     * Проверяет, были ли ошибки выполнения SQL.
     */
    private function hasFailedSQLExecution(array $executionResults): bool
    {
        foreach ($executionResults as $item) {
            if (empty($item['result']['success'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Формирует контекст результатов SQL для LLM.
     */
    private function buildSQLResultsContext(array $executionResults): string
    {
        $resultsContext = "РЕЗУЛЬТАТЫ SQL-ЗАПРОСОВ:\n\n";
        foreach ($executionResults as $idx => $item) {
            $resultsContext .= "Запрос " . ($idx + 1) . ": " . $item['sql'] . "\n";
            if ($item['result']['success']) {
                $resultsContext .= "Результат:\n";
                if (!empty($item['result']['data'])) {
                    $resultsContext .= json_encode($item['result']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    $resultsContext .= "Нет данных (пустой результат)";
                }
                $resultsContext .= "\nКоличество записей: " . $item['result']['count'] . "\n";
                if (isset($item['result']['total_count']) && $item['result']['total_count'] !== $item['result']['count']) {
                    $resultsContext .= "Всего строк в результате: " . $item['result']['total_count'] . "\n";
                }
                if (!empty($item['result']['truncated'])) {
                    $resultsContext .= "Результат обрезан до первых " . $item['result']['count'] . " строк.\n";
                }
            } else {
                $resultsContext .= "ОШИБКА: " . ($item['result']['error'] ?? 'Неизвестная ошибка') . "\n";
            }
            $resultsContext .= "\n";
        }

        return $resultsContext;
    }

    /**
     * Streaming-версия chat() с поддержкой Server-Sent Events (SSE)
     * Клиент получает ответ по частям в реальном времени (ChatGPT-подобный опыт)
     */
    public function chatStream(): void
    {
        // Снимаем все ограничения времени и буферизации для стриминга
        set_time_limit(300);
        ini_set('display_errors', '0');
        ini_set('output_buffering', '0');
        ini_set('zlib.output_compression', '0');
        
        // Отключаем все уровни буферизации
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Проверка авторизации ДО отправки SSE-заголовков
        try {
            $this->requireAuth();
        } catch (\Throwable $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        // Rate limiting
        if (!$this->checkRateLimit($this->getClientIP(), 'ai_chat', 20, 60)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Слишком много запросов. Попробуйте через минуту.']);
            return;
        }

        // Устанавливаем SSE-заголовки
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        // Освобождаем сессию, чтобы не блокировать другие запросы пользователя
        session_write_close();

        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?: [];

            $userMessage = $this->sanitizeString($data['message'] ?? '', 2000);
            if (empty($userMessage)) {
                $this->sseEmit('error', ['message' => 'No message provided']);
                return;
            }

            // Переоткрываем сессию на чтение для получения user info
            session_start();
            $userName = $_SESSION['user']['lastname'] ?? ($_SESSION['user']['username'] ?? 'Guest');
            session_write_close();

            require_once __DIR__ . '/../../db_connection.php';
            if (!isset($conn)) {
                $this->sseEmit('error', ['message' => 'Database connection failed']);
                return;
            }

            $dbHandler = new \App\Service\DatabaseQueryHandler($conn);
            $analysis = $dbHandler->analyzeQuery($userMessage);
            $dbResult = $dbHandler->executeQuery($analysis);

            $timestamp = date('Y-m-d H:i:s');
            $fullResponse = '';
            $isDbQuery = false;

            // 1. Диалоговые запросы
            if (!empty($dbResult['type']) && $dbResult['type'] === 'dialog') {
                $fullResponse = $this->getDialogResponse($dbResult['dialog_type'] ?? 'unknown');
                $this->sseEmit('content', ['delta' => $fullResponse]);
            }
            // 2. Распознанные БД-запросы
            elseif (!empty($dbResult['success']) && empty($dbResult['type'])) {
                $isDbQuery = true;
                $templateManager = new ResponseTemplateManager();
                $context = ['timestamp' => $timestamp, 'user_name' => $userName, 'log_link' => ''];
                $templateKey = 'generic';
                
                switch ($analysis['type']) {
                    case 'count_devices_by_type':
                        $templateKey = 'count_devices_by_type';
                        $context['count'] = $dbResult['count'] ?? 0;
                        $context['device_type'] = $dbResult['device_type'] ?? '';
                        break;
                    case 'count_all_devices':
                        $templateKey = 'count_all_devices';
                        $context['count'] = $dbResult['count'] ?? 0;
                        break;
                    case 'count_repairs':
                        $templateKey = 'count_repairs';
                        $context['count'] = $dbResult['count'] ?? 0;
                        break;
                    case 'last_repair':
                    case 'devices_in_repair':
                    case 'devices_in_testing':
                    case 'repair_analysis':
                        $fullResponse = $dbResult['message'] ?? '';
                        break;
                    default:
                        $context['message'] = $dbResult['message'] ?? '';
                }
                
                if (empty($fullResponse)) {
                    $fullResponse = $templateManager->render($templateKey, $context);
                }
                $this->sseEmit('content', ['delta' => $fullResponse]);
            }
            // 3. Нераспознанные запросы — стриминг через LLM
            else {
                $fullResponse = $this->streamLLMWithDatabaseContext($userMessage, $dbHandler);
            }

            $this->saveStreamLog($userMessage, $userName, $analysis, $dbResult, $fullResponse, $isDbQuery);
            $this->sseEmit('done', ['total_length' => strlen($fullResponse)]);
        } catch (\Throwable $e) {
            error_log("[AI Agent Stream] Error: " . $e->getMessage());
            $this->sseEmit('error', ['message' => 'Internal error: ' . $e->getMessage()]);
        }
    }

    /**
     * Отправляет SSE-событие клиенту
     */
    private function sseEmit(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        @ob_flush();
        @flush();
    }

    /**
     * Стримит ответ LLM с двухступенчатой обработкой:
     * Step1 — синхронная генерация SQL, Step2 — стрим ответа пользователю
     */
    private function streamLLMWithDatabaseContext(string $userMessage, \App\Service\DatabaseQueryHandler $dbHandler): string
    {
        $dbContext = $dbHandler->getDatabaseContextForLLM();
        
        $deviceContext = $dbHandler->getCurrentContext();
        $deviceContextStr = '';
        if ($deviceContext) {
            $deviceContextStr = "\n\n=== ТЕКУЩИЙ КОНТЕКСТ ===\n";
            $deviceContextStr .= "Сейчас мы обсуждаем прибор с серийным номером: {$deviceContext['serial_number']}\n";
            if ($deviceContext['case_number']) {
                $deviceContextStr .= "Номер корпуса: {$deviceContext['case_number']}\n";
            }
        }

        $this->sseEmit('status', ['message' => 'Анализирую запрос...']);

        // ШАГ 1: Роутер запросов — определяем, нужна ли БД для ответа
        $instructionsBlock = $this->getAgentInstructionsBlock();
        $step1SystemContext = $instructionsBlock .
            "Вы — роутер запросов для ИИ-ассистента системы учёта приборов АМК «Горизонт».\n\n" .
            "Структура БД:\n{$dbContext}{$deviceContextStr}\n\n" .
            "ВАША ЗАДАЧА: решить, нужна ли БД для ответа.\n\n" .
            "ПРАВИЛА:\n" .
            "1. Если вопрос ТРЕБУЕТ данных из БД (приборы, ремонты, статистика, поиск) — выдайте SQL в тегах: <sql>ЗАПРОС</sql>\n" .
            "2. Если вопрос НЕ требует БД (программирование, общие вопросы, разговор, математика) — выдайте ТОЛЬКО тег: <no_sql/>\n" .
            "3. ТОЛЬКО SELECT-запросы или read-only WITH ... SELECT.\n" .
            "4. Не размышляйте долго. Отвечайте сразу одним-двумя тегами.\n" .
            "5. Никакого текста вне тегов.\n" .
            "6. Соблюдайте запреты из руководства: users.password и uploaded_files.file_data в SELECT не включать.\n" .
            "7. Для DATE/DATETIME столбцов не сравнивайте дату с пустой строкой ''; используйте IS NULL и YEAR(date_column) = 0 для zero-date. NULLIF(..., '') применяйте только к varchar/text датам.\n" .
            "8. ВНИМАНИЕ: lifecycle-таблицы (repair_data, testing_data, inspection_data, termination_data, production_discontinuation, additional_data, uploaded_files) соединяются с device_identification ТОЛЬКО по (serial_number, case_number). У них НЕТ колонок device_id и device_type. См. блок === ТИПИЧНЫЕ ОШИБКИ JOIN ===.\n\n" .
            "Примеры:\n" .
            "«Сколько приборов LWD?» → <sql>SELECT COUNT(*) FROM device_identification WHERE device_type='LWD'</sql>\n" .
            "«Напиши функцию факториала» → <no_sql/>\n" .
            "«Привет, как дела?» → <no_sql/>\n" .
            "«Как работает рекурсия?» → <no_sql/>";

        $step1Messages = [
            ['role' => 'system', 'content' => $step1SystemContext],
            ['role' => 'user', 'content' => $userMessage]
        ];

        // max_tokens с запасом на reasoning (qwen3.5-9b тратит до ~1000 токенов на размышления)
        $step1Response = $this->callLLM($step1Messages, 2500, 0.1);
        $sqlQueries = $this->extractSQLQueries($step1Response);

        if (empty($sqlQueries)) {
            // SQL не нужен — вопрос не про БД. Переходим к универсальному стримингу.
            return $this->streamGeneralAnswer($userMessage);
        }

        $this->sseEmit('status', ['message' => 'Получаю данные из базы...']);

        // ШАГ 2: Выполнение SQL с одной попыткой исправить ошибочный запрос
        $executionResults = $this->executeLLMQueriesWithRetry($sqlQueries, $dbHandler, $dbContext, $deviceContextStr, $userMessage);
        $resultsContext = $this->buildSQLResultsContext($executionResults);

        $step2SystemContext = $this->getAgentInstructionsBlock() .
            "Вы — ИИ-агент системы учёта приборов АМК «Горизонт». Сформируйте ответ на основе данных из БД.\n" .
            "ПРАВИЛА:\n" .
            "1. Используйте Markdown (заголовки уровня ###, маркированные списки, таблицы при необходимости).\n" .
            "2. Не придумывайте данные — используйте только то, что пришло из SQL-результата.\n" .
            "3. Не дублируйте SQL-запросы в ответе.\n" .
            "4. Если SQL после повторной попытки всё равно вернул ошибку, не делайте фактических выводов из предположений и не советуйте чистить БД — сообщите, что данные не получены.\n" .
            "5. Отвечайте лаконично, в нейтрально-деловом тоне, без эмодзи.\n" .
            "6. При выводе таблиц старайтесь не превышать 4–5 колонок: длинные многоколоночные таблицы плохо читаются в drawer-чате; вместо этого используйте маркированные списки или несколько компактных таблиц.";

        $step2Messages = [
            ['role' => 'system', 'content' => $step2SystemContext],
            ['role' => 'user', 'content' => "Вопрос: {$userMessage}\n\n{$resultsContext}"]
        ];

        $this->sseEmit('status', ['message' => 'Формирую ответ...']);

        // ШАГ 3: Стримим ответ пользователю
        $accumulated = '';
        $this->callLLMStream($step2Messages, 3000, 0.5, function(string $delta) use (&$accumulated) {
            $accumulated .= $delta;
            $this->sseEmit('content', ['delta' => $delta]);
        });

        return $accumulated;
    }

    /**
     * Стриминговый вызов LLM (polza.ai) через CURLOPT_WRITEFUNCTION
     * @param callable $onDelta Колбэк, вызывается для каждого нового куска текста
     */
    private function callLLMStream(array $messages, int $maxTokens, float $temperature, callable $onDelta): void
    {
        $payload = [
            'model' => 'deepseek/deepseek-v3.2-exp',
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'stream' => true
        ];

        $buffer = '';
        $ch = curl_init('https://polza.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . POLZA_AI_API_KEY,
            'Accept: text/event-stream'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$buffer, $onDelta) {
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);
                
                foreach (explode("\n", $event) as $line) {
                    if (strncmp($line, 'data: ', 6) === 0) {
                        $jsonData = substr($line, 6);
                        if ($jsonData === '[DONE]') {
                            return strlen($chunk);
                        }
                        $decoded = json_decode($jsonData, true);
                        if (isset($decoded['choices'][0]['delta']['content'])) {
                            $delta = $decoded['choices'][0]['delta']['content'];
                            if ($delta !== '') {
                                $onDelta($delta);
                            }
                        }
                    }
                }
            }
            return strlen($chunk);
        });

        $startTime = microtime(true);
        curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration = round(microtime(true) - $startTime, 2);
        curl_close($ch);

        $this->llmDebug[] = [
            'streaming' => true,
            'request' => $payload,
            'http_code' => $httpCode,
            'duration_sec' => $duration,
            'curl_error' => $curlError ?: null
        ];

        if ($curlError) {
            error_log("[AI Agent Stream] cURL Error: " . $curlError);
            $onDelta("\n\nОшибка связи с ИИ-сервисом: " . $curlError);
        }
    }

    /**
     * Сохраняет лог и историю для streaming-запроса
     */
    private function saveStreamLog(string $userMessage, string $userName, array $analysis, array $dbResult, string $responseText, bool $isDbQuery): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logDir = ROOT_PATH . '/logs/ai';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logId = uniqid('', true);
        $logFile = "{$logDir}/{$logId}.log";
        $logData = [
            'timestamp' => $timestamp,
            'user' => $userName,
            'message' => $userMessage,
            'analysis' => $analysis,
            'dbResult' => $dbResult,
            'response' => $responseText,
            'streaming' => true
        ];
        if (!$isDbQuery && !empty($this->llmDebug)) {
            $logData['llm_calls'] = $this->llmDebug;
        }
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        session_start();
        $_SESSION['conversation_history'][] = [
            'user' => $userMessage,
            'assistant' => $responseText,
            'timestamp' => $timestamp,
            'is_database_query' => $isDbQuery
        ];
        if (count($_SESSION['conversation_history']) > 50) {
            $_SESSION['conversation_history'] = array_slice($_SESSION['conversation_history'], -50);
        }
        session_write_close();
    }

    /**
     * Стрим ответа на общий вопрос, не связанный с БД
     * (программирование, математика, общие темы и т.п.)
     */
    private function streamGeneralAnswer(string $userMessage): string
    {
        $this->sseEmit('status', ['message' => 'Формирую ответ...']);

        $systemPrompt = $this->getAgentInstructionsBlock() .
            "Вы — корпоративный ИИ-ассистент системы учёта приборов АМК «Горизонт».\n" .
            "Отвечайте на вопросы пользователя по программированию, математике, общим темам и справке по системе.\n" .
            "ПРАВИЛА:\n" .
            "1. Используйте Markdown для форматирования (заголовки, списки, блоки кода).\n" .
            "2. Код оборачивайте в блоки ```язык ... ```.\n" .
            "3. Отвечайте кратко, по делу, в нейтрально-деловом тоне, без лишних предисловий и без эмодзи.\n" .
            "4. Не размышляйте долго — сразу давайте готовый ответ.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMessage]
        ];

        $accumulated = '';
        $this->callLLMStream($messages, 3000, 0.7, function(string $delta) use (&$accumulated) {
            $accumulated .= $delta;
            $this->sseEmit('content', ['delta' => $delta]);
        });

        // Если модель ничего не вернула (reasoning съел токены) — аварийный ответ
        if (trim($accumulated) === '') {
            $fallback = "Извините, не удалось сформировать ответ. Попробуйте переформулировать вопрос.";
            $this->sseEmit('content', ['delta' => $fallback]);
            return $fallback;
        }

        return $accumulated;
    }
}
