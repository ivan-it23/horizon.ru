<?php
// Файл конфигурации шаблонов ответов ИИ-агента
return [
    // Подсчет приборов по типу
    'count_devices_by_type' => "В базе данных найдено {count} приборов типа {device_type}.\n\nОтвет сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",
    // Общее количество приборов
    'count_all_devices'      => "Общее количество приборов в базе данных: {count}.\n\nОтвет сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",
    // Подсчет ремонтов
    'count_repairs'          => "Общее количество ремонтов: {count}.\n\nОтвет сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",

    // Краткая информация о приборе
    'device_info_short'      => "📋 **ИНФОРМАЦИЯ О ПРИБОРЕ**\n\n"
                             . "**ПАРАМЕТРЫ ПРИБОРА:**\n"
                             . "• Тип прибора: {device_type}\n"
                             . "• Серийный номер: {serial_number}\n"
                             . "• Номер корпуса: {case_number}\n"
                             . "{additional_params}\n\n"
                             . "**СОСТАВ ПРИБОРА:**\n"
                             . "{composition_list}\n\n"
                             . "{repairs_section}\n\n"
                             . "Ответ сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",

    // Полная информация о приборе
    'device_info_full'       => "📋 **ПОЛНАЯ ИНФОРМАЦИЯ О ПРИБОРЕ**\n\n"
                             . "**ПАРАМЕТРЫ ПРИБОРА:**\n"
                             . "• Тип прибора: {device_type}\n"
                             . "• Серийный номер: {serial_number}\n"
                             . "• Номер корпуса: {case_number}\n"
                             . "{additional_params}\n\n"
                             . "**СОСТАВ ПРИБОРА:**\n"
                             . "{composition_list}\n\n"
                             . "**ИСТОРИЯ РЕМОНТОВ:**\n"
                             . "{repairs_list}\n\n"
                             . "**СВЕДЕНИЯ О ВЫХОДЕ ИЗ ПРОИЗВОДСТВА:**\n"
                             . "{production_discontinuation_list}\n\n"
                             . "**СВЕДЕНИЯ ОБ ИСПЫТАНИЯХ:**\n"
                             . "{testing_data_list}\n\n"
                             . "**СВЕДЕНИЯ О НЕРАЗРУШАЮЩЕМ КОНТРОЛЕ КОРПУСА:**\n"
                             . "{inspection_data_list}\n\n"
                             . "**СВЕДЕНИЯ О ПРЕКРАЩЕНИИ ЭКСПЛУАТАЦИИ:**\n"
                             . "{termination_data_list}\n\n"
                             . "**ДОПОЛНИТЕЛЬНЫЕ СВЕДЕНИЯ:**\n"
                             . "{additional_data_list}\n\n"
                             . "Ответ сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",

    // Статистика приборов
    'device_statistics'      => "📊 **СТАТИСТИКА ПРИБОРОВ:**\n\n"
                             . "**Общее количество приборов:** {total_devices}\n\n"
                             . "**Распределение по типам:**\n"
                             . "• LWD: {count_LWD} шт.\n"
                             . "• Картограф: {count_Cartographer} шт.\n"
                             . "• Автономный: {count_Autonomous} шт.\n\n"
                             . "**Общее количество ремонтов:** {count_repairs}\n\n"
                             . "Ответ сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",

    // Список приборов по типу
    'list_devices_by_type'   => "📋 **СПИСОК ПРИБОРОВ ТИПА {device_type}:**\n\n{device_list}\n\n"
                             . "Ответ сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",

    // Последние ремонты
    'recent_repairs'         => "🔧 **Последние ремонты:**\n\n{repairs_list}\n\n"
                             . "Ответ сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",

    // Универсальный шаблон для обертки произвольных сообщений
    'generic'                => "{message}\n\nОтвет сформирован {timestamp}, пользователь: {user_name}. Подробнее: {log_link}",
]; 