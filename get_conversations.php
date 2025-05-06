<?php
session_start();
if (!isset($_SESSION['email'])) exit;

$mysqli = new mysqli("localhost", "root", "", "baza2");
if ($mysqli->connect_error) exit;

$current = $mysqli->real_escape_string($_SESSION['email']);

$sql = "SELECT other_user, MAX(created_at) AS last_msg
        FROM (
            SELECT 
                IF(sender_email = '$current', receiver_email, sender_email) AS other_user, created_at
            FROM messages
            WHERE sender_email = '$current' OR receiver_email = '$current' ) AS conv
        GROUP BY other_user
        ORDER BY last_msg DESC;
        ";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    $email = htmlspecialchars($row['other_user']);
    echo "<div onclick=\"openConversation('$email')\" style='cursor:pointer; padding:5px; border-bottom:1px solid #ccc;'>$email</div>";
}
?>