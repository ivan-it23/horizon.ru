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
            $username = $mysqli->real_escape_string($_POST['username']);
            $password = $_POST['password'];

            $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                $dbPassword = $user['password'];

                if (strpos($dbPassword, '$2y$') === 0 && password_verify($password, $dbPassword)) {
                    $_SESSION['user'] = [
                        'id'        => $user['id'],
                        'lastname'  => $user['lastname'],
                        'firstname' => $user['firstname'],
                        'middlename'=> $user['middlename'],
                        'is_admin'  => $user['is_admin'],
                    ];
                    header("Location: index.php");
                    exit();
                } elseif (md5($password) === $dbPassword) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $newHash, $user['id']);
                    $updateStmt->execute();

                    $_SESSION['user'] = [
                        'id'        => $user['id'],
                        'lastname'  => $user['lastname'],
                        'firstname' => $user['firstname'],
                        'middlename'=> $user['middlename'],
                        'is_admin'  => $user['is_admin'],
                    ];
                    header("Location: index.php");
                    exit();
                } else {
                    header("Location: index.php?error=login");
                    exit();
                }
            } else {
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
        session_destroy();
        header("Location: index.php");
        exit();
    }


} 