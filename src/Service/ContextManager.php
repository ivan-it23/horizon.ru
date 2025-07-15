<?php
namespace App\Service;

/**
 * Класс для управления контекстом диалога с ИИ-агентом
 * Сохраняет и извлекает информацию о последнем найденном приборе
 */
class ContextManager
{
    /**
     * Извлекает из ответа ИИ серийный номер или номер корпуса и сохраняет в сессию
     * 
     * @param string $responseText Текст ответа от ИИ
     * @return bool True, если контекст был найден и сохранен, иначе false
     */
    public function extractAndSaveContext($responseText): bool
    {
        $serialNumber = null;
        $caseNumber = null;

        // Ищем "Серийный номер: [номер]"
        if (preg_match('/Серийный номер:\s*(\w+)/ui', $responseText, $matches)) {
            $serialNumber = $matches[1];
        }

        // Ищем "Номер корпуса: [номер]"
        if (preg_match('/Номер корпуса:\s*([\w\-]+)/ui', $responseText, $matches)) {
            $caseNumber = $matches[1];
        }

        if ($serialNumber || $caseNumber) {
            $_SESSION['current_device_context'] = [
                'serial_number' => $serialNumber,
                'case_number' => $caseNumber,
                'timestamp' => time()
            ];
            return true;
        }

        return false;
    }

    /**
     * Получает текущий контекст устройства из сессии
     * 
     * @return array|null Массив с контекстом или null, если контекст не найден
     */
    public function getCurrentContext(): ?array
    {
        if (isset($_SESSION['current_device_context'])) {
            $context = $_SESSION['current_device_context'];
            
            // Проверяем, не устарел ли контекст (30 минут)
            if (time() - $context['timestamp'] > 1800) {
                $this->clearContext();
                return null;
            }
            
            return $context;
        }
        
        return null;
    }

    /**
     * Устанавливает контекст устройства
     * 
     * @param string|null $serialNumber Серийный номер
     * @param string|null $caseNumber Номер корпуса
     */
    public function setContext(?string $serialNumber, ?string $caseNumber): void
    {
        $_SESSION['current_device_context'] = [
            'serial_number' => $serialNumber,
            'case_number' => $caseNumber,
            'timestamp' => time()
        ];
    }

    /**
     * Очищает текущий контекст
     */
    public function clearContext(): void
    {
        unset($_SESSION['current_device_context']);
    }

    /**
     * Проверяет, есть ли активный контекст
     * 
     * @return bool True, если есть активный контекст
     */
    public function hasContext(): bool
    {
        return $this->getCurrentContext() !== null;
    }

    /**
     * Получает серийный номер из текущего контекста
     * 
     * @return string|null Серийный номер или null
     */
    public function getSerialNumber(): ?string
    {
        $context = $this->getCurrentContext();
        return $context['serial_number'] ?? null;
    }

    /**
     * Получает номер корпуса из текущего контекста
     * 
     * @return string|null Номер корпуса или null
     */
    public function getCaseNumber(): ?string
    {
        $context = $this->getCurrentContext();
        return $context['case_number'] ?? null;
    }

    /**
     * Обновляет временную метку контекста
     */
    public function updateContextTimestamp(): void
    {
        if (isset($_SESSION['current_device_context'])) {
            $_SESSION['current_device_context']['timestamp'] = time();
        }
    }
} 