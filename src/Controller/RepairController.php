<?php
namespace App\Controller;

use App\Controller\BaseController;

class RepairController extends BaseController
{
    /**
     * Create new repair record
     */
    public function create(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        // Check auth
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Доступ запрещен.']);
            return;
        }

        $serial_number      = $_POST['serial_number']      ?? '';
        $case_number        = $_POST['case_number']        ?? '';
        $repair_reason      = $_POST['repair_reason']      ?? null;
        $failure_reason     = $_POST['failure_reason']     ?? null;
        $admission_date     = $_POST['admission_date']     ?? null;
        $release_date       = $_POST['release_date']       ?? null;
        $actions_description= $_POST['actions_description']?? null;
        $repair_creator     = $_POST['repair_creator']     ?? null;
        $creator_id         = $_SESSION['user']['id'];

        if (empty($serial_number) || empty($case_number)) {
            echo json_encode(['success' => false, 'message' => 'Ошибка: Не указан серийный номер или номер корпуса.']);
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
            return;
        }

        try {
            $sql = "INSERT INTO repair_data (serial_number, case_number, repair_reason, failure_reason, admission_date, release_date, actions_description, repair_creator, creator_id)"
                 . " VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?)";
            executeQuery(
                $conn,
                $sql,
                [$serial_number, $case_number, $repair_reason, $failure_reason, $admission_date, $release_date, $actions_description, $repair_creator, $creator_id],
                'ssssssssi'
            );
            echo json_encode(['success' => true, 'message' => 'Новая запись о ремонте успешно создана!']);
        } catch (\Exception $e) {
            error_log('RepairController::create error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ошибка при создании записи: ' . $e->getMessage()]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Update existing repair record
     */
    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        // Check auth and parameters
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Доступ запрещен.']);
            return;
        }
        $repair_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$repair_id) {
            echo json_encode(['success' => false, 'message' => 'Отсутствует ID записи.']);
            return;
        }
        $user_id = $_SESSION['user']['id'];
        $new_data = [
            'repair_reason'      => $_POST['repair_reason']      ?? null,
            'failure_reason'     => $_POST['failure_reason']     ?? null,
            'admission_date'     => $_POST['admission_date']     ?? null,
            'release_date'       => $_POST['release_date']       ?? null,
            'actions_description'=> $_POST['actions_description']?? null
        ];

        try {
            $conn->begin_transaction();
            // Get old data
            $sqlSelect = "SELECT repair_reason, failure_reason, admission_date, release_date, actions_description FROM repair_data WHERE id = ?";
            $stmt = $conn->prepare($sqlSelect);
            $stmt->bind_param('i', $repair_id);
            $stmt->execute();
            $old_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Insert history
            $sqlHist = "INSERT INTO repair_data_history (repair_data_id, repair_reason, failure_reason, admission_date, release_date, actions_description, changed_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlHist);
            $stmt->bind_param('isssssi', $repair_id, $old_data['repair_reason'], $old_data['failure_reason'], $old_data['admission_date'], $old_data['release_date'], $old_data['actions_description'], $user_id);
            $stmt->execute();
            $stmt->close();

            // Update main record
            $sqlUpdate = "UPDATE repair_data SET repair_reason = ?, failure_reason = ?, admission_date = NULLIF(?, ''), release_date = NULLIF(?, ''), actions_description = ? WHERE id = ?";
            $stmt = $conn->prepare($sqlUpdate);
            $stmt->bind_param('sssssi', $new_data['repair_reason'], $new_data['failure_reason'], $new_data['admission_date'], $new_data['release_date'], $new_data['actions_description'], $repair_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Изменения успешно сохранены!']);
        } catch (\Exception $e) {
            $conn->rollback();
            error_log('RepairController::update error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении изменений: ' . $e->getMessage()]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Fetch repair history (JSON)
     */
    public function history(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $serial_number = $_POST['serial_number'] ?? '';
        $case_number   = $_POST['case_number']   ?? '';
        if (empty($serial_number) || empty($case_number)) {
            echo json_encode(['error' => 'Отсутствуют серийный номер или номер корпуса.']);
            return;
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT * FROM repair_data WHERE serial_number = ? AND case_number = ? ORDER BY created_at DESC",
                [$serial_number, $case_number],
                'ss'
            );
            echo json_encode($data);
        } catch (\Exception $e) {
            error_log('RepairController::history error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки истории: ' . $e->getMessage()]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Fetch edit history for a repair record (JSON)
     */
    public function editHistory(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../../db_connection.php';

        $repair_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$repair_id) {
            echo json_encode(['error' => 'ID записи не указан.']);
            return;
        }

        try {
            $data = fetchResults(
                $conn,
                "SELECT h.*, u.lastname, u.firstname, u.middlename
                 FROM repair_data_history h
                 LEFT JOIN users u ON h.changed_by_user_id = u.id
                 WHERE h.repair_data_id = ?
                 ORDER BY h.changed_at DESC",
                [$repair_id],
                'i'
            );
            echo json_encode($data);
        } catch (\Exception $e) {
            error_log('RepairController::editHistory error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки истории правок: ' . $e->getMessage()]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }

    /**
     * Delete a repair record and its history (JSON)
     */
    public function delete(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        session_start();
        require_once __DIR__ . '/../../db_connection.php';

        $repair_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$repair_id) {
            echo json_encode(['success' => false, 'message' => 'ID записи не указан.']);
            return;
        }

        try {
            // Delete history first
            executeQuery($conn,
                'DELETE FROM repair_data_history WHERE repair_data_id = ?',
                [$repair_id], 'i'
            );
            // Delete main record
            executeQuery($conn,
                'DELETE FROM repair_data WHERE id = ?',
                [$repair_id], 'i'
            );
            echo json_encode(['success' => true, 'message' => 'Запись и история удалены.']);
        } catch (\Exception $e) {
            error_log('RepairController::delete error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Ошибка при удалении: ' . $e->getMessage()]);
        } finally {
            if (isset($conn) && $conn instanceof \mysqli) $conn->close();
        }
    }
} 