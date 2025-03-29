<?php
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$response = ["status" => 0, "message" => ""];

if (isset($data['action'])) {
    if ($data['action'] == "register") {
        $email = $data['email'];
        $password = $data['password'];
        $phone = $data['phone'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response["message"] = "Invalid email address.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $response["message"] = "This email is already registered!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, phone) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hashedPassword, $phone]);
                $response["status"] = 1;
                $response["message"] = "Registration successful!";
            }
        }
    }

    if ($data['action'] == "login") {
        $email = $data['email'];
        $password = $data['password'];
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $response["status"] = 1;
            $response["message"] = "Login successful!";
            session_start();
            $_SESSION['user_id'] = $user['id'];
        } else {
            $response["message"] = "Incorrect email or password.";
        }
    }
}

echo json_encode($response);
?>