<?php
namespace App\Service;

/**
 * Класс для форматирования ответов ИИ-агента
 * Преобразует данные в читаемый Markdown-формат
 */
class ResponseFormatter
{
    /**
     * Форматирует информацию о приборе для вывода (краткая)
     */
    public function formatDeviceInfo($device, $composition, $repairs): array
    {
        $message = "**ИНФОРМАЦИЯ О ПРИБОРЕ**\n\n";

        $message .= "**ПАРАМЕТРЫ ПРИБОРА:**\n";
        $message .= "• Тип прибора: {$device['device_type']}\n";
        $message .= "• Серийный номер: {$device['serial_number']}\n";
        $message .= "• Номер корпуса: {$device['case_number']}\n";
        $message .= "• Номинальный диаметр: {$device['nominal_diameter']} мм\n";
        $message .= "• Количество передатчиков: {$device['transmitter_count']}\n";
        $message .= "• Длина: {$device['length_mm']} мм\n";
        $message .= "• Диаметр: {$device['diameter_mm']} мм\n";
        $message .= "• Верхняя резьба: {$device['upper_thread']}\n";
        $message .= "• Нижняя резьба: {$device['lower_thread']}\n";
        $message .= "• Частота 1: {$device['frequency1_khz']} кГц\n";
        $message .= "• Частота 2: {$device['frequency2_khz']} кГц\n";

        // Базы приёмников
        if (!empty($device['receiver_base1_mm'])) {
            $message .= "• База приёмника 1: {$device['receiver_base1_mm']} мм\n";
        }
        if (!empty($device['receiver_base2_mm'])) {
            $message .= "• База приёмника 2: {$device['receiver_base2_mm']} мм\n";
        }
        if (!empty($device['receiver_base3_mm'])) {
            $message .= "• База приёмника 3: {$device['receiver_base3_mm']} мм\n";
        }

        // Базы передатчиков
        if (!empty($device['transmitter_base1_mm'])) {
            $message .= "• База передатчика 1: {$device['transmitter_base1_mm']} мм\n";
        }
        if (!empty($device['transmitter_base2_mm'])) {
            $message .= "• База передатчика 2: {$device['transmitter_base2_mm']} мм\n";
        }
        if (!empty($device['transmitter_base3_mm'])) {
            $message .= "• База передатчика 3: {$device['transmitter_base3_mm']} мм\n";
        }
        if (!empty($device['transmitter_base4_mm'])) {
            $message .= "• База передатчика 4: {$device['transmitter_base4_mm']} мм\n";
        }
        if (!empty($device['transmitter_base5_mm'])) {
            $message .= "• База передатчика 5: {$device['transmitter_base5_mm']} мм\n";
        }

        $message .= "• Дата записи: {$device['record_date']}\n";
        $message .= "• Создатель записи: {$device['creator_lastname']}\n";

        if (!empty($device['additional_info'])) {
            $message .= "• Дополнительная информация: {$device['additional_info']}\n";
        }

        // Состав прибора
        if (!empty($composition)) {
            $message .= "\n**СОСТАВ ПРИБОРА:**\n";
            foreach ($composition as $comp) {
                $message .= "• {$comp['board_name']}: {$comp['type']}";
                if (!empty($comp['version']) && $comp['version'] !== '-') {
                    $message .= " (версия: {$comp['version']})";
                }
                if (!empty($comp['serial_number']) && $comp['serial_number'] !== '-') {
                    $message .= " [S/N: {$comp['serial_number']}]";
                }
                $message .= "\n";
            }
        } else {
            $message .= "\n**СОСТАВ ПРИБОРА:**\n• Нет данных о составе.\n";
        }

        // История ремонтов
        if (!empty($repairs)) {
            $message .= "\n**ИСТОРИЯ РЕМОНТОВ:**\n";
            foreach ($repairs as $repair) {
                $message .= "**Ремонт от {$repair['admission_date']}**\n";
                if (!empty($repair['repair_reason'])) {
                    $message .= "   Причина ремонта: {$repair['repair_reason']}\n";
                }
                if (!empty($repair['failure_reason'])) {
                    $message .= "   Причина неисправности: {$repair['failure_reason']}\n";
                }
                if (!empty($repair['release_date']) && $repair['release_date'] !== '0000-00-00') {
                    $message .= "   Дата выпуска: {$repair['release_date']}\n";
                }
                if (!empty($repair['actions_description'])) {
                    $message .= "   Выполненные действия: {$repair['actions_description']}\n";
                }
                if (!empty($repair['repair_creator'])) {
                    $message .= "   Ответственный: {$repair['repair_creator']}\n";
                }
            }
        } else {
            $message .= "\n**ИСТОРИЯ РЕМОНТОВ:**\n• Нет данных о ремонтах.\n";
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * Форматирует полную информацию о приборе для вывода
     */
    public function formatFullDeviceInfo($data): array
    {
        $message = "**ПОЛНАЯ ИНФОРМАЦИЯ О ПРИБОРЕ**\n\n";

        $device = $data['device'];
        $composition = $data['composition'];
        $repairs = $data['repairs'];
        $production_discontinuation = $data['production_discontinuation'];
        $testing_data = $data['testing_data'];
        $inspection_data = $data['inspection_data'];
        $termination_data = $data['termination_data'];
        $additional_data = $data['additional_data'];

        // Параметры прибора (из formatDeviceInfo)
        $message .= "**ПАРАМЕТРЫ ПРИБОРА:**\n";
        $message .= "• Тип прибора: {$device['device_type']}\n";
        $message .= "• Серийный номер: {$device['serial_number']}\n";
        $message .= "• Номер корпуса: {$device['case_number']}\n";
        $message .= "• Номинальный диаметр: {$device['nominal_diameter']} мм\n";
        $message .= "• Количество передатчиков: {$device['transmitter_count']}\n";
        $message .= "• Длина: {$device['length_mm']} мм\n";
        $message .= "• Диаметр: {$device['diameter_mm']} мм\n";
        $message .= "• Верхняя резьба: {$device['upper_thread']}\n";
        $message .= "• Нижняя резьба: {$device['lower_thread']}\n";
        $message .= "• Частота 1: {$device['frequency1_khz']} кГц\n";
        $message .= "• Частота 2: {$device['frequency2_khz']} кГц\n";

        // Базы приёмников
        if (!empty($device['receiver_base1_mm'])) {
            $message .= "• База приёмника 1: {$device['receiver_base1_mm']} мм\n";
        }
        if (!empty($device['receiver_base2_mm'])) {
            $message .= "• База приёмника 2: {$device['receiver_base2_mm']} мм\n";
        }
        if (!empty($device['receiver_base3_mm'])) {
            $message .= "• База приёмника 3: {$device['receiver_base3_mm']} мм\n";
        }

        // Базы передатчиков
        if (!empty($device['transmitter_base1_mm'])) {
            $message .= "• База передатчика 1: {$device['transmitter_base1_mm']} мм\n";
        }
        if (!empty($device['transmitter_base2_mm'])) {
            $message .= "• База передатчика 2: {$device['transmitter_base2_mm']} мм\n";
        }
        if (!empty($device['transmitter_base3_mm'])) {
            $message .= "• База передатчика 3: {$device['transmitter_base3_mm']} мм\n";
        }
        if (!empty($device['transmitter_base4_mm'])) {
            $message .= "• База передатчика 4: {$device['transmitter_base4_mm']} мм\n";
        }
        if (!empty($device['transmitter_base5_mm'])) {
            $message .= "• База передатчика 5: {$device['transmitter_base5_mm']} мм\n";
        }

        $message .= "• Дата записи: {$device['record_date']}\n";
        $message .= "• Создатель записи: {$device['creator_lastname']}\n";

        if (!empty($device['additional_info'])) {
            $message .= "• Дополнительная информация: {$device['additional_info']}\n";
        }

        // Состав прибора
        if (!empty($composition)) {
            $message .= "\n**СОСТАВ ПРИБОРА:**\n";
            foreach ($composition as $comp) {
                $message .= "• {$comp['board_name']}: {$comp['type']}";
                if (!empty($comp['version']) && $comp['version'] !== '-') {
                    $message .= " (версия: {$comp['version']})";
                }
                if (!empty($comp['serial_number']) && $comp['serial_number'] !== '-') {
                    $message .= " [S/N: {$comp['serial_number']}]";
                }
                $message .= "\n";
            }
        } else {
            $message .= "\n**СОСТАВ ПРИБОРА:**\n• Нет данных о составе.\n";
        }

        // История ремонтов
        if (!empty($repairs)) {
            $message .= "\n**ИСТОРИЯ РЕМОНТОВ:**\n";
            foreach ($repairs as $repair) {
                $message .= "**Ремонт от {$repair['admission_date']}**\n";
                if (!empty($repair['repair_reason'])) {
                    $message .= "   Причина ремонта: {$repair['repair_reason']}\n";
                }
                if (!empty($repair['failure_reason'])) {
                    $message .= "   Причина неисправности: {$repair['failure_reason']}\n";
                }
                if (!empty($repair['release_date']) && $repair['release_date'] !== '0000-00-00') {
                    $message .= "   Дата выпуска: {$repair['release_date']}\n";
                }
                if (!empty($repair['actions_description'])) {
                    $message .= "   Выполненные действия: {$repair['actions_description']}\n";
                }
                if (!empty($repair['repair_creator'])) {
                    $message .= "   Ответственный: {$repair['repair_creator']}\n";
                }
            }
        } else {
            $message .= "\n**ИСТОРИЯ РЕМОНТОВ:**\n• Нет данных о ремонтах.\n";
        }

        // Сведения о выходе из производства
        if (!empty($production_discontinuation)) {
            $message .= "\n**СВЕДЕНИЯ О ВЫХОДЕ ИЗ ПРОИЗВОДСТВА:**\n";
            foreach ($production_discontinuation as $pd) {
                $message .= "**Дата выхода из производства:** {$pd['discontinuation_date']}\n";
                if (!empty($pd['creator_surname'])) {
                    $message .= "   Создатель записи: {$pd['creator_surname']}\n";
                }
            }
        } else {
            $message .= "\n**СВЕДЕНИЯ О ВЫХОДЕ ИЗ ПРОИЗВОДСТВА:**\n• Нет данных о выходе из производства.\n";
        }

        // Сведения об испытаниях
        if (!empty($testing_data)) {
            $message .= "\n**СВЕДЕНИЯ ОБ ИСПЫТАНИЯХ:**\n";
            foreach ($testing_data as $td) {
                $message .= "**Дата испытания:** {$td['test_date']}\n";
                if (!empty($td['test_result'])) {
                    $message .= "   Результат испытания: {$td['test_result']}\n";
                }
                if (!empty($td['creator_surname'])) {
                    $message .= "   Создатель записи: {$td['creator_surname']}\n";
                }
            }
        } else {
            $message .= "\n**СВЕДЕНИЯ ОБ ИСПЫТАНИЯХ:**\n• Нет данных об испытаниях.\n";
        }

        // Сведения о неразрушающем контроле корпуса
        if (!empty($inspection_data)) {
            $message .= "\n**СВЕДЕНИЯ О НЕРАЗРУШАЮЩЕМ КОНТРОЛЕ КОРПУСА:**\n";
            foreach ($inspection_data as $id) {
                $message .= "**Дата контроля:** {$id['inspection_date']}\n";
                if (!empty($id['inspection_result'])) {
                    $message .= "   Результат контроля: {$id['inspection_result']}\n";
                }
                if (!empty($id['creator_surname'])) {
                    $message .= "   Создатель записи: {$id['creator_surname']}\n";
                }
            }
        } else {
            $message .= "\n**СВЕДЕНИЯ О НЕРАЗРУШАЮЩЕМ КОНТРОЛЕ КОРПУСА:**\n• Нет данных о неразрушающем контроле.\n";
        }

        // Сведения о прекращении эксплуатации
        if (!empty($termination_data)) {
            $message .= "\n**СВЕДЕНИЯ О ПРЕКРАЩЕНИИ ЭКСПЛУАТАЦИИ:**\n";
            foreach ($termination_data as $td) {
                $message .= "**Дата прекращения эксплуатации:** {$td['termination_date']}\n";
                if (!empty($td['termination_reason'])) {
                    $message .= "   Причина прекращения: {$td['termination_reason']}\n";
                }
                if (!empty($td['creator_surname'])) {
                    $message .= "   Создатель записи: {$td['creator_surname']}\n";
                }
            }
        } else {
            $message .= "\n**СВЕДЕНИЯ О ПРЕКРАЩЕНИИ ЭКСПЛУАТАЦИИ:**\n• Нет данных о прекращении эксплуатации.\n";
        }

        // Дополнительные сведения
        if (!empty($additional_data)) {
            $message .= "\n**ДОПОЛНИТЕЛЬНЫЕ СВЕДЕНИЯ:**\n";
            foreach ($additional_data as $ad) {
                $message .= "**Дата записи:** {$ad['additional_date']}\n";
                if (!empty($ad['additional_content'])) {
                    $message .= "   Содержание: {$ad['additional_content']}\n";
                }
                if (!empty($ad['creator_surname'])) {
                    $message .= "   Создатель записи: {$ad['creator_surname']}\n";
                }
            }
        } else {
            $message .= "\n**ДОПОЛНИТЕЛЬНЫЕ СВЕДЕНИЯ:**\n• Нет дополнительных сведений.\n";
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * Форматирует результаты в виде Markdown-таблицы
     */
    public function formatResultsAsMarkdownTable($data): string
    {
        if (empty($data)) {
            return "Нет данных для отображения.";
        }

        $table = "| ";
        $separator = "| ";
        
        // Заголовки
        $headers = array_keys($data[0]);
        foreach ($headers as $header) {
            $table .= $header . " | ";
            $separator .= "--- | ";
        }
        $table .= "\n" . $separator . "\n";

        // Данные
        foreach ($data as $row) {
            $table .= "| ";
            foreach ($row as $value) {
                $table .= $value . " | ";
            }
            $table .= "\n";
        }

        return $table;
    }
} 