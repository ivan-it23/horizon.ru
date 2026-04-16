<?php
namespace App\Controller;

use App\Service\ResponseTemplateManager;

class AiAgentController extends BaseController
{
    /**
     * Обрабатывает AJAX-запросы к ИИ-агенту
     */
    public function chat(): void
    {
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
                        ['message' => ['content' => '🔄 История беседы очищена. Начинаем новый диалог!']]
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
            if (!$isDbQuery) {
                $logData['llm_request']  = $payload;
                $logData['llm_response'] = $decoded ?? null;
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
            'about' => "# 🤖 Я — ИИ-агент системы учёта приборов АМК «Горизонт»\n\n" .
                "Я помогаю работать с базой данных геофизических приборов индукционного каротажа.\n\n" .
                "## 📊 Что я умею:\n\n" .
                "• **Поиск приборов** — по серийному номеру или номеру корпуса\n" .
                "• **Статистика** — количество приборов, ремонтов, типы\n" .
                "• **Информация о ремонтах** — последние ремонты, приборы в ремонте\n" .
                "• **История приборов** — полная информация из всех таблиц\n" .
                "• **Контекстные диалоги** — помню о каком приборе мы говорим\n\n" .
                "## 💡 Примеры вопросов:\n\n" .
                "• «Сколько приборов типа LWD?»\n" .
                "• «Покажи информацию о приборе 109»\n" .
                "• «Какой был последний ремонт?»\n" .
                "• «Какие приборы сейчас в ремонте?»\n" .
                "• «Полная информация о приборе с корпусом 008-2024»\n\n" .
                "Просто спросите меня о приборах естественным языком!",

            'greeting' => "# 👋 Привет!\n\n" .
                "Я ИИ-агент системы учёта приборов АМК «Горизонт». Готов помочь вам с:\n\n" .
                "• Поиском приборов по серийным номерам\n" .
                "• Получением статистики и аналитики\n" .
                "• Информацией о ремонтах и испытаниях\n" .
                "• Ответами на вопросы о базе данных\n\n" .
                "**Чем могу помочь?**",

            'farewell' => "# 👋 До свидания!\n\n" .
                "Был рад помочь! Если понадобится информация о приборах — я всегда здесь.\n" .
                "Хорошего дня! 🌟",

            'unknown' => "Я готов помочь! Задайте вопрос о приборах или спросите «кто ты», чтобы узнать больше о моих возможностях."
        ];

        return $responses[$dialogType] ?? $responses['unknown'];
    }

    /**
     * Вызывает LLM с контекстом базы данных для нераспознанных запросов
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

        // Загружаем инструкции для ИИ
        $instructions = '';
        $instrPath = ROOT_PATH . '/ai_agent/ai_agent_instructions.md';
        if (file_exists($instrPath)) {
            $instructions = file_get_contents($instrPath);
        }

        // Формируем расширенное системное сообщение с контекстом БД
        $systemContext = "Ты ИИ-агент корпоративной системы учета приборов компании АМК ГОРИЗОНТ.\n\n" .
            "У тебя есть прямой доступ к базе данных. Вот структура базы данных:\n\n{$dbContext}{$deviceContextStr}\n\n" .
            "ВАЖНЫЕ ИНСТРУКЦИИ:\n{$instructions}\n\n" .
            "Дополнительные инструкции:\n" .
            "1. Если пользователь спрашивает что-то, что можно найти в БД — используй SQL-запросы.\n" .
            "2. Если запрос неясен — вежливо переспроси уточняющий вопрос.\n" .
            "3. Если данных нет в БД — честно скажи об этом.\n" .
            "4. Для диалогов используй дружелюбный, профессиональный тон.\n" .
            "5. НЕ ПРИДУМЫВАЙ данные — используй только информацию из БД.\n" .
            "6. Для сложных запросов можешь составить SQL и описать, что бы ты искал.";

        // Собираем историю беседы и текущее сообщение
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemContext];
        
        $history = $_SESSION['conversation_history'] ?? [];
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $entry) {
            $messages[] = ['role' => 'user', 'content' => $entry['user']];
            $messages[] = ['role' => 'assistant', 'content' => $entry['assistant']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        // Отправляем запрос к polza.ai
        $payload = [
            'model' => 'qwen/qwen3.5-9b',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];

        $ch = curl_init('https://polza.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . POLZA_AI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log("[AI Agent] cURL Error: " . $curlError);
            return 'Извините, произошла ошибка связи с ИИ-сервисом. Детали: ' . $curlError . ' (HTTP Code: ' . $httpCode . ')';
        }

        $decoded = json_decode($apiResponse, true);

        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        } elseif (isset($decoded['error'])) {
            error_log("[AI Agent] OpenRouter Error: " . json_encode($decoded['error'], JSON_UNESCAPED_UNICODE));
            return 'Извините, ИИ-сервис временно недоступен. Попробуйте позже. (Error: ' . ($decoded['error']['message'] ?? 'Unknown') . ')';
        } else {
            error_log("[AI Agent] Unexpected Response: " . $apiResponse);
            return 'Извините, не удалось получить ответ от ИИ-агента. Попробуйте переформулировать вопрос.';
        }
    }
}