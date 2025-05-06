<?php
session_start();
if (!isset($_SESSION['email'])) exit;

$current = $_SESSION['email'];
$mysqli = new mysqli("localhost", "root", "", "baza2");
if ($mysqli->connect_error) exit;

$sql = "SELECT email FROM users WHERE email != '$current'";
$res = $mysqli->query($sql);

$users = [];
while ($row = $res->fetch_assoc()) {
    $users[] = $row['email'];
}
echo json_encode($users);
?>