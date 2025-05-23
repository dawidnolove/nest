<?php
function isMobile() {
    return preg_match('/android|iphone|blackberry|windows phone|opera mini|mobile/i', $_SERVER['HTTP_USER_AGENT']);
}

if (isMobile()) {
    header('Location: mobilny.php');
    exit();
}
?>
<?php
session_start();
include("db.php");
if (!isset($_SESSION['email'])) {
    $_SESSION['message'] = "Proszę się zalogować, aby uzyskać dostęp do strony.";
    header("Location: login-form.php");
    exit();
}
$banned_words_url = 'https://raw.githubusercontent.com/LDNOOBW/List-of-Dirty-Naughty-Obscene-and-Otherwise-Bad-Words/master/pl';
$banned_words_content = file_get_contents($banned_words_url);

if ($banned_words_content !== false) {
    $banned_words = explode("\n", $banned_words_content);
    
    $banned_words = array_filter($banned_words, function($word) {
        return !empty(trim($word));
    });
    
    $banned_words = array_values($banned_words);
} else {
    $banned_words = [];
}
echo '<script>';
echo 'const bannedWords = ' . json_encode($banned_words) . ';';
echo '</script>';
header('Content-Type: text/html; charset=utf-8');

if (isset($_SESSION['email'])) {
    $localuseremail = $_SESSION['email'];
    
    $stmt = $pdo->prepare("SELECT phone FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $localuseremail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $stmt = $pdo->prepare("SELECT phone FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $localuseremail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($user) {
        $_SESSION['phone'] = $user['phone'];
        $localuserphone = $_SESSION['phone']; 
    } else {
        $_SESSION['message'] = "Nie znaleziono numeru telefonu dla tego użytkownika.";
        header("Location: login-form.php");
        exit();
    }
} else {
    echo "<script>console.log('Brak zalogowanego użytkownika.');</script>";
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_post') {
    try {
        $username = sanitizeInput($_POST['username']);
        $content = sanitizeInput($_POST['content']);
        $subject = isset($_POST['subject']) ? sanitizeInput($_POST['subject']) : null;
        $timestamp = date("Y-m-d H:i:s");
        $file_path = null;
        $file_type = null;
		   foreach ($banned_words as $word) {
            if (stripos($content, $word) !== false) {
                throw new Exception("Treść zawiera zabronione słowo: '$word'.");
            }
        }
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_name = sanitizeInput($_FILES['attachment']['name']);
            $file_size = $_FILES['attachment']['size'];
            $file_type = $_FILES['attachment']['type'];

            if (!is_uploaded_file($_FILES['attachment']['tmp_name'])) {
                throw new Exception("Nieprawidłowe przesyłanie pliku");
            }

            $allowed_types = ['image/jpeg', 
                              'image/png', 
                              'image/gif', 
                              'image/webp', 
                              'application/msword', 
                              'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                              'application/pdf', 
                              'application/vnd.ms-excel', 
                              'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                              'text/plain',
                              'application/octet-stream'
                        ];

            $max_size = 5 * 1024 * 1024;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'txt', 'doc', 'docx', 'pdf', 'xls', 'xlsx', 'txt', 'webp'];
            $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Nieprawidłowe rozszerzenie pliku");
            }

            if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Nieprawidłowy typ pliku. Dozwolone formaty graficzne i tekstowe");
            }

            if ($file_size > $max_size) {
                throw new Exception("Plik jest zbyt duży (max 5MB)");
            }

            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_file_name = time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $new_file_name;

            if (!move_uploaded_file($file_tmp, $file_path)) {
                throw new Exception("Wystąpił błąd przy przesyłaniu pliku");
            }
        }
        if (empty($username) || empty($content) || empty($subject)) {
            throw new Exception("Wypełnij wszystkie wymagane pola, w tym wybierz przedmiot.");
        }
        $stmt = $pdo->prepare("INSERT INTO posts (username, content, timestamp, file_path, file_type, subject) 
                              VALUES (:username, :content, :timestamp, :file_path, :file_type, :subject)");
        $stmt->execute([
            ':username' => $username,
            ':content' => $content,
            ':timestamp' => $timestamp,
            ':file_path' => $file_path,
            ':file_type' => $file_type,
            ':subject' => $subject
        ]);

        $_SESSION['message'] = "Post został dodany pomyślnie";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_post') {
    try {
        $post_id = (int)$_POST['post_id'];
        $new_content = sanitizeInput($_POST['content']);
        $stmt = $pdo->prepare("SELECT username FROM posts WHERE id = :id");
        $stmt->execute([':id' => $post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            throw new Exception("Post nie istnieje.");
        }
        if ($_SESSION['email'] !== $post['username'] && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
            throw new Exception("Brak uprawnień do edycji tego posta.");
        }

        $stmt = $pdo->prepare("UPDATE posts SET content = :content WHERE id = :id");
        $stmt->execute([
            ':content' => $new_content,
            ':id' => $post_id
        ]);

        $_SESSION['message'] = "Post został zaktualizowany";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_post') {
    try {
        $post_id = (int)$_POST['post_id'];

        $stmt = $pdo->prepare("SELECT username FROM posts WHERE id = :id");
        $stmt->execute([':id' => $post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            throw new Exception("Post nie istnieje.");
        }
        if ($_SESSION['email'] !== $post['username'] && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
            throw new Exception("Brak uprawnień do usunięcia tego posta.");
        }

        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute([':id' => $post_id]);

        $_SESSION['message'] = "Post został usunięty";
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
$search_query = '';
$posts = [];

try {
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = sanitizeInput($_GET['search']);

        $stmt = $pdo->prepare("SELECT id, username, content, timestamp, file_path, file_type, subject FROM posts 
                              WHERE content LIKE :search_content 
                              OR username LIKE :search_username 
                              OR subject LIKE :search_subject
                              ORDER BY timestamp DESC");
        $stmt->execute([
            ':search_content' => "%$search_query%",
            ':search_username' => "%$search_query%",
            ':search_subject' => "%$search_query%"
        ]);
    } else {
        $stmt = $pdo->query("SELECT id, username, content, timestamp, file_path, file_type, subject FROM posts ORDER BY timestamp DESC");
    }

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Główna - Posty</title>
	<link rel="icon" href="logo720.webp" type="image/x-icon">
    <link rel="stylesheet" href="home.css?v=<?= time() ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.1/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php if (isset($message)): ?>
    <div class="message" id="success-message"><?= $message ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="error"><?= $error ?></div>
<?php endif; ?>

<div id="editPostModal" class="modal">
    <h3>Edytuj post</h3>
    <form action="home.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_post">
        <input type="hidden" id="edit_post_id" name="post_id">
        <label for="edit_content">Treść posta:</label><br>
        <textarea id="edit_content" name="content" rows="4" cols="50" required></textarea><br><br>
        <label for="edit_attachment" id="edit-attach-label" style="cursor: pointer;">Załącz plik: (klik)</label>
        <input type="file" id="edit_attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.doc,.docx,.pdf,.xls,.xlsx,.txt,.webp" style="display: none;">
        <div id="edit-file-info" class="file-info" style="display: none;">
            <span id="edit-file-name" class="file-name"></span>
            <span id="edit-remove-file" class="remove-file" title="Usuń plik">&times;</span>
        </div>
        <button type="submit" class="add-post-btn">Aktualizuj</button>
        <button type="button" class="add-post-btn" onclick="closeEditModal()">Anuluj</button>
    </form>
</div>
<div id="postModal" class="modal">
    <h3>Dodaj nowy post</h3>
    <form action="home.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_post">
        <input type="hidden" name="username" value="<?= isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '' ?>">
        
        <label for="content">Treść posta:</label><br>
        <textarea id="content" name="content" rows="4" cols="50" required></textarea><br><br>

        <label for="attachment" id="attach-label" style="cursor: pointer;">Załącz plik: (klik)</label>
        <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.doc,.docx,.pdf,.xls,.xlsx,.txt,.webp" style="display: none;">
        <div id="file-info" class="file-info" style="display: none;">
            <span id="file-name" class="file-name"></span>
            <span id="remove-file" class="remove-file" title="Usuń plik">&times;</span>
        </div>
        <label for="subject">Przedmiot:</label>
<input type="text" id="subject" name="subject" autocomplete="off" placeholder="Wpisz przedmiot którego dotyczy post" required>
<div id="suggestions" style="display: none; border: 1px solid #ccc; max-height: 150px; overflow-y: auto;"></div>

        <button type="submit" class="add-post-btn">Wyślij</button>
        <button type="button" class="add-post-btn" onclick="closeModal()">Anuluj</button>
    </form>
</div>
<div id="modal-new-msg" style="display:none;">
    <div id="modal-body" style="background:#000; border: 1px var(--light) solid; border-radius: 10px; padding:20px; width:400px;">
    </div>
</div>


<header>
    <div class="logo" onclick="loadPosts()"></div>
    <div class="dropdown-response" onclick="toggleDropdown()" hidden>
        <a href="javascript:void(0);" id="response-menu">☰ Menu</a>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="javascript:void(0);" onclick="loadProfile()">Profil</a>
            <a href="javascript:void(0);" onclick="loadSettings()">Ustawienia</a>
            <a href="javascript:void(0);" onclick="loadRules()">Zasady</a>
            <a href="javascript:void(0);" onclick="loadFAQ()">FAQ</a>
            <a href="https://github.com/dawidnolove/nest" target="blank">Github 
                <img id="icon-github" src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Github_logo_svg.svg/640px-Github_logo_svg.svg.png" width="16px">
            </a>
            <p><a id="logout" href="logout.php">Wyloguj</a></p>
            <h1>&reg;&copy;</h1>
            <a href="javascript:void(0);" onclick="loadChat()" id="chatButton">BETA ML CHAT</a>
        </div>
    </div>
</header>

<div class="layout">
    <div class="sidebar left-sidebar">
        <a href="javascript:void(0);" onclick="loadProfile()">
            <img src="https://img.icons8.com/ios-glyphs/24/user--v1.png" alt="Profil" style="vertical-align: middle;"> PROFIL
        </a>
        <a href="javascript:void(0);" onclick="loadSettings()">
            <img src="https://img.icons8.com/ios-glyphs/24/settings.png" alt="Ustawienia" style="vertical-align: middle;"> USTAWIENIA
        </a>
        <a href="javascript:void(0);" onclick="fetchConversations()">
            <img src="https://img.icons8.com/ios-glyphs/24/000000/speech-bubble.png" alt="Czat" style="vertical-align: middle;">WIADOMOŚCI
        </a>
        <a href="javascript:void(0);" onclick="loadRules()">
            <img src="https://img.icons8.com/ios-glyphs/24/rules.png" alt="Zasady" style="vertical-align: middle;"> ZASADY
        </a>
        <a href="javascript:void(0);" onclick="loadFAQ()">
            <img src="https://img.icons8.com/ios-glyphs/24/faq.png" alt="FAQ" style="vertical-align: middle;"> FAQ
        </a>
        <p>
            <a href="https://github.com/dawidnolove/nest" target="_blank">
                <img src="https://img.icons8.com/ios-glyphs/24/github.png" alt="Github" style="vertical-align: middle;"> GITHUB
            </a>
        </p>
        <p>
            <a id="logout" href="logout.php">
                <img src="https://img.icons8.com/ios-glyphs/24/logout-rounded.png" alt="Wyloguj" style="vertical-align: middle;"> WYLOGUJ SIĘ
            </a>
        </p>
        <h1>&copy;</h1>
        <a href="javascript:void(0);" onclick="loadChat()" id="chatButton">
            <img src="https://img.icons8.com/ios-glyphs/24/chat.png" alt="Chat" style="vertical-align: middle;"> BETA ML CHAT
        </a>
    </div>
    
    
    <div class="main-content">
        <h1>Ściana postów</h1>
        <div class="search-wrapper">
            <img src="https://img.icons8.com/ios-filled/50/ffffff/search.png" alt="FAQ" style="vertical-align: middle;">
            <div class="search-container">
                <form method="GET" action="home.php">
                    <input type="text" name="search" id="search-input" placeholder="Szukaj postów..." value="<?= htmlspecialchars($search_query) ?>" class="styled-input">
                    <button type="button" class="clear-btn" onclick="clearSearch()">Pokaż wszystkie</button> 
                </form>
                <div id="search-results"></div>
                
            </div>
        </div>
        <button class="add-post-btn" onclick="openModal()">Dodaj Post</button>
        
<div id="postsContainer">
    <?php if (count($posts) > 0): ?>
        <?php foreach ($posts as $post): ?>
        <div class="post" id="post-<?= htmlspecialchars($post['id']) ?>">
            <h3>
                <a href="mailto:<?= htmlspecialchars($post['username']) ?>" class="username-link">
                    <?= htmlspecialchars($post['username']) ?>
                </a>
                <?php if (!empty($post['subject'])): ?>
                    <span class="user-subject"><?= htmlspecialchars($post['subject']) ?></span>
                <?php endif; ?>
            </h3>
            <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <?php if (!empty($post['file_path'])): ?>
                <?php if (strpos($post['file_type'], 'image/') === 0): ?>
                    <img src="<?= htmlspecialchars($post['file_path']) ?>" 
                         class="post-image" 
                         onclick="openFullScreen(this)"
                         alt="Załącznik">
                <?php else: ?>
                    <a href="<?= htmlspecialchars($post['file_path']) ?>" 
                       class="download-link"
                       download>
                        Pobierz załącznik
                    </a>
                <?php endif; ?>
            <?php endif; ?>
     <div class="post-footer">
    <span class="timestamp"><?= htmlspecialchars($post['timestamp']) ?></span>
    <div class="post-actions">
        <?php if (
            isset($_SESSION['email']) &&
            ($_SESSION['email'] === $post['username'] || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true))
        ): ?>
            <button onclick="editPost(<?= $post['id'] ?>)">Edytuj</button>
            <button onclick="confirmDelete(<?= $post['id'] ?>)">Usuń</button>
        <?php endif; ?>
    </div>
</div>
        </div> 
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-posts">Brak postów do wyświetlenia.</p>
    <?php endif; ?>
</div>
    </div>
    <div class="back-button" onclick="loadPosts()" hidden></div>
</div>





<script>
    document.addEventListener(
    'click',
    function handleClickOutsideBox(event) {
        const box = document.getElementById('postModal');
        if (
        box.style.display === 'block' && 
        !box.contains(event.target) &&
        !event.target.classList.contains('add-post-btn')
        ) {
        box.style.display = 'none';
        }
    }
    );


    document.getElementById('attachment').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif|\.doc|\.docx|\.pdf|\.xls|\.xlsx|\.txt|\.webp)$/i;
    
    if (!allowedExtensions.exec(file.name)) {
        alert('Dozwolone są tylko pliki: JPG, PNG, GIF, DOC, PDF, XLS, TXT, WEBP');
        e.target.value = '';
    }
});
const subjects = [
    "Język polski", "Język angielski", "Język niemiecki", "Język francuski", "Język hiszpański",
    "Język rosyjski", "Język włoski", "Matematyka", "Matematyka rozszerzona", "Fizyka", "Fizyka rozszerzona",
    "Chemia", "Chemia rozszerzona", "Biologia", "Biologia rozszerzona", "Geografia", "Geografia rozszerzona",
    "Historia", "Historia rozszerzona", "Wiedza o społeczeństwie", "Edukacja dla bezpieczeństwa", "Informatyka",
    "Religia", "Etika", "Wychowanie fizyczne", "Plastyka", "Muzyka", "Podstawy przedsiębiorczości", "Biznes i zarządzanie",
    "Filozofia", "Psychologia", "Logika", "Przyroda", "Technika", "Zajęcia z wychowawcą", "Zajęcia praktyczne",
    "Przedmioty zawodowe", "Podstawy prawa", "Zajęcia artystyczne", "Podstawy grafiki komputerowej", "Techniki multimedialne",
    "Przedsiębiorczość", "Edukacja regionalna", "Wiedza o kulturze", "Zajęcia z przedsiębiorczości", "Zajęcia z informatyki",
    "Ekonomia", "Zajęcia praktyczne zawodowe", "Elektronika", "Mechanika", "Automatyka", "Inżynieria materiałowa",
    "Technologia chemiczna", "Gastronomia", "Hotelarstwo", "Turystyka", "Logistyka", "Rolnictwo", "Transport",
    "Mechatronika", "Programowanie", "Administracja", "Opieka nad dziećmi", "Opieka zdrowotna", "Prace biurowe",
    "Fryzjerstwo", "Kucharstwo", "Kosmetologia", "Pielęgniarstwo", "Technologia drewna", "Spawalnictwo", "Stolarstwo",
    "Krawiectwo", "Florystyka", "Technik rolnik", "Technik weterynarii", "Technik budownictwa", "Technik architektury krajobrazu",
    "Technik elektryk", "Technik informatyk", "Technik logistyk", "Technik mechanik", "Technik teleinformatyk",
    "Technik transportu drogowego", "Technik ochrony środowiska", "Technik geodeta", "Technik rachunkowości",
    "Technik reklamy"
];

function filterSubjects() {
    const input = document.getElementById("subject").value.toLowerCase();
    const suggestions = document.getElementById("suggestions");
    suggestions.innerHTML = '';
    suggestions.style.display = 'none';

    if (input) {
        const filteredSubjects = subjects.filter(subject => subject.toLowerCase().includes(input));
        
        if (filteredSubjects.length > 0) {
            suggestions.style.display = 'block';
            filteredSubjects.forEach(subject => {
                const div = document.createElement('div');
                div.textContent = subject;
                div.style.padding = '8px';
                div.style.cursor = 'pointer';
                div.addEventListener('click', function() {
                    document.getElementById('subject').value = subject;
                    suggestions.innerHTML = '';
                    suggestions.style.display = 'none';
                });
                suggestions.appendChild(div);
            });
        }
    }
}

document.getElementById("subject").addEventListener("input", filterSubjects);

document.addEventListener('click', function(event) {
    if (!event.target.closest('#subject')) {
        document.getElementById("suggestions").style.display = 'none';
    }
});

function setupFileInput(inputId, infoId, nameId, removeId) {
    const fileInput = document.getElementById(inputId);
    const fileInfo = document.getElementById(infoId);
    const fileName = document.getElementById(nameId);
    const removeFile = document.getElementById(removeId);

    if (!fileInput || !fileInfo || !fileName || !removeFile) return;

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileName.textContent = this.files[0].name;
            fileInfo.style.display = 'flex';
            
            gsap.from(fileInfo, {
                duration: 0.3,
                opacity: 0,
                y: -5,
                ease: "back.out"
            });
        } else {
            fileInfo.style.display = 'none';
        }
    });

    removeFile.addEventListener('click', function(e) {
        e.stopPropagation();
        fileInput.value = '';
        fileInfo.style.display = 'none';
        
        gsap.to(this, {
            duration: 0.1,
            scale: 0.8,
            yoyo: true,
            repeat: 1,
            ease: "power2.out"
        });
    });

    fileInfo.addEventListener('click', function() {
        fileInput.click();
    });
}

function openModal() {
    document.getElementById("postModal").style.display = "block";
    gsap.from("#postModal", {
        duration: 0.4,
        opacity: 0,
        y: 50,
        scale: 0.9,
        ease: 'back.out(1.7)'
    });
    setTimeout(() => { document.getElementById("content").focus(); }, 100);
}

function closeModal() {
    document.getElementById("postModal").style.display = "none";
}

function editPost(postId) {
    let content = document.querySelector(`#post-${postId} p`).innerText;
    openEditModal(postId, content);
}

function openEditModal(postId, content) {
    document.getElementById("edit_post_id").value = postId;
    document.getElementById("edit_content").value = content;
    document.getElementById("editPostModal").style.display = "block";
    
    gsap.from("#editPostModal", {
        duration: 0.4,
        opacity: 0,
        y: 50,
        scale: 0.9,
        ease: 'back.out(1.7)'
    });
}

function closeEditModal() {
    document.getElementById("editPostModal").style.display = "none";
}

function confirmDelete(postId) {
    const postElement = document.getElementById(`post-${postId}`);
    
    gsap.set(postElement, { x: 0, boxShadow: 'none' });

    gsap.to(postElement, {
        duration: 0.1,
        x: -10,
        repeat: 0,
        yoyo: true,
        ease: 'power1.inOut',
        boxShadow: '0 0 0 4px rgba(255, 0, 0, 0.7)',
        onComplete: () => {
            const timeline = gsap.timeline({
                onComplete: () => {
                    if (confirm("Na pewno chcesz usunąć ten post?")) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'home.php';
                        
                        const inputAction = document.createElement('input');
                        inputAction.type = 'hidden';
                        inputAction.name = 'action';
                        inputAction.value = 'delete_post';
                        
                        const inputId = document.createElement('input');
                        inputId.type = 'hidden';
                        inputId.name = 'post_id';
                        inputId.value = postId;
                        
                        form.appendChild(inputAction);
                        form.appendChild(inputId);
                        document.body.appendChild(form);
                        form.submit();
                    } else {
                        gsap.to(postElement, { x: 0, boxShadow: 'none', duration: 0.3 });
                    }
                }
            });
            
            timeline.to(postElement, {
                boxShadow: '0 0 0 8px rgba(255, 0, 0, 0.7)',
                duration: 1,
                ease: 'power1.inOut'
            }).to(postElement, {
                boxShadow: '0 0 0 8px rgba(255, 0, 0, 0)',
                duration: 1,
                ease: 'power1.inOut'
            });
        }
    });
}
function fetchConversations() {
    document.querySelector('.main-content').innerHTML = `
        <div id="messages-cont">
            <div id="messageModal" style="position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#000; border: 1px var(--light) solid; border-radius: 10px; padding:20px; width:400px; z-index:9999;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3>Wiadomości</h3>
                    <button onclick="startNewConversation()" title="Nowa wiadomość" style="font-size:20px;">➕</button>
                </div>
                <hr>
                <div id="conversations-list">
                    <p>Ładowanie konwersacji...</p>
                </div>
            </div>
        </div>
    `;
    fetch('get_conversations.php')
        .then(res => res.text())
        .then(html => {
            if (html.trim() === '') {
                document.getElementById('conversations-list').innerHTML = '<p>Brak konwersacji</p>';
            } else {
                document.getElementById('conversations-list').innerHTML = html;
            }
        })
        .catch(error => {
            console.error('Błąd przy ładowaniu konwersacji:', error);
            document.getElementById('conversations-list').innerHTML = '<p>Wystąpił błąd podczas ładowania konwersacji</p>';
        });
}



function startNewMessage() {
    alert('formularz nowej wiadomości');
}

function openConversation(otherEmail) {
    alert("Otwieram rozmowę z: " + otherEmail);
}

function showNewConversationForm() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center;">
            <div style="background:#000; padding:20px; border-radius:5px;">
                <h3>Nowa wiadomość</h3>
                <input type="text" id="recipient-email" placeholder="E-mail odbiorcy" />
                <br><br>
                <button onclick="startConversation()">Rozpocznij</button>
                <button onclick="this.closest('div').parentNode.remove()">Anuluj</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function startNewConversation() {
    fetch('get_user_list.php')
        .then(res => res.json())
        .then(users => {
            document.getElementById('modal-body').innerHTML = `
                <h3>Nowa wiadomość</h3>
                <input type="text" id="user-search" placeholder="Wpisz email..." oninput="filterUserList()" style="width:100%;margin-bottom:10px;">
                <div id="user-list" style="max-height:200px;overflow-y:auto;"></div>
            `;
            const listDiv = document.getElementById('user-list');
            users.forEach(email => {
                const div = document.createElement('div');
                div.textContent = email;
                div.style = "padding:5px;cursor:pointer;border-bottom:1px solid #ccc;";
                div.onclick = () => {
                    openConversation(email);
                    document.getElementById('modal-new-msg').style.display = 'none';
                };
                listDiv.appendChild(div);
            });
            document.getElementById('modal-new-msg').style.display = 'block';
        });
}

function startConversation() {
    const email = document.getElementById('recipient-email').value.trim();
    if (!email) {
        alert("Podaj e-mail odbiorcy.");
        return;
    }
}

function openConversation(otherEmail) {
    fetch('get_messages.php?with=' + encodeURIComponent(otherEmail))
        .then(res => res.text())
        .then(html => {
            const modal = document.createElement('div');
            modal.innerHTML = `
                <div id="convo-modal-parent" style="position:fixed; top:0; left:0; width:100%; height:100%;
                    background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center;">
                    <div id="convo-modal" style="background:#000; border: 1px var(--light) solid; border-radius: 10px; padding:20px; width:400px; height:500px; overflow-y:auto; display:flex; flex-direction:column;">
                        <div id="conversation-messages" style="flex:1; overflow-y:auto; margin-bottom:10px;">
                            ${html}
                        </div>
                        <div style="display:flex;">
                            <input type="text" id="new-message-input" style="flex:1;" placeholder="Wpisz wiadomość..." />
                            <button onclick="sendMessage('${otherEmail}')">Wyślij</button>
                        </div>
                        <button onclick=removeConvo()>Zamknij</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        });
}

function sendMessage(otherEmail) {
    const input = document.getElementById('new-message-input');
    const msg = input.value.trim();
    if (!msg) return;

    fetch('send_messages.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `to=${encodeURIComponent(otherEmail)}&message=${encodeURIComponent(msg)}`
    })
        .then(res => res.text())
        .then(resp => {
            if (resp.trim() === 'OK') {
                input.value = '';
                openConversation(otherEmail);
            } else {
                alert('Błąd wysyłania: ' + resp);
            }
        });
}
function filterUserList() {
const search = document.getElementById('user-search').value.toLowerCase();
const items = document.querySelectorAll('#user-list div');
items.forEach(div => {
const match = div.textContent.toLowerCase().includes(search);
div.style.display = match ? 'block' : 'none';
});
}


function removeConvo() {
    document.getElementById('convo-modal').remove();
    document.getElementById('convo-modal-parent').remove();
}



function loadProfile() {
    document.querySelector('.main-content').innerHTML = `
        <div class="user-info">
            <h1>Twoje Dane</h1>
            <p><label>Email:</label> <span><?php echo $localuseremail; ?></span></p>
           <p><label>Numer telefonu:</label> <span><?php echo $localuserphone; ?></span></p>
        </div>`;
}

function loadSettings() {
    document.querySelector('.main-content').innerHTML = ` 
        <div class="settings-section">
            <h1>Ustawienia konta</h1>
            <label for="email">Adres email:</label>
            <input type="email" id="email" value="<?php echo $localuseremail; ?>" readonly>
            <label for="newPassword">Zapomniałeś hasła?</label>
            <input type="password" id="newPassword" placeholder="Wprowadź nowe hasło" class="styled-input">
            <div id="password-strength"></div>
            <div id="update-password-btn" style="display: none;">
                <button onclick="updatePassword()" class="add-post-btn">Zaktualizuj hasło</button>
            </div>
        </div>`;
    
    document.getElementById("newPassword").addEventListener("input", checkPasswordStrength);
}


function loadFAQ() {
    document.querySelector('.main-content').innerHTML = `
        <div id="faq-cont">
            <h1>FAQ</h1>
            <p style="text-weight: bold;">Czym jest LoreNest?</p>
            <p>LoreNest jest to aplikacja ułatwiająca użytkownikom znajdowanie korepetytorów/nauczycieli prywatnych, którzy mogą pomóc w nauce.</p>
            <br><br>
            <p style="text-weight: bold;">Ile kosztuje członkowstwo?</p>
            <p>Członkowstwo jest bezpłatne, jedynymi opłatami jakie może ponieść użytkownik to opłaty za lekcje prowadzone przez nauczycieli pochodzących z naszej platformy</p>
            <br><br>
            <p style="text-weight: bold;">Jak mogę skontaktować się z osobą świadczącą pomoc?</p>
            <p>Aby skontaktować się z osobą świadczącą pomoc, wystarczy że skopiujesz mail podany w nagłówku wiadomości, a następnie napiszesz do niej maila</p>
            <br><br>
            <p style="text-weight: bold;">Kto może ogłaszać się jako nauczyciel?</p>
            <p>Jako nauczyciel może ogłaszać się każda osoba, która ma zakres wiedzy w temacie na który jest zapotrzebowanie, lecz jeśli osoba, która świadczy usługi nie wykazuje się taką wiedzą i zostanie zgłoszona, jej konto może zostać permanentnie zbanowane</p>
            <br><br>
            <p style="text-weight: bold;">Straciłem hasło, jak mogę je zresetować?</p>
            <p>Aby zresetować hasło przejdź do zakładki ustawienia i tam w odpowiednim polu wpisz nowe hasło</p>
            <br><br>
            <p style="text-weight: bold">Straciłem dostęp do konta, czy mogę je odzyskać></p>
            <p>Nie ma możliwości odzyskania dostępu do utraconego konta, w takim przypadku prosimy wysłać prośbę o usunięcie go na maila supportu, a następnie po +- dobie utworzenie nowego konta</p>
        </div>`;
}
function loadChat() {
    const style = document.createElement('style');
    style.innerHTML = `
    .chat-box {
        --neon-primary: #0ff0fc;
        --neon-secondary: #ff00ff;
        width: 420px;
        min-height: 300px;
        height: auto;
        padding-top: 30px;
        padding-left: 30px;
        padding-right: 30px;
        padding-bottom: 30px !important;
        border: 1px solid transparent;
        border-radius: 16px;
        background: 
            linear-gradient(145deg, #111827, #1f2937),
            radial-gradient(circle at 75% 30%, #0ff0fc, transparent 70%),
            radial-gradient(circle at 25% 70%, #ff00ff, transparent 70%);
        box-shadow: 
            0 0 15px rgba(15, 240, 252, 0.3),
            0 0 30px rgba(255, 0, 255, 0.2),
            0 0 60px rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        animation: pulse-glow 6s infinite alternate;
        color: white;
        position: relative;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .chat-box-input-container {
        position: relative;
        width: 100%;
        margin-bottom: 5px;
    }
    .chat-box input[type="text"] {
        width: 100%; 
        font-size: 30px;
        margin-bottom: 10px;
        padding: 12px 15px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.3);
        color: white;
        font-family: inherit;
        transition: all 0.3s ease;
        box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.5);
        display: block;
        margin: 0; 
    }
    .chat-box input[type="text"]:focus {
        outline: none;
        border-color: var(--neon-primary);
        box-shadow: 
            inset 0 0 15px rgba(15, 240, 252, 0.5),
            0 0 10px rgba(15, 240, 252, 0.3);
        background: rgba(0, 0, 0, 0.5);
    }
    .chat-box input[type="text"]::placeholder {
        color: rgba(255, 255, 255, 0.5);
        font-style: italic;
    }
    .chat-box button {
        margin-bottom: 10px;
        background: linear-gradient(45deg, var(--neon-primary), var(--neon-secondary));
        color: black;
        font-size: 30px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
        z-index: 1;
        transition: all 0.3s ease;
        box-shadow: 0 0 15px rgba(15, 240, 252, 0.5);
        width: 100%;
        font-family: inherit;
        margin-top: 5px;
    }
    .chat-box button:hover {
        transform: translateY(-2px);
        box-shadow: 
            0 0 20px rgba(15, 240, 252, 0.8),
            0 0 30px rgba(255, 0, 255, 0.6);
    }
    .chat-box button:hover::before {
        left: 100%;
    }
    .chat-box button:active {
        transform: translateY(1px);
    }
    .chat-box p {
        margin: 0;
        padding: 0;
        line-height: 1.6;
        position: relative;
        padding-left: 15px;
        font-size: 22px;
    }
    .chat-box p::before {
        line-height: 1.6;
        content: '>';
        left: 0;
        color: var(--neon-primary);
        position: absolute;
        margin-left: 0; 
    }
    .typing-animation {
        display: inline-block;
        white-space: nowrap;
        border-right: 3px solid var(--neon-primary);
        animation: typing 3s steps(30) 1s forwards, blink 0.75s step-end infinite;
    }

    @keyframes typing {
        from {
            width: 0;
        }
        to {
            width: 100%;
        }
    }
    @keyframes blink {
        0% {
            border-color: transparent;
        }
        50% {
            border-color: var(--neon-primary);
        }
        100% {
            border-color: transparent;
        }
    }
    @keyframes pulse-glow {
        0% {
            box-shadow: 
                0 0 15px rgba(15, 240, 252, 0.3),
                0 0 30px rgba(255, 0, 255, 0.2);
        }
        100% {
            box-shadow: 
                0 0 25px rgba(15, 240, 252, 0.5),
                0 0 50px rgba(255, 0, 255, 0.3);
        }
    }
    @keyframes shine {
        0% {
            transform: rotate(30deg) translate(-10%, -10%);
        }
        100% {
            transform: rotate(30deg) translate(10%, 10%);
        }
    }
    `;
    document.head.appendChild(style);
    document.querySelector('.main-content').innerHTML = `
    <div id="chat-cont"> 
        <div class="chat-box">
            <input type="text" id="user-input" placeholder="Witaj, będę Ci asystował">
            <button onclick="sendChat()">ASK ME</button>
            <p id="response"></p>
        </div>
    </div>`;
    
}
function sendChat() {
            const userInput = document.getElementById('user-input').value;
            fetch('http://localhost:5000/api/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ input: userInput })
            })
            .then(response => response.json())
            .then(data => {
                const responseText = data.response || data.error;
                const responseElement = document.getElementById('response');
                responseElement.innerHTML = '';
                typeWriter(responseElement, responseText);
            })
            .catch(error => console.error('Error:', error));
        }

        function typeWriter(element, text, i = 0) {
            if (i < text.length) {
                element.innerHTML += text.charAt(i);
                i++;
                setTimeout(() => typeWriter(element, text, i), 50);
            }
        }



function loadRules() {
    document.querySelector('.main-content').innerHTML = `
        <div id="regulamin-cont">
            <h1>Regulamin</h1>
            <ul>
                <li>Każdy użytkownik musi zachowywać się tak jak należy w miejscu publicznym.</li>
                <li>Publikowanie treści wulgarnych lub obraźliwych jest zabronione.</li>
                <li>Użytkownicy są odpowiedzialni za swoje działania na platformie, a działanie na jej szkodę będzie obciążone odpowiedzialnością.</li>
                <li>Zakazuje się sprzedawania nielegalnych treści lub plików (chyba że za darmo wyślesz je adminom).</li>
            </ul>
        </div>`;
}

function loadPosts() {
    window.location.href = 'home.php';
}


function clearSearch() {
    document.getElementById("search-input").value = "";
    window.location.href = "home.php";
}

function openFullScreen(imgElement) {
    const fullScreenImage = document.createElement("img");
    fullScreenImage.src = imgElement.src;
    fullScreenImage.style.position = "fixed";
    fullScreenImage.style.top = "0";
    fullScreenImage.style.left = "0";
    fullScreenImage.style.width = "100%";
    fullScreenImage.style.height = "100%";
    fullScreenImage.style.objectFit = "contain";
    fullScreenImage.style.zIndex = "9999";
    fullScreenImage.style.cursor = "zoom-out";
    fullScreenImage.onclick = function() {
        document.body.removeChild(fullScreenImage);
    };
    document.body.appendChild(fullScreenImage);
}

function checkPasswordStrength() {
    const password = document.getElementById("newPassword").value;
    const strengthDisplay = document.getElementById("password-strength");
    const updateBtn = document.getElementById("update-password-btn");
    
    strengthDisplay.innerHTML = '';
    updateBtn.style.display = 'none';
    
    if (password.length === 0) return;
    
    const hasMinLength = password.length >= 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);
    
    const requirements = [
        { met: hasMinLength, text: "Minimum 8 znaków" },
        { met: hasUpperCase, text: "Przynajmniej 1 wielka litera" },
        { met: hasLowerCase, text: "Przynajmniej 1 mała litera" },
        { met: hasNumber, text: "Przynajmniej 1 cyfra" }
    ];
    
    const list = document.createElement('ul');
    requirements.forEach(req => {
        const item = document.createElement('li');
        item.style.color = req.met ? 'green' : 'red';
        item.innerHTML = req.met ? '✓ ' + req.text : '✗ ' + req.text;
        list.appendChild(item);
    });
    
    strengthDisplay.appendChild(list);
    
    if (requirements.every(req => req.met)) {
        updateBtn.style.display = 'block';
        gsap.from(updateBtn, {
            duration: 0.5,
            opacity: 0,
            y: 20,
            ease: "back.out"
        });
    }
}

function updatePassword() {
    const newPassword = document.getElementById("newPassword").value;

    if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/.test(newPassword)) {
        alert("Hasło musi zawierać min. 8 znaków, w tym wielkie i małe litery oraz cyfry");
        return;
    }

    fetch('update-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `new_password=${encodeURIComponent(newPassword)}&email=${encodeURIComponent('<?php echo $localuseremail; ?>')}`
    })
    .then(response => response.text())
    .then(data => {
        if (data === "success") {
            alert("Hasło zostało zaktualizowane!");
            loadSettings();
        } else {
            alert("Wystąpił błąd podczas aktualizacji hasła.");
        }
    })
    .catch(error => {
        console.error('Błąd: ', error);
        alert("Wystąpił problem z połączeniem. Spróbuj ponownie.");
    });
}

document.addEventListener('DOMContentLoaded', function() {
    gsap.utils.toArray('.post').forEach((post, i) => {
        gsap.from(post, {
            duration: 0.5,
            opacity: 0,
            y: 30,
            delay: i * 0.1,
            ease: 'back.out'
        });
    });

    setupFileInput('attachment', 'file-info', 'file-name', 'remove-file');
    setupFileInput('edit_attachment', 'edit-file-info', 'edit-file-name', 'edit-remove-file');

    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(function() {
            gsap.to(successMessage, {
                duration: 0.3,
                opacity: 0,
                onComplete: () => successMessage.remove()
            });
        }, 5000);
    }

    document.getElementById('search-input').addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const posts = document.querySelectorAll('.post');
        
        posts.forEach(post => {
            const text = post.textContent.toLowerCase();
            gsap.to(post, {
                duration: 0.3,
                opacity: text.includes(query) ? 1 : 0,
                scale: text.includes(query) ? 1 : 0.9,
                ease: 'power2.out',
                onComplete: () => {
                    post.style.display = text.includes(query) ? 'block' : 'none';
                }
            });
        });
    });
});

window.onclick = function(event) {
    if (!event.target.matches('.dropdown-response > a')) {
        document.getElementById("dropdownMenu").classList.remove("show");
    }
};
function checkBannedWords(content) {
    for (let i = 0; i < bannedWords.length; i++) {
        if (content.toLowerCase().includes(bannedWords[i].toLowerCase())) {
            return bannedWords[i];
        }
    }
    return null;
}
document.getElementById('content').addEventListener('input', function() {
    const content = this.value;
    const bannedWord = checkBannedWords(content);

    const messageElement = document.getElementById('banned-word-message');
    if (bannedWord) {
        messageElement.style.display = 'block';
        messageElement.innerHTML = `⛔ Treść zawiera zabronione słowo: '${bannedWord}'`;
    } else {
        messageElement.style.display = 'none';
    }
});
function toggleDropdown() {
    var dropdownMenu = document.getElementById('dropdownMenu');
    if (dropdownMenu.style.display === "block") {
        dropdownMenu.style.display = "none"; 
    } else {
        dropdownMenu.style.display = "block"; 
    }
}
</script>

</body>
</html>
