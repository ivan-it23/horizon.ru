<?php
namespace App\Service;

use RuntimeException;
use InvalidArgumentException;

class ResponseTemplateManager
{
    /**
     * @var array<string,string>
     */
    private $templates = [];

    public function __construct()
    {
        // Путь к файлу с шаблонами
        $configPath = ROOT_PATH . '/ai_agent/config/response_templates.php';
        if (!file_exists($configPath)) {
            throw new RuntimeException("Response templates config not found at {$configPath}");
        }
        $loaded = include $configPath;
        if (!is_array($loaded)) {
            throw new RuntimeException('Invalid response templates format; expected array.');
        }
        $this->templates = $loaded;
    }

    /**
     * Рендерит шаблон по ключу и контексту плейсхолдеров
     *
     * @param string $key Ключ шаблона
     * @param array<string,mixed> $context Значения для подстановки плейсхолдеров
     * @return string Сформированный текст ответа
     */
    public function render(string $key, array $context): string
    {
        if (!isset($this->templates[$key])) {
            throw new InvalidArgumentException("Response template for key '{$key}' not found.");
        }
        $template = $this->templates[$key];
        // Подготавливаем замены: оборачиваем ключи в фигурные скобки
        $replacements = [];
        foreach ($context as $placeholder => $value) {
            $replacements['{' . $placeholder . '}'] = $value;
        }
        return strtr($template, $replacements);
    }
} 