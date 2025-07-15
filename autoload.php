<?php
/**
 * Автозагрузчик для пространства имён App\
 * Регистрирует PSR-4 автозагрузку без Composer
 */
spl_autoload_register(function ($class) {
    // Пространство имён проекта
    $prefix = 'App\\';
    // Базовая директория для пространств имён
    $base_dir = __DIR__ . '/src/';

    // Проверяем, начинается ли класс с префикса
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // нет — выходим
        return;
    }

    // Получаем относительное имя класса
    $relative_class = substr($class, $len);

    // Заменяем разделители пространств имён на разделители директорий
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Подключаем файл, если он существует
    if (file_exists($file)) {
        require $file;
    }
});