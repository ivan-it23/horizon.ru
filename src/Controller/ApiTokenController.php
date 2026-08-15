<?php
namespace App\Controller;

use App\Controller\BaseController;
use App\Service\ApiTokenService;

/**
 * Управление токенами доступа из раздела «Профиль».
 *
 * Работает по сессии сайта: пользователь уже вошёл логином и паролем.
 * Сам обмен приложения с сервером идёт через ApiController по токену.
 *
 * Маршруты: front.php?c=ApiToken&a=<действие>
 */
class ApiTokenController extends BaseController
{
    public function list(): void
    {
        $userId = $this->currentUserId();
        $conn = $this->db();

        $this->jsonResponse([
            'success' => true,
            'tokens'  => ApiTokenService::listForUser($conn, $userId),
        ]);
    }

    public function create(): void
    {
        $userId = $this->currentUserId();
        $this->requirePost();

        // Токены создают редко, поэтому лимит намеренно жёсткий.
        if (!$this->checkRateLimit($this->getClientIP(), 'api_token_create', 10, 3600)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Слишком много попыток создания токена. Попробуйте через час.',
            ], 429);
        }

        $name = $this->sanitizeString($_POST['name'] ?? '', 120);
        if ($name === '') {
            $name = 'Визуализация данных';
        }

        $conn = $this->db();
        $issued = ApiTokenService::issue($conn, $userId, $name);
        if ($issued === null) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Не удалось создать токен.',
            ], 500);
        }

        $this->logSecurityEvent('api_token_created', [
            'user_id'  => $userId,
            'token_id' => $issued['id'],
            'name'     => $name,
        ]);

        $this->jsonResponse([
            'success' => true,
            'token'   => $issued['token'],
            'id'      => $issued['id'],
            'name'    => $name,
            'message' => 'Токен создан. Скопируйте его сейчас — позже показать его будет нельзя.',
        ]);
    }

    public function revoke(): void
    {
        $userId = $this->currentUserId();
        $this->requirePost();

        $tokenId = $this->validateInt($_POST['id'] ?? 0, 1);
        if (!$tokenId) {
            $this->jsonResponse(['success' => false, 'message' => 'Неверный идентификатор токена.'], 400);
        }

        $conn = $this->db();
        if (!ApiTokenService::revoke($conn, $userId, (int)$tokenId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Токен не найден или уже отозван.'], 404);
        }

        $this->logSecurityEvent('api_token_revoked', [
            'user_id'  => $userId,
            'token_id' => (int)$tokenId,
        ]);

        $this->jsonResponse(['success' => true, 'message' => 'Токен отозван.']);
    }

    // ------------------------------------------------------------------

    private function currentUserId(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Требуется вход в систему.'], 401);
        }

        return (int)$_SESSION['user']['id'];
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Требуется метод POST.'], 405);
        }
    }

    /**
     * @return \mysqli
     */
    private function db()
    {
        require_once __DIR__ . '/../../db_connection.php';
        // require_once не выполняет файл повторно, поэтому $conn в этой
        // области может отсутствовать — тогда открываем соединение сами.
        if (isset($conn) && $conn instanceof \mysqli) {
            return $conn;
        }

        if (function_exists('createDatabaseConnection')) {
            return createDatabaseConnection();
        }

        $this->jsonResponse(['success' => false, 'message' => 'Нет подключения к базе данных.'], 500);
    }
}
