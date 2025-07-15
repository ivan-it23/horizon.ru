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