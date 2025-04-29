<?php
function isMobile() {
    return preg_match('/android|iphone|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
}

$currentFile = basename($_SERVER['PHP_SELF']);

if (isMobile() && $currentFile !== 'mobilny.php') {
    header('Location: mobilny.php');
    exit();
}

if (!isMobile() && $currentFile !== 'home.php') {
    header('Location: home.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mobilna blokada</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      background-color: black;
      font-family: 'Courier New', Courier, monospace;
      color: #00ff00;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    h1 {
      text-shadow: 0 0 5px #00ff00, 0 0 10px #00ff00, 0 0 20px #00ff00;
      font-size: 1.8rem;
      text-align: center;
      padding: 0 20px;
    }
  </style>
</head>
<body>
  <h1>Nie obsługujemy na razie<br>urządzeń mobilnych</h1>
</body>
</html>
