<?php
namespace App\Service;

/**
 * Класс для анализа естественно-языковых запросов пользователя
 * и определения типа запроса к базе данных
 */
class QueryAnalyzer
{
    private $last_device_serial_number = null;
    private $last_device_case_number = null;
    private ?ContextManager $contextManager;

    public function __construct(?ContextManager $contextManager = null)
    {
        $this->contextManager = $contextManager;
    }

    /**
     * Устанавливает контекст последнего найденного прибора
     */
    public function setLastDevice($serialNumber, $caseNumber): void
    {
        $this->last_device_serial_number = $serialNumber;
        $this->last_device_case_number = $caseNumber;
    }

    /**
     * Анализирует запрос пользователя и определяет тип запроса
     * 
     * @param string $userQuery Запрос пользователя
     * @return array Массив с типом запроса и дополнительными параметрами
     */
    public function analyzeQuery($userQuery): array
    {
        $query = mb_strtolower($userQuery);
        $reference = $this->contextManager ? $this->contextManager->resolveReferences($userQuery) : [
            'has_reference' => false,
            'device' => null,
            'topic' => null,
        ];

        if (!empty($reference['has_reference']) && empty($reference['device']) && preg_match('/\b(прибор|ремонт|проблем|неисправ|уход)\b/ui', $query)) {
            return [
                'type' => 'context_missing',
                'message' => 'Не удалось определить, к какому предыдущему прибору относится запрос. Уточните серийный номер или номер корпуса.'
            ];
        }

        // Контекстные вопросы о причинах ремонта. Важно: после темы ремонтов
        // "причины ухода" означает причины отправки в ремонт, а не списание.
        if (($reference['device'] ?? null) &&
            preg_match('/(?:проблем|причин|почему|из-за чего|из за чего|основан).*(?:прибор|уход|ремонт|неисправ|полом)|(?:уход|ремонт|неисправ|полом).*(?:проблем|причин|почему|из-за чего|из за чего)/ui', $query) &&
            (($reference['topic'] ?? null) === 'repairs' || preg_match('/ремонт|уход|проблем|неисправ|полом/ui', $query))
        ) {
            return [
                'type' => 'repair_reasons_by_device',
                'serial_number' => $reference['device']['serial_number'] ?? null,
                'case_number' => $reference['device']['case_number'] ?? null,
                'context_reference' => true,
            ];
        }

        // Контекстные запросы на информацию о первом/втором/данном приборе.
        if (($reference['device'] ?? null) &&
            preg_match('/(?:полную|все).*?(?:информацию|данные).*?(?:перв|втор|трет|данн|эт|нему|него|прибор)/ui', $query)
        ) {
            if (!empty($reference['device']['serial_number'])) {
                return [
                    'type' => 'full_device_info_by_serial',
                    'serial_number' => $reference['device']['serial_number'],
                    'context_reference' => true,
                ];
            }
            if (!empty($reference['device']['case_number'])) {
                return [
                    'type' => 'full_device_info_by_case',
                    'case_number' => $reference['device']['case_number'],
                    'context_reference' => true,
                ];
            }
        }

        if (($reference['device'] ?? null) &&
            preg_match('/(?:информация|покажи|выдай|что известно).*?(?:перв|втор|трет|данн|эт|нему|него|прибор)/ui', $query)
        ) {
            if (!empty($reference['device']['serial_number'])) {
                return [
                    'type' => 'device_info_by_serial',
                    'serial_number' => $reference['device']['serial_number'],
                    'context_reference' => true,
                ];
            }
            if (!empty($reference['device']['case_number'])) {
                return [
                    'type' => 'device_info_by_case',
                    'case_number' => $reference['device']['case_number'],
                    'context_reference' => true,
                ];
            }
        }

        // Определяем тип запроса
        if (preg_match("/сколько.*приборов?.*типа?\s+(\w+)/ui", $query, $matches)) {
            return [
                'type' => 'count_devices_by_type',
                'device_type' => mb_strtoupper($matches[1])
            ];
        }

        if (preg_match("/сколько.*всего.*приборов?/ui", $query)) {
            return ['type' => 'count_all_devices'];
        }

        if (preg_match("/сколько.*ремонтов?/ui", $query)) {
            return ['type' => 'count_repairs'];
        }

        if (preg_match('/(?:какой|какого|какие|что за).*прибор.*(?:чаще|больше|много).*ремонт|(?:чаще|больше|много).*ремонт.*прибор|прибор.*уходил.*ремонт.*чаще/ui', $query)) {
            return ['type' => 'top_repaired_device'];
        }

        // Поиск полной информации по серийному номеру
        if (preg_match("/(?:полную|все).*?(?:информацию|данные).*?прибор.*?серийн.*?номер.*?(\d+)/ui", $query, $matches) ||
            preg_match("/полная.*?информация.*?прибор.*?(\d+)/ui", $query, $matches)) {
            return [
                'type' => 'full_device_info_by_serial',
                'serial_number' => $matches[1]
            ];
        }

        // Поиск полной информации по номеру корпуса
        if (preg_match("/(?:полную|все).*?(?:информацию|данные).*?прибор.*?номер.*корпуса?\s*([\w\-]+)/ui", $query, $matches) ||
            preg_match("/полная.*?информация.*?прибор.*?номер.*корпуса?\s*([\w\-]+)/ui", $query, $matches)) {
            return [
                'type' => 'full_device_info_by_case',
                'case_number' => trim($matches[1])
            ];
        }

        // Поиск краткой информации по серийному номеру - улучшенные регулярные выражения
        if (preg_match("/(?:информация|покажи|выдай).*?прибор.*?серийн.*?номер.*?(\d+)/ui", $query, $matches) ||
            preg_match("/прибор.*?серийн.*?номер.*?(\d+)/ui", $query, $matches) ||
            preg_match("/серийн.*?номер.*?(\d+)/ui", $query, $matches)) {
            return [
                'type' => 'device_info_by_serial',
                'serial_number' => $matches[1]
            ];
        }

        // Поиск краткой информации по номеру корпуса
        if (preg_match("/информация.*прибор.*номер.*корпуса?\s*([\w\-]+)/ui", $query, $matches) ||
            preg_match("/покажи.*прибор.*номер.*корпуса?\s*([\w\-]+)/ui", $query, $matches) ||
            preg_match("/выдай.*информацию.*прибор.*номер.*корпуса?\s*([\w\-]+)/ui", $query, $matches)) {
            return [
                'type' => 'device_info_by_case',
                'case_number' => trim($matches[1])
            ];
        }

        // Контекстный запрос на полную информацию (если ранее был найден прибор)
        if (preg_match("/(?:полную|все).*?(?:информацию|данные).*?(?:об этом|о данном|по этому|по данному|о приборе)/ui", $query)) {
            if ($this->last_device_serial_number) {
                return [
                    'type' => 'full_device_info_by_serial',
                    'serial_number' => $this->last_device_serial_number
                ];
            } elseif ($this->last_device_case_number) {
                return [
                    'type' => 'full_device_info_by_case',
                    'case_number' => $this->last_device_case_number
                ];
            }
        }

        // Контекстный запрос на краткую информацию (если ранее был найден прибор)
        if (preg_match("/(?:информация|покажи|выдай).*?(?:об этом|о данном|по этому|по данному|о приборе)/ui", $query)) {
            if ($this->last_device_serial_number) {
                return [
                    'type' => 'device_info_by_serial',
                    'serial_number' => $this->last_device_serial_number
                ];
            } elseif ($this->last_device_case_number) {
                return [
                    'type' => 'device_info_by_case',
                    'case_number' => $this->last_device_case_number
                ];
            }
        }

        if (preg_match("/список.*приборов?.*типа?\s+(\w+)/ui", $query, $matches)) {
            return [
                'type' => 'list_devices_by_type',
                'device_type' => mb_strtoupper($matches[1])
            ];
        }

        if (preg_match("/последние.*ремонты/ui", $query)) {
            return ['type' => 'recent_repairs'];
        }

        // Самый последний ремонт (единственное число)
        if (preg_match("/(?:самый\s+)?последний.*ремонт/ui", $query)) {
            return ['type' => 'last_repair'];
        }

        // Приборы в ремонте (без release_date)
        if (preg_match("/прибор.*в\s+ремонте|в\s+ремонте.*прибор/ui", $query)) {
            return ['type' => 'devices_in_repair'];
        }

        // Приборы на испытаниях
        if (preg_match("/прибор.*на\s+испытаниях|на\s+испытаниях.*прибор/ui", $query)) {
            return ['type' => 'devices_in_testing'];
        }

        // Диалоговые/общие запросы о системе
        if (preg_match("/(?:кто\s+ты|ты\s+кто|расскажи\s+о\s+себе|что\s+ты\s+умеешь|какие\s+возможности|помощь|help)/ui", $query)) {
            return ['type' => 'about_agent'];
        }

        // Приветствия
        if (preg_match("/^(?:привет|здравствуй|добрый\s+день|доброе\s+утро|добрый\s+вечер|hello|hi)$/ui", trim($query))) {
            return ['type' => 'greeting'];
        }

        // Прощания
        if (preg_match("/^(?:пока|до\s+свидания|спасибо|благодарю|спс)$/ui", trim($query))) {
            return ['type' => 'farewell'];
        }

        // Запросы о наличии приборов с определенными характеристиками
        if (preg_match("/есть\s+ли.*прибор.*с\s+(?:частотой|диаметром|базой|длиной)/ui", $query)) {
            return ['type' => 'flexible_device_search', 'query' => $query];
        }

        if (preg_match("/статистика.*приборов?/ui", $query)) {
            return ['type' => 'device_statistics'];
        }

        return ['type' => 'unknown'];
    }

    /**
     * Получает серийный номер последнего прибора
     */
    public function getLastDeviceSerialNumber(): ?string
    {
        return $this->last_device_serial_number;
    }

    /**
     * Получает номер корпуса последнего прибора
     */
    public function getLastDeviceCaseNumber(): ?string
    {
        return $this->last_device_case_number;
    }
}
