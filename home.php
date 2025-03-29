<?php
session_start();
include("db.php");

header('Content-Type: text/html; charset=utf-8');

if (isset($_SESSION['email'])) {
  $localuseremail = $_SESSION['email'];
  $localuserphone = $_SESSION['phone'];
  echo "<script>console.log('Zalogowany użytkownik: " . $localuseremail . "');</script>";
} else {
  echo "<script>console.log('Brak zalogowanego użytkownika.');</script>";
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// dodawanie posta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_post') {
    try {
        $username = sanitizeInput($_POST['username']);
        $content = sanitizeInput($_POST['content']);
        $timestamp = date("Y-m-d H:i:s");
        $file_path = null;
        $file_type = null;

        // dodawanie pliku
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $file_name = sanitizeInput($_FILES['attachment']['name']);
            $file_size = $_FILES['attachment']['size'];
            $file_type = $_FILES['attachment']['type'];
            
            if (!is_uploaded_file($_FILES['attachment']['tmp_name'])) {
                throw new Exception("Nieprawidłowe przesyłanie pliku");
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/msword', 
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                            'application/pdf', 'application/vnd.ms-excel', 
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                            'text/plain'];

            $max_size = 5 * 1024 * 1024;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'pdf', 'xls', 'xlsx', 'txt'];
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

        if (empty($username) || empty($content)) {
            throw new Exception("Wypełnij wszystkie wymagane pola");
        }

        $stmt = $pdo->prepare("INSERT INTO posts (username, content, timestamp, file_path, file_type) 
                              VALUES (:username, :content, :timestamp, :file_path, :file_type)");
        $stmt->execute([
            ':username' => $username,
            ':content' => $content,
            ':timestamp' => $timestamp,
            ':file_path' => $file_path,
            ':file_type' => $file_type
        ]);

        $_SESSION['message'] = "Post został dodany pomyślnie";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// edytowanie posta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_post') {
    try {
        $post_id = (int)$_POST['post_id'];
        $new_content = sanitizeInput($_POST['content']);

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

// usuwanie posta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_post') {
    try {
        $post_id = (int)$_POST['post_id'];
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute([':id' => $post_id]);

        $_SESSION['message'] = "Post został usunięty";
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); 
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// wyszukiwanie
$search_query = '';
$posts = [];

try {
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = sanitizeInput($_GET['search']);
        $stmt = $pdo->prepare("SELECT id, username, content, timestamp, file_path, file_type FROM posts 
                              WHERE content LIKE :search_content OR username LIKE :search_username 
                              ORDER BY timestamp DESC");
        $stmt->execute([
            ':search_content' => "%$search_query%",
            ':search_username' => "%$search_query%"
        ]);
    } else {
        $stmt = $pdo->query("SELECT id, username, content, timestamp, file_path, file_type FROM posts ORDER BY timestamp DESC");
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
        <label for="edit_attachment" id="edit-attach-label" style="cursor: pointer;">Załącz plik:</label>
        <input type="file" id="edit_attachment" name="attachment" style="display: none;">
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
        <label for="attachment" id="attach-label" style="cursor: pointer;">Załącz plik:</label>
        <input type="file" id="attachment" name="attachment" style="display: none;">
        <div id="file-info" class="file-info" style="display: none;">
            <span id="file-name" class="file-name"></span>
            <span id="remove-file" class="remove-file" title="Usuń plik">&times;</span>
        </div>
        <button type="submit" class="add-post-btn">Wyślij</button>
        <button type="button" class="add-post-btn" onclick="closeModal()">Anuluj</button>
    </form>
</div>

<header>
    <div class="logo" onclick="loadPosts()"></div>
    <div class="dropdown-response" onclick="toggleDropdown()" hidden>
        <a href="javascript:void(0);" id="response-menu">☰ Menu</a>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="javascript:void(0);" onclick="loadProfile()">Profil</a>
            <a href="javascript:void(0);" onclick="loadSettings()">Ustawienia</a>
            <a href="javascript:void(0);" onclick="loadRules()">Zasady</a>
            <a href="javascript:void(0);" onclick="loadHelp()">Pomoc</a>
            <a href="https://github.com/dawidnolove/nest" target="blank">Github 
                <img id="icon-github" src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Github_logo_svg.svg/640px-Github_logo_svg.svg.png" width="16px">
            </a>
            <h1>&reg;&copy;</h1>
            <p><a id="logout" href="logout.php">Wyloguj</a></p>
        </div>
    </div>
</header>

<div class="layout">
    <div class="sidebar left-sidebar">
        <a href="javascript:void(0);" onclick="loadProfile()">Profil</a>
        <a href="javascript:void(0);" onclick="loadSettings()">Ustawienia</a>
        <p><a id="logout" href="logout.php">Wyloguj</a></p>
        <h1>&copy;</h1>
    </div>
    
    <div class="main-content">
        <h1>Posty użytkowników</h1>
        <div class="search-container">
            <form method="GET" action="home.php">
                <input type="text" name="search" id="search-input" placeholder="Szukaj postów..." value="<?= htmlspecialchars($search_query) ?>" class="styled-input">
                <button type="button" class="clear-btn" onclick="clearSearch()">Pokaż wszystkie</button> 
            </form>
            <div id="search-results"></div>
            <div class="back-button" onclick="loadPosts()" hidden></div>
        </div>

        <button class="add-post-btn" onclick="openModal()">Dodaj Post</button>
        
        <div id="postsContainer">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                <div class="post" id="post-<?= htmlspecialchars($post['id']) ?>">
                    <h3><?= htmlspecialchars($post['username']) ?></h3>
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
                            <?php if (isset($_SESSION['email']) && $_SESSION['email'] === $post['username']): ?>
                                <button onclick="editPost(<?= $post['id'] ?>)">Edytuj</button>
                                <button onclick="confirmDelete(<?= $post['id'] ?>)" id="del-btn">Usuń</button>
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
    
    <div class="sidebar right-sidebar">
        <a href="javascript:void(0);" onclick="loadRules()">Zasady</a>
        <a href="javascript:void(0);" onclick="loadHelp()">Pomoc</a>
        <p><a href="https://github.com/dawidnolove/nest" target="blank">Github <img id="icon-github" src="https://upload.wikimedia.org/wikipedia/commons/9/91/Octicons-mark-github.svg"></a></p>
        <h1>&reg;</h1>
    </div>
</div>

<script>
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

function loadProfile() {
    document.querySelector('.main-content').innerHTML = `
        <div class="user-info">
            <h2>Twoje Dane</h2>
            <p><label>Email:</label> <span><?php echo $localuseremail; ?></span></p>
            <p><label>Numer telefonu:</label> <span><?php echo $localuserphone; ?></span></p>
        </div>
        <div class="back-button" onclick="loadPosts()"></div>`;
}

function loadSettings() {
    document.querySelector('.main-content').innerHTML = ` 
        <div class="settings-section">
            <h2>Ustawienia konta</h2>
            <label for="email">Adres email:</label>
            <input type="email" id="email" value="<?php echo $localuseremail; ?>" readonly>
            <label for="newPassword">Zapomniałeś hasła?</label>
            <input type="password" id="newPassword" placeholder="Wprowadź nowe hasło" class="styled-input">
            <div id="password-strength"></div>
            <div id="update-password-btn" style="display: none;">
                <button onclick="updatePassword()" class="add-post-btn">Zaktualizuj hasło</button>
            </div>
        </div>
        <div class="back-button" onclick="loadPosts()"></div>`;
    
    document.getElementById("newPassword").addEventListener("input", checkPasswordStrength);
}

function loadHelp() {
    document.querySelector('.main-content').innerHTML = `
        <h1>Pomoc</h1>
        <h2>Wprowadzenie do platformy</h2>
        <p>Nasza platforma pozwala uczniom pomagać sobie nawzajem w nauce czy nazwiązywać wartościowe kontakty.</p>
        <p>Nie pobeiramy opłat za ogloszenia lub członkostwo(narazie).</p>
        <div class="back-button" onclick="loadPosts()"></div>`;
}

function loadRules() {
    document.querySelector('.main-content').innerHTML = `
        <h1>Regulamin</h1>
        <ul>
            <li>Każdy użytkownik musi przestrzegać zasad społeczności.</li>
            <li>Publikowanie treści wulgarnych lub obraźliwych jest zabronione.</li>
            <li>Użytkownicy są odpowiedzialni za swoje działania na platformie.</li>
            <li>Zakazuje się udostępniania nielegalnych treści lub plików.</li>
            <li>Każdy użytkownik może usunąć swoje konto w dowolnym momencie.</li>
        </ul>
        <div class="back-button" onclick="loadPosts()"></div>`;
}

function loadPosts() {
    window.location.href = 'home.php';
}

function toggleDropdown() {
    document.getElementById("dropdownMenu").classList.toggle("show");
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
}
</script>
</body>
</html>