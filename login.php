<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "amk_gorizont1");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $mysqli->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Подготовленный запрос для поиска пользователя
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $dbPassword = $user['password'];

        // Если пароль в формате bcrypt
        if (strpos($dbPassword, '$2y$') === 0) {
            if (password_verify($password, $dbPassword)) {
                // Успешная авторизация
                $_SESSION['user'] = [
                    'id'        => $user['id'],        // ← добавлено
                    'lastname'  => $user['lastname'],
                    'firstname' => $user['firstname'],
                    'middlename'=> $user['middlename'],
                    'is_admin'  => $user['is_admin']
                ];
                header("Location: index.php");
                exit();
            } else {
                echo "<script>alert('Неверный логин или пароль'); window.location.href='index.php';</script>";
                exit();
            }

        // Если пароль в виде MD5
        } elseif (md5($password) === $dbPassword) {
            // Обновляем на bcrypt
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHash, $user['id']);
            $updateStmt->execute();

            $_SESSION['user'] = [
                'id'        => $user['id'],        // ← добавлено
                'lastname'  => $user['lastname'],
                'firstname' => $user['firstname'],
                'middlename'=> $user['middlename'],
                'is_admin'  => $user['is_admin']
            ];
            header("Location: index.php");
            exit();

        } else {
            echo "<script>alert('Неверный логин или пароль'); window.location.href='index.php';</script>";
            exit();
        }

    } else {
        echo "<script>alert('Неверный логин или пароль'); window.location.href='index.php';</script>";
        exit();
    }
}
?>
