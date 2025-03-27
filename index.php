<?php
include("db.php");
session_start();

$servername = "localhost";// Połączenie z bazą danych
$username = "root"; // Zmień na swoje dane
$password = ""; // Zmień na swoje dane
$dbname = "baza2"; // Zmień na swoją nazwę bazy danych

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Dodawanie postu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_post') {
    $username = $_SESSION['sesyjnymail'];
    $content = $_POST['content'];
    $timestamp = date("Y-m-d H:i:s");
    $file_path = null;
    $file_type = null;

    // Obsługa przesyłania pliku
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = $_FILES['attachment']['name'];
        $file_type = $_FILES['attachment']['type'];

        // Zdefiniowanie ścieżki zapisu pliku
        $upload_dir = "uploads/"; // Katalog do zapisu
        $file_path = $upload_dir . basename($file_name);

        // Przeniesienie pliku do katalogu
        if (move_uploaded_file($file_tmp, $file_path)) {
            echo "Plik został przesłany pomyślnie.";
        } else {
            echo "Wystąpił błąd przy przesyłaniu pliku.";
        }
    }

    if (!empty($username) && !empty($content)) {
        $sql = "INSERT INTO posts (username, content, timestamp, file_path, file_type) 
                VALUES ('$username', '$content', '$timestamp', '$file_path', '$file_type')";
        if ($conn->query($sql) === TRUE) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Błąd: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "Błąd: Wypełnij wszystkie pola.";
    }
}

// Usuwanie postu
if (isset($_GET['delete_post_id'])) {
    $post_id = $_GET['delete_post_id'];

    $sql = "DELETE FROM posts WHERE id=$post_id";
    if ($conn->query($sql) === TRUE) {
    } else {
        echo "Błąd: " . $sql . "<br>" . $conn->error;
    }
}

// Edytowanie postu
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_post') {
    $post_id = $_POST['post_id'];
    $new_content = $_POST['content'];

    $sql = "UPDATE posts SET content='$new_content' WHERE id=$post_id";
    if ($conn->query($sql) === TRUE) {
    } else {
        echo "Błąd: " . $sql . "<br>" . $conn->error;
    }
}

// Obsługa wyszukiwania
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $sql = "SELECT id, username, content, timestamp, file_path, file_type FROM posts 
            WHERE content LIKE '%$search_query%' OR username LIKE '%$search_query%' 
            ORDER BY timestamp DESC";
} else {
    $sql = "SELECT id, username, content, timestamp, file_path, file_type FROM posts ORDER BY timestamp DESC";
}

$result = $conn->query($sql);

$posts = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $post = array(
            "id" => $row["id"],
            "username" => $row["username"],
            "content" => $row["content"],
            "timestamp" => $row["timestamp"],
            "file_path" => $row["file_path"],
            "file_type" => $row["file_type"]
        );
        array_push($posts, $post);
    }
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Przygotowanie zapytania SQL (ochrona przed SQL Injection)
    $stmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id); // "i" oznacza integer (liczba całkowita)
    $stmt->execute();
    $stmt->bind_result($localusermail, $localuserphone);
    $stmt->fetch();
    $stmt->close();
}
$_SESSION['sesyjnymail'] = $localusermail;
$conn->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna - Posty</title>
    <link rel="stylesheet" href="index.css">
    <style>
  
    </style>
</head>
<link href="register.php">
<link href="login.php">
<body>
<header>
    <a class="logo" href="index.php"></a>
</header>

<div class="layout">
    <div class="sidebar">
        <a href="javascript:void(0);" onclick="loadProfile()">Profil</a>
        <a href="javascript:void(0);" onclick="loadSettings()">Ustawienia</a>
        <p><a id="logout" href="logout.php">Wyloguj</a></p>
    </div>
    
    <div class="main-content">
        <h1>Posty użytkowników</h1>

        <!-- Form for search
        <div class="search-container">
            <form method="GET" action="index.php">
            <input type="text" name="search" placeholder="Szukaj postów..." value="" class="styled-input">
                <button type="submit" class="search-btn">Szukaj</button>
            </form>
        </div>*/-->
        <div class="search-container">
    <form method="GET" action="index.php">
        <input type="text" name="search" id="search-input" placeholder="Szukaj postów..." value="" class="styled-input">
        
    </form>
    <div id="search-results"></div> <!-- Miejsce na wyniki wyszukiwania -->
</div>

<script>
    // Nasłuchujemy na wprowadzanie tekstu
    document.getElementById("search-input").addEventListener("input", function() {
        var query = this.value;

        // Jeśli użytkownik wpisuje coś (minimum 3 znaki, np.)
        if (query.length >= 1) {
            fetchResults(query);
        } else {
            document.getElementById("search-results").innerHTML = ''; // Jeśli puste, czyścimy wyniki
        }
    });

    // Funkcja do wykonania zapytania AJAX
    function fetchResults(query) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "search.php?search=" + encodeURIComponent(query), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var results = JSON.parse(xhr.responseText);
                displayResults(results);
            }
        };
        xhr.send();
    }

    // Funkcja do wyświetlania wyników
    function displayResults(results) {
        var resultsDiv = document.getElementById("search-results");
        resultsDiv.innerHTML = ''; // Czyścimy poprzednie wyniki

        if (results.length > 0) {
            results.forEach(function(post) {
                var postDiv = document.createElement("div");
                postDiv.classList.add("post");
                postDiv.innerHTML = `<strong>${post.username}</strong><p>${post.content}</p>`;
                resultsDiv.appendChild(postDiv);
            });
        } else {
            resultsDiv.innerHTML = "Brak wyników";
        }
    }
</script>


        <button class="add-post-btn" onclick="openModal()">Dodaj Post</button>

<div id="postsContainer">
    <?php if (count($posts) > 0): ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <span class="timestamp"><?php echo $post['timestamp']; ?></span>
                
                <?php if ($post['file_path']): ?>
                    <?php
                        // Sprawdzamy rozszerzenie pliku
                        $file_extension = pathinfo($post['file_path'], PATHINFO_EXTENSION);
                        if (in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                            // Jeśli plik jest obrazem
                            echo '<p><img src="' . htmlspecialchars($post['file_path']) . '" alt="Załączony obraz" style="max-width: 100%; height: auto;"></p>';
                        } else {
                            // Jeśli plik nie jest obrazem, pokazujemy link do pobrania
                            echo '<p><a href="' . htmlspecialchars($post['file_path']) . '" target="_blank">Pobierz załącznik</a></p>';
                        }
                    ?>
                <?php endif; ?>
                
                <div class="options" onclick="toggleOptionsMenu(<?php echo $post['id']; ?>)">
                    &#x22EE;
                    <div id="options-menu-<?php echo $post['id']; ?>" class="options-menu">
                        <button onclick="editPost(<?php echo $post['id']; ?>)">Edytuj</button>
                        <button onclick="deletePost(<?php echo $post['id']; ?>)">Usuń</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Brak postów do wyświetlenia.</p>
    <?php endif; ?>
</div>
    </div>

    <div class="sidebar right-sidebar">
    <a href="javascript:void(0);" onclick="loadRules()">Zasady</a>
     <a href="javascript:void(0);" onclick="loadContact()">Kontakt</a>
    <a href="javascript:void(0);" onclick="loadHelp()">Pomoc</a>
    <p><a href="https://github.com/dawidnolove/bucket" target="blank">Github <img id="icon-github" src="https://upload.wikimedia.org/wikipedia/commons/9/91/Octicons-mark-github.svg"></a></p>
</div>
</div>

<!-- Okno modalne do dodawania postów -->
<div id="postModal" class="modal">
    <h3>Dodaj nowy post</h3>
    <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_post">
        <label for="content">Treść posta:</label><br>
        <textarea id="content" name="content" rows="4" cols="50" required></textarea><br><br>

        <label for="attachment">Załącz plik:</label><br>
        <input type="file" id="attachment" name="attachment"><br><br>

        <button type="submit">Wyślij</button>
        <button type="button" onclick="closeModal()">Anuluj</button>
    </form>
</div>

<footer>
    <p>© 2024 LoreNest. Wszelkie prawa zastrzeżone. All Rights Reserved</p>
</footer>

<script>
    //                    <p><label>Hasło:</label> <span id="password">********</span> 
    //                    <span id="wyświetlhasło" class="toggle-password">Pokaż</span></p> 
    // Funkcja wyświetlania i ukrywania hasła w poniższej funkcji
 function loadProfile() {
        document.querySelector('.main-content').innerHTML = `
            <div class="main-content">
                <div class="user-info">
                    <h2>Dane użytkownika</h2>
                    <p><label>Email:</label> <span><?php echo $localusermail;?></span></p>
                    <p><label>Numer telefonu:</label> <span><?php echo $localuserphone;?></span></p>

                </div>
            </div>
        `;
        document.getElementById("wyświetlhasło").addEventListener("click", function() {
            var passwordSpan = document.getElementById("password");
            var togglePasswordText = document.getElementById("wyświetlhasło");

            if (passwordSpan.textContent === "********") {
                passwordSpan.textContent = "hasło12345";
                togglePasswordText.textContent = "Ukryj";
            } else {
                passwordSpan.textContent = "********";
                togglePasswordText.textContent = "Pokaż";
            }
        });
    }
function loadSettings() {
    document.querySelector('.main-content').innerHTML = ` 
        <div class="main-content">
            <div class="settings-section">
                <h2>Ustawienia konta</h2>
                <label for="email">Adres email:</label>
                <input type="email" id="email" placeholder="Wpisz email">
                <label for="password">Nowe hasło:</label>
                <input type="password" id="password" placeholder="Wpisz nowe hasło">
                <button>Zapisz zmiany</button>
            </div>`;
}

function loadHelp() {
    document.querySelector('.main-content').innerHTML = `
        <h1>Pomoc</h1>
        <h2>Wprowadzenie do platformy</h2>
        <p>Nasza platforma pozwala uczniom pomagać sobie nawzajem w nauce. Możesz zadawać pytania, oferować pomoc i kontaktować się z innymi użytkownikami.</p>
        
        <h2>Tworzenie i zarządzanie kontem</h2>
        <ul>
            <li><strong>Jak się zarejestrować?</strong> Kliknij „Zarejestruj się” </li>
            <li><strong>Jak edytować profil?</strong> Przejdź do ustawień i dokonaj zmian.</li>
            <li><strong>Jak usunąć konto?</strong> Skontaktuj się z administracją.</li>
        </ul>
        
        <h2>Dodawanie i znajdowanie pomocy</h2>
        <ul>
            <li><strong>Jak dodać prośbę o pomoc?</strong> Opublikuj swoje zapytanie.</li>
            <li><strong>Jak odpowiedzieć na prośbę?</strong> Kliknij wiadomości i napisz wiadomość</li>
        </ul>
        
        <h2>Zasady bezpieczeństwa</h2>
        <p>Nie udostępniaj swoich danych osobowych innym użytkownikom i zgłaszaj podejrzane zachowania.</p>
        
        <h2>Rozwiązywanie problemów technicznych</h2>
        <p>Jeśli napotkasz problem, skontaktuj się z działem wsparcia przez formularz zgłoszeniowy.</p>
        
        <h2>Najczęściej zadawane pytania (FAQ)</h2>
        <ul>
            <li><strong>Nie mogę się zalogować – co zrobić?</strong> Sprawdź swoje dane logowania lub zresetuj hasło.</li>
            <li><strong>Aplikacja nie działa prawidłowo?</strong> Spróbuj odświeżyć stronę lub skorzystać z innej przeglądarki.</li>
        </ul>
    `;
}
// Skrypt do obsługi postów, edytowania i usuwania
function loadRules() {
    document.querySelector('.main-content').innerHTML = `
        <div class="main-content3">
            <h1>Regulamin</h1>
            <p>Proszę zapoznać się z poniższymi punktami:</p>
            <ul>
                <li>Każdy użytkownik musi przestrzegać zasad społeczności.</li>
                <li>Publikowanie treści wulgarnych lub obraźliwych jest zabronione.</li>
                <li>Użytkownicy są odpowiedzialni za swoje działania na platformie.</li>
                <li>Zakazuje się udostępniania nielegalnych treści lub plików.</li>
                <li>Każdy użytkownik może usunąć swoje konto w dowolnym momencie.</li>
            </ul>
        </div>
    `;
}
function loadContact() {
    document.querySelector('.main-content').innerHTML = `
    <div class="main-content">
        <h1>Skontaktuj się z nami</h1>
        <div class="contact-info">
            <h2>Dane kontaktowe:</h2>
            <p><strong>Email:</strong> <a href="mailto:przykładowycontact@lorenest.pl">przykładowycontact@lorenest.pl</a></p>
            <p><strong>Telefon:</strong> +48 123 456 789</p>
        </div>   
    </div>`;
}
function openModal() {
    document.getElementById("postModal").style.display = "block";
}

function closeModal() {
    document.getElementById("postModal").style.display = "none";
}

function editPost(postId) {
    let newContent = prompt("Edytuj post:");
    if (newContent) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';
        
        let inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'edit_post';
        form.appendChild(inputAction);
        
        let inputPostId = document.createElement('input');
        inputPostId.type = 'hidden';
        inputPostId.name = 'post_id';
        inputPostId.value = postId;
        form.appendChild(inputPostId);
        
        let inputContent = document.createElement('textarea');
        inputContent.name = 'content';
        inputContent.value = newContent;
        form.appendChild(inputContent);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function deletePost(postId) {
    if (confirm("Na pewno chcesz usunąć ten post?")) {
        window.location.href = 'index.php?delete_post_id=' + postId;
    }
}

function toggleOptionsMenu(postId) {
    var menu = document.getElementById('options-menu-' + postId);
    if (menu.style.display === "block") {
        menu.style.display = "none";
    } else {
        menu.style.display = "block";
    }
}
</script>

</body>
</html>
