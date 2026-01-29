<?php
require_once 'Database/Database.php';

//Handles login and logout logic
class LoginController {
    public $message = '';

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Cleans the input by user
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            if (empty($username) || empty($password)) {
                $this->message = 'Please enter both username and password.';
                return;
            }

            $db = Database::getInstance();

            //Allows login by username OR email
            $query = "SELECT * FROM users WHERE username = :ue OR email = :ue LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':ue', $username, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            //Verify hashed password
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                session_regenerate_id(true); // prevents session fixation
                header('Location: index.php?page=pets');
                exit;
            } else {
                $this->message = '❌ Invalid username/email or password.';
            }
        }
    }

    //Logout (destroys session and redirects to home)
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
?>