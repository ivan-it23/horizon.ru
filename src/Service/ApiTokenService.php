<?php
namespace App\Service;

/**
 * Токены доступа для приложения «Визуализация данных».
 *
 * Пользователь создаёт токен в разделе «Профиль», копирует его один раз и
 * вставляет в приложении. В базе хранится только SHA-256 токена: восстановить
 * исходное значение из базы нельзя, поэтому утечка дампа не даёт доступа к API.
 */
class ApiTokenService
{
    /** Префикс токена — помогает опознать его в чужом тексте. */
    const TOKEN_PREFIX = 'amkdv_';

    /** Загрузок файлов в час на один токен. */
    const RATE_LIMIT_PER_HOUR = 20;

    /**
     * Создаёт таблицу токенов, если её ещё нет.
     */
    public static function ensureTable(\mysqli $conn): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `api_tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `token_hash` char(64) NOT NULL,
            `token_prefix` varchar(16) NOT NULL DEFAULT '',
            `name` varchar(255) NOT NULL DEFAULT '',
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            `last_used_at` datetime DEFAULT NULL,
            `revoked_at` datetime DEFAULT NULL,
            `usage_window_start` datetime DEFAULT NULL,
            `usage_count` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_token_hash` (`token_hash`),
            KEY `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";

        return (bool)$conn->query($sql);
    }

    /**
     * Выпускает новый токен. Полное значение возвращается ровно один раз —
     * в базе остаётся только его хэш.
     *
     * @return array|null ['id' => int, 'token' => string, 'prefix' => string]
     */
    public static function issue(\mysqli $conn, int $userId, string $name)
    {
        if (!self::ensureTable($conn)) {
            return null;
        }

        $secret = bin2hex(random_bytes(24));
        $token  = self::TOKEN_PREFIX . $secret;
        $hash   = hash('sha256', $token);
        $prefix = substr($secret, 0, 8);

        $stmt = $conn->prepare(
            "INSERT INTO api_tokens (user_id, token_hash, token_prefix, name, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('isss', $userId, $hash, $prefix, $name);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $id = (int)$conn->insert_id;
        $stmt->close();

        return ['id' => $id, 'token' => $token, 'prefix' => $prefix];
    }

    /**
     * Находит действующий токен и данные его владельца.
     *
     * @return array|null строка токена, дополненная полями пользователя
     */
    public static function findValid(\mysqli $conn, string $token)
    {
        if ($token === '') {
            return null;
        }

        if (!self::ensureTable($conn)) {
            return null;
        }

        $hash = hash('sha256', $token);
        $stmt = $conn->prepare(
            "SELECT t.id, t.user_id, t.name, t.created_at, t.revoked_at,
                    t.usage_window_start, t.usage_count,
                    u.username, u.firstname, u.lastname, u.middlename, u.is_admin
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.revoked_at IS NULL
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Отмечает факт использования токена.
     */
    public static function touch(\mysqli $conn, int $tokenId): void
    {
        $stmt = $conn->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $tokenId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Расходует одну единицу часового лимита загрузок.
     *
     * Счётчик живёт в самой строке токена: сессии для API нет, поэтому
     * общий сессионный rate limit здесь неприменим.
     *
     * @return bool false, если лимит исчерпан
     */
    public static function consumeRateLimit(\mysqli $conn, int $tokenId, int $limit = self::RATE_LIMIT_PER_HOUR): bool
    {
        // Открываем новое окно, если предыдущее старше часа или его не было.
        $reset = $conn->prepare(
            "UPDATE api_tokens
             SET usage_window_start = NOW(), usage_count = 0
             WHERE id = ?
               AND (usage_window_start IS NULL OR usage_window_start < (NOW() - INTERVAL 1 HOUR))"
        );
        if ($reset) {
            $reset->bind_param('i', $tokenId);
            $reset->execute();
            $reset->close();
        }

        $stmt = $conn->prepare(
            "UPDATE api_tokens SET usage_count = usage_count + 1
             WHERE id = ? AND usage_count < ?"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $tokenId, $limit);
        $stmt->execute();
        $updated = $stmt->affected_rows > 0;
        $stmt->close();

        return $updated;
    }

    /**
     * Список действующих токенов пользователя (без секретов).
     */
    public static function listForUser(\mysqli $conn, int $userId): array
    {
        if (!self::ensureTable($conn)) {
            return [];
        }

        $stmt = $conn->prepare(
            "SELECT id, token_prefix, name, created_at, last_used_at
             FROM api_tokens
             WHERE user_id = ? AND revoked_at IS NULL
             ORDER BY created_at DESC
             LIMIT 50"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * Отзывает токен пользователя. Чужой токен отозвать нельзя.
     */
    public static function revoke(\mysqli $conn, int $userId, int $tokenId): bool
    {
        $stmt = $conn->prepare(
            "UPDATE api_tokens SET revoked_at = NOW()
             WHERE id = ? AND user_id = ? AND revoked_at IS NULL"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $tokenId, $userId);
        $stmt->execute();
        $revoked = $stmt->affected_rows > 0;
        $stmt->close();

        return $revoked;
    }

    /**
     * Достаёт токен из запроса.
     *
     * Проверяются три источника: стандартный заголовок Authorization,
     * запасной X-Api-Token (часть конфигураций Apache вырезает Authorization)
     * и поле формы.
     */
    public static function extractToken(): string
    {
        $header = '';
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, 'Authorization') === 0) {
                    $header = $value;
                    break;
                }
            }
        }

        if ($header !== '' && stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }

        if (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
            return trim($_SERVER['HTTP_X_API_TOKEN']);
        }

        if (!empty($_POST['api_token'])) {
            return trim($_POST['api_token']);
        }

        return '';
    }

    /**
     * Отображаемое имя пользователя по строке токена.
     */
    public static function describeUser(array $tokenRow): string
    {
        $full = trim(
            ($tokenRow['lastname'] ?? '') . ' ' .
            ($tokenRow['firstname'] ?? '') . ' ' .
            ($tokenRow['middlename'] ?? '')
        );

        return $full !== '' ? $full : (string)($tokenRow['username'] ?? '');
    }
}
