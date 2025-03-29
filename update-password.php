<?php
session_start();
include("db.php");

$new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
$email = $_POST['email'];

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
if ($stmt->execute([$new_password, $email])) {
    echo "success";
} else {
    echo "error";
}
?>