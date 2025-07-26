<?php
namespace App\Controller;

use App\Controller\BaseController;

class AuthController extends BaseController
{
    /**
     * Show login form or process login (POST)
     */
    public function login(): void
    {
        session_start();
        require __DIR__ . '/../../config.php';
        require __DIR__ . '/../../db_connection.php';
        $mysqli = $conn;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Безопасная валидация и очистка входных данных
            $username = $this->sanitizeString($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $this->logSecurityEvent('auth_login_empty_credentials', [
                    'username' => $username,
                    'ip' => $this->getClientIP()
                ]);
                header("Location: index.php?error=login");
                exit();
            }
            
            // Rate limiting для защиты от брутфорса (5 попыток за 5 минут)
            if (!$this->checkRateLimit($this->getClientIP(), 'login', 5, 300)) {
                $this->logSecurityEvent('auth_login_rate_limit_exceeded', [
                    'username' => $username,
                    'ip' => $this->getClientIP()
                ]);
                header("Location: index.php?error=rate_limit");
                exit();
            }

            try {
                $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user) {
                    $dbPassword = $user['password'];

                    // Проверка пароля с bcrypt хешированием
                    if (strpos($dbPassword, '$2y$') === 0 && password_verify($password, $dbPassword)) {
                        // Успешная авторизация с bcrypt
                        $_SESSION['user'] = [
                            'id'        => (int)$user['id'],
                            'lastname'  => $user['lastname'],
                            'firstname' => $user['firstname'],
                            'middlename'=> $user['middlename'],
                            'is_admin'  => (bool)$user['is_admin'],
                        ];
                        
                        $this->logSecurityEvent('auth_login_success', [
                            'user_id' => $user['id'],
                            'username' => $username,
                            'is_admin' => $user['is_admin'],
                            'ip' => $this->getClientIP()
                        ]);
                        
                        header("Location: index.php");
                        exit();
                    } 
                    // Поддержка старых MD5 паролей с автоматическим обновлением
                    elseif (md5($password) === $dbPassword) {
                        // Обновляем MD5 хеш на bcrypt
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->bind_param("si", $newHash, $user['id']);
                        $updateStmt->execute();

                        $_SESSION['user'] = [
                            'id'        => (int)$user['id'],
                            'lastname'  => $user['lastname'],
                            'firstname' => $user['firstname'],
                            'middlename'=> $user['middlename'],
                            'is_admin'  => (bool)$user['is_admin'],
                        ];
                        
                        $this->logSecurityEvent('auth_login_success_md5_upgraded', [
                            'user_id' => $user['id'],
                            'username' => $username,
                            'is_admin' => $user['is_admin'],
                            'ip' => $this->getClientIP()
                        ]);
                        
                        header("Location: index.php");
                        exit();
                    } else {
                        // Неверный пароль
                        $this->logSecurityEvent('auth_login_failed_password', [
                            'username' => $username,
                            'user_exists' => true,
                            'ip' => $this->getClientIP()
                        ]);
                        header("Location: index.php?error=login");
                        exit();
                    }
                } else {
                    // Пользователь не найден
                    $this->logSecurityEvent('auth_login_failed_user_not_found', [
                        'username' => $username,
                        'ip' => $this->getClientIP()
                    ]);
                    header("Location: index.php?error=login");
                    exit();
                }
            } catch (\Exception $e) {
                // Ошибка базы данных
                $this->logSecurityEvent('auth_login_database_error', [
                    'username' => $username,
                    'error' => $e->getMessage(),
                    'ip' => $this->getClientIP()
                ]);
                header("Location: index.php?error=login");
                exit();
            }
        }

        header("Location: index.php");
        exit();
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        session_start();
        
        // Логируем выход пользователя
        if (isset($_SESSION['user']['id'])) {
            $this->logSecurityEvent('auth_logout', [
                'user_id' => $_SESSION['user']['id'],
                'username' => $_SESSION['user']['lastname'] ?? 'unknown',
                'ip' => $this->getClientIP()
            ]);
        }
        
        session_destroy();
        header("Location: index.php");
        exit();
    }
} 