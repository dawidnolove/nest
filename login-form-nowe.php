<!DOCTYPE html>
<html lang="pl">


<head>

    <script src="https://accounts.google.com/gsi/client" async></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bcryptjs/2.4.3/bcrypt.min.js"></script>
    <meta charset="utf-8" />
    <link rel="icon" href="favicon.ico" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#000000" />
    <meta name="description" content="Web site created by LoreNest team using React but it was quite hard, so we passed">
    <link rel="apple-touch-icon" href="logo192.png" />
    <link rel="manifest" href="manifest.json" />
    <link rel="stylesheet" href="login-form.css">
    <title>Nest for Lore</title>
</head>
<body>
    <img src="logo720.png" width="150px" height="150px"/>
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <div id="root"></div>

    <div class="flip-card">
        <!-- Sekcja logowania -->
        <div class="login-container" id="login">
            <h2>Logowanie</h2>
            <form id="login-form" method="POST" action="login.php">
    <input type="email" id="login-mail" name="mail" placeholder="E-MAIL" required />
    <input type="password" id="login-password" pattern="^(?=.*[A-Z])(?=.*[0-9]).{8,}$" title="Hasło musi zawierać co najmniej 8 znaków, w tym jedną dużą literę i jedną cyfrę." name="password" autocomplete="current_password" placeholder="HASŁO" required />
    <input type="submit" value="Zaloguj się" />
    <div id="login-error-message" style="color: red; display: none;"></div>
</form>
            <div class="footer">
                <p onclick="handleFlip()" style="font-size: 12px">Nie masz konta? <a href="#" style="color: #aaa;">Zarejestruj się</a></p>
                <p><a href="policy.html" target="_blank" style="color: #aaa; font-size: 10px !important;">Polityka prywatności</a></p>
            </div>
        </div>

        <!-- Sekcja rejestracji -->
        <div class="signup-container" id="register">
            <h2>Rejestracja</h2>
           <form id="signup-form" method="POST" action="register.php">
    <input type="email" id="signup-mail" name="email" placeholder="E-MAIL" required />
    <input type="password" id="signup-password" pattern="^(?=.*[A-Z])(?=.*[0-9]).{8,}$" title="Hasło musi zawierać co najmniej 8 znaków, w tym jedną dużą literę i jedną cyfrę." name="password" autocomplete="current_password" placeholder="HASŁO" required />
    <input type="tel" id="phone-number" pattern="[0-9]{9}" title="Wprowadź poprawny numer telefonu" name="phone" placeholder="TELEFON" required />
    <input type="submit" value="Zarejestruj się" />
</form>
            <div class="footer">
                <p onclick="handleFlip()" style="font-size: 12px">Masz już konto? <a href="#" style="color: #aaa;">Zaloguj się</a></p>
            </div>
        </div>
    </div>

    <script>
        // Funkcja przełączająca formularze
        function handleFlip() {
            const flipCard = document.querySelector('.flip-card');
            flipCard.classList.toggle('flipped');
        }

        // Jeśli w URL jest parametr login=true, to przełączamy na formularz logowania
        if (window.location.search.indexOf("login=true") > -1) {
            handleFlip(); // Przełączamy na formularz logowania
        }

        function validatePhoneNumber() {
            const phoneInput = document.getElementById('phone-number');
            const phone = phoneInput.value;
            const phoneRegex = /^\d{9}$/;

            if (!phoneRegex.test(phone)) {
                alert("Nieprawidłowy numer telefonu. Musi zawierać 9 cyfr.");
                return false;
            }
            return true;
        }

        document.getElementById('signup-form').addEventListener('submit', function(event) {
            if (!validatePhoneNumber()) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
