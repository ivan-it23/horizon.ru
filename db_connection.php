<?php
// Подключаем конфигурацию
require_once 'config.php';

// Функция для создания подключения к базе данных
function createDatabaseConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Устанавливаем кодировку UTF-8
        if (!$conn->set_charset("utf8mb4")) {
            throw new Exception("Error setting charset: " . $conn->error);
        }

        return $conn;

    } catch (Exception $e) {
        logError("Database connection error: " . $e->getMessage(), __FILE__, __LINE__);

        if (DEBUG_MODE) {
            die("Database connection failed. Check logs for details.");
        } else {
            die("Service temporarily unavailable. Please try again later.");
        }
    }
}

// Создаем подключение
$conn = createDatabaseConnection();

// Функция для безопасного выполнения запросов
function executeQuery($conn, $sql, $params = [], $types = '') {
    try {
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        return $stmt;

    } catch (Exception $e) {
        logError("Query execution error: " . $e->getMessage() . " SQL: " . $sql, __FILE__, __LINE__);
        throw $e;
    }
}

// Функция для получения результатов SELECT запроса
function fetchResults($conn, $sql, $params = [], $types = '') {
    try {
        $stmt = executeQuery($conn, $sql, $params, $types);
        // Use get_result if available (requires mysqlnd), fallback otherwise
        if (method_exists($stmt, 'get_result')) {
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        } else {
            // Fallback for non-mysqlnd: bind results manually
            $stmt->store_result();
            $meta = $stmt->result_metadata();
            $fields = $meta->fetch_fields();
            $row = [];
            $bindParams = [];
            foreach ($fields as $field) {
                $bindParams[] = &$row[$field->name];
            }
            call_user_func_array([$stmt, 'bind_result'], $bindParams);
            $data = [];
            while ($stmt->fetch()) {
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        }
    } catch (Exception $e) {
        throw $e;
    }
}
?>
