<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <script src="https://accounts.google.com/gsi/client" async></script> <!--async defer-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bcryptjs/2.4.3/bcrypt.min.js"></script>
    <meta charset="utf-8" />
    <link rel="icon" href="favicon.ico" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#000000" />
    <meta name="description" content="Web site created by LoreNest team using React but it was quite hard, so we passed">
    <link rel="stylesheet" href="login-form.css">
    <title>Nest for Lore</title>
    <link rel="icon" href="logo720.png" type="image/x-icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>
    

    <img src="logo720.png" width="150px" height="150px"/>
    <noscript>You need to enable JavaScript to run this app.</noscript>
    <div id="root"></div>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="success-message" style="
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
        background: rgba(0, 201, 183, 0.4);
        border-left: 4px solid #00c9b7;
        color: white;
        z-index: 99999;
        max-width: 100%;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        animation: fadeIn 0.3s ease-out;
    "><?= htmlspecialchars($_SESSION['message']) ?></div>
    <?php unset($_SESSION['message']); ?>
<?php endif; ?>

    <div class="flip-card">
        <!-- Reszta Twojego oryginalnego kodu pozostaje BEZ ZMIAN -->
        <div class="login-container">
            <h2>Logowanie</h2>
            <form id="login-form" method="POST" action="login.php" onsubmit="handleLogin(event)">
                <input type="email" id="login-mail" name="mail" placeholder="E-MAIL" required />
                <input type="password" id="login-password" name="password" placeholder="HASŁO" required />
                <input type="submit" value="Zaloguj się" />
                <div id="login-error-message" style="color: red; display: none;"></div>
            </form>
            <div class="footer">
                <p onclick="handleFlip()">Nie masz konta? <a href="#">Zarejestruj się</a></p>
            </div>
        </div>
        
        <div class="signup-container">
            <h2>Rejestracja</h2>
            <form id="signup-form" method="POST" action="register.php">
                <input type="email" id="signup-mail" name="email" placeholder="E-MAIL" required />
                <input type="password" id="signup-password" name="password" placeholder="HASŁO" required />
                <input type="tel" id="phone-number" name="phone" placeholder="TELEFON" required />
                <input type="submit" value="Zarejestruj się" />
            </form>
            <div class="footer">
                <p onclick="handleFlip()">Masz już konto? <a href="#">Zaloguj się</a></p>
            </div>
        </div>
        
    </div>
    <canvas id="particles-bg"></canvas>

    <script src="login-form.js"></script>
    <script>
        const successMessage = document.querySelector('.success-message');
    if (successMessage) {
        setTimeout(function() {
            successMessage.style.opacity = '0';
            setTimeout(() => successMessage.remove(), 300);
        }, 5000);
    }
        
        // NEW: Animacja cząsteczek (identyczna jak w welcome.html)
        const canvas = document.getElementById('particles-bg');
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.z = 5;
        
        const particlesGeometry = new THREE.BufferGeometry();
        const particleCount = 1000;
        const posArray = new Float32Array(particleCount * 3);
        
        for(let i = 0; i < particleCount * 3; i++) {
            posArray[i] = (Math.random() - 0.5) * 5;
        }
        
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        
        const particlesMaterial = new THREE.PointsMaterial({
            size: 0.02,
            color: 0x00c9b7,
            transparent: true,
            opacity: 0.8
        });
        
        const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particlesMesh);
        
        function animate() {
            requestAnimationFrame(animate);
            particlesMesh.rotation.x += 0.0005;
            particlesMesh.rotation.y += 0.0005;
            renderer.render(scene, camera);
        }
        animate();

        // Reszta Twojego oryginalnego JS (handleFlip itd.)
        function handleFlip() {
            const flipCard = document.querySelector('.flip-card');
            flipCard.classList.toggle('flipped');
        }
    </script>
</body>
</html>
