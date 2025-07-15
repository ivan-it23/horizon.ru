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
            session_start();
            header('Content-Type: application/json');
            // Очищаем буфер от возможного HTML-выхода
            ob_clean();

            // Разрешаем только POST-запросы
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                ob_clean();
                echo json_encode(['error' => 'Method Not Allowed']);
                return;
            }

            $input = file_get_contents('php://input');
            $data  = json_decode($input, true) ?: [];

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

            // Получаем сообщение от пользователя
            $userMessage = $data['message'] ?? '';
            if (!$userMessage) {
                http_response_code(400);
                echo json_encode(['error' => 'No message provided']);
                return;
            }

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
            if (!empty($dbResult['success'])) {
                // Подготовка контекста метаданных
                $context = [
                    'timestamp' => $timestamp,
                    'user_name' => $userName,
                    'log_link'  => $logLink
                ];
                $templateKey = null;
                // Маппинг типов DB-запросов на шаблоны
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
                    default:
                        // По умолчанию обёртка generic
                        $templateKey = 'generic';
                        $context['message'] = $dbResult['message'] ?? '';
                }
                $responseText = $templateManager->render($templateKey, $context);
                $isDbQuery = true;
            } else {
                // Запрос не к БД или ошибка – обращаемся к LLM
                // Загружаем инструкции для ИИ
                $instructions = '';
                $instrPath = ROOT_PATH . '/ai_agent/ai_agent_instructions.md';
                if (file_exists($instrPath)) {
                    $instructions = file_get_contents($instrPath);
                }
                // Формируем системное сообщение
                $systemContext = "Ты ИИ-агент корпоративной системы учета приборов компании АМК ГОРИЗОНТ.\n\nВАЖНЫЕ ИНСТРУКЦИИ:\n{$instructions}";
                // Собираем историю беседы и текущее сообщение
                $messages = [];
                $messages[] = ['role' => 'system', 'content' => $systemContext];
                $history = $_SESSION['conversation_history'] ?? [];
                $recentHistory = array_slice($history, -10);
                foreach ($recentHistory as $entry) {
                    $messages[] = ['role' => 'user',      'content' => $entry['user']];
                    $messages[] = ['role' => 'assistant', 'content' => $entry['assistant']];
                }
                $messages[] = ['role' => 'user', 'content' => $userMessage];
                // Подготавливаем и отправляем запрос к OpenRouter
                $payload = ['model' => 'deepseek/deepseek-chat-v3-0324:free', 'messages' => $messages];
                $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . OPENROUTER_API_KEY
                ]);
                $apiResponse = curl_exec($ch);
                curl_close($ch);
                $decoded = json_decode($apiResponse, true);
                if (isset($decoded['choices'][0]['message']['content'])) {
                    $responseText = $decoded['choices'][0]['message']['content'];
                } else {
                    $responseText = $dbResult['message'] ?? 'Извините, не удалось получить ответ от ИИ-агента.';
                }
                $isDbQuery = false;
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
} 