<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../controller/PostController.php';
require_once __DIR__ . '/../../../model/Post.php';

$postController = new PostController();

$errors = [
    'titre' => '',
    'type_post' => '',
    'statut_post' => '',
    'contenu' => '',
    'image' => ''
];

$old = [
    'titre' => '',
    'type_post' => '',
    'statut_post' => '',
    'contenu' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_post'])) {
    $old['titre'] = trim($_POST['titre'] ?? '');
    $old['type_post'] = trim($_POST['type_post'] ?? '');
    $old['statut_post'] = trim($_POST['statut_post'] ?? '');
    $old['contenu'] = trim($_POST['contenu'] ?? '');

    if ($old['titre'] === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    } elseif (mb_strlen($old['titre']) < 3) {
        $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
    }

    if ($old['type_post'] === '') {
        $errors['type_post'] = 'Veuillez choisir le type du post.';
    }

    if ($old['statut_post'] === '') {
        $errors['statut_post'] = 'Veuillez choisir le statut.';
    }

    if ($old['contenu'] === '') {
        $errors['contenu'] = 'Le contenu est obligatoire.';
    } elseif (mb_strlen($old['contenu']) < 5) {
        $errors['contenu'] = 'Le contenu doit contenir au moins 5 caractères.';
    }

    $imagePath = null;

    if (!empty($_FILES['image']['name'])) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $fileName = $_FILES['image']['name'];
        $tmpName = $_FILES['image']['tmp_name'];
        $fileSize = $_FILES['image']['size'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            $errors['image'] = 'Formats autorisés : JPG, JPEG, PNG, WEBP.';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors['image'] = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            $uploadDir = __DIR__ . '/../../../uploads/posts/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newName = uniqid('post_', true) . '.' . $extension;
            $destination = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $destination)) {
                $imagePath = 'uploads/posts/' . $newName;
            } else {
                $errors['image'] = "Erreur lors de l'upload de l'image.";
            }
        }
    }

    $hasErrors = false;
    foreach ($errors as $error) {
        if (!empty($error)) {
            $hasErrors = true;
            break;
        }
    }

    if (!$hasErrors) {
        $post = new Post(
            null,
            $old['titre'],
            $old['contenu'],
            $imagePath,
            $old['type_post'],
            $old['statut_post'],
            1
        );

        $postController->addPost($post);
        header('Location: /GoService/view/front/index.php?page=forum&published=1');
        exit;
    }
}

$posts = $postController->listPosts();

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function invalidClass($error)
{
    return !empty($error) ? 'field-invalid' : '';
}
?>

<style>
    .field-error {
        display: block;
        margin-top: 8px;
        color: #e25b5b;
        font-size: 13px;
        font-weight: 500;
        min-height: 18px;
    }

    .field-invalid {
        border: 1.8px solid #e25b5b !important;
        box-shadow: 0 0 0 3px rgba(226, 91, 91, 0.08);
    }

    .success-message {
        background: #eefaf2;
        color: #1f7a43;
        border: 1px solid #ccebd6;
        border-radius: 18px;
        padding: 14px 18px;
        margin-bottom: 18px;
        font-weight: 600;
    }

    .real-post-image {
        width: 100%;
        height: 310px;
        border-radius: 28px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        margin-top: 18px;
    }

    .comment-textarea {
        width: 100%;
        min-height: 120px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 20px;
        padding: 16px 18px;
        resize: vertical;
        background: #fff;
        outline: none;
        font: inherit;
    }

    .comment-textarea:focus {
        border-color: rgba(34, 197, 94, 0.35);
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.08);
    }

    .publish-btn-reset {
        border: none;
        background: none;
        padding: 0;
        margin: 0;
    }
</style>

<section class="page-hero reveal">
    <span class="section-badge">Forum</span>
    <h1 class="page-title">Forum & échanges</h1>
    <p class="page-intro">
        Une page riche visuellement pour publier, commenter, aimer, partager et suivre les threads.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un post...">
        <select>
            <option>Tous les posts</option>
            <option>Discussions</option>
            <option>Questions</option>
            <option>Conseils</option>
            <option>Récents</option>
        </select>
    </div>

    <div class="icon-actions">
        <button class="outline-btn" type="button">Mes posts</button>
        <button class="outline-btn" type="button">Posts enregistrés</button>
    </div>
</section>

<section class="module-split reveal">
    <div>
        <?php if (isset($_GET['published'])): ?>
            <div class="success-message">Post publié avec succès.</div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
    <?php
        $fullname = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? ''));
        if ($fullname === '') {
            $fullname = 'Utilisateur';
        }

        $avatarLetter = strtoupper(substr($fullname, 0, 1));
        $dateText = !empty($post['date_publication']) ? $post['date_publication'] : '';
        $imageUrl = !empty($post['image']) ? '/GoService/' . ltrim($post['image'], '/') : '';
    ?>
    <article class="post-card">
        <div class="post-top">
            <div class="post-user">
                <div class="mini-avatar"><?php echo e($avatarLetter); ?></div>
                <div>
                    <strong><?php echo e($fullname); ?></strong><br>
                    <span class="page-intro">
                        <?php echo e($post['type_post'] ?? 'Post'); ?> • <?php echo e($dateText); ?>
                    </span>
                </div>
            </div>
        </div>

        <h3><?php echo e($post['titre'] ?? ''); ?></h3>
        <p><?php echo nl2br(e($post['contenu'] ?? '')); ?></p>

        <?php if (!empty($imageUrl)): ?>
            <img
                src="<?php echo e($imageUrl); ?>"
                alt="Image post"
                class="forum-post-img"
                onerror="this.style.display='none';"
            >
        <?php endif; ?>

        <div class="forum-actions">
            <button class="outline-btn" type="button">👍 J’aime</button>
            <button class="outline-btn" type="button">💬 Commenter</button>
            <button class="outline-btn" type="button">🔁 Partager</button>
            <button class="outline-btn" type="button">🚩 Signaler</button>
            <button class="outline-btn" type="button">🔖 Enregistrer</button>
        </div>

        <div class="comment-box">
            <h4>Commentaires</h4>

            <div class="panel" style="margin-top:16px;">
                <span class="section-badge">Ajouter un commentaire</span>
                <div class="form-grid">
                    <textarea class="comment-textarea" placeholder="Écrire un commentaire..."></textarea>
                </div>
                <div class="icon-actions" style="margin-top:14px;">
                    <button class="solid-btn" type="button">Publier commentaire</button>
                </div>
            </div>
        </div>
    </article>
<?php endforeach; ?>
    </div>

    <div>
<article class="panel">
    <span class="section-badge">Créer un post</span>

    <form method="POST" enctype="multipart/form-data" id="postForm" novalidate>
        <div class="form-grid">
            <div>
                <input
                    type="text"
                    name="titre"
                    id="titre"
                    placeholder="Titre du post"
                    value="<?php echo e($old['titre']); ?>"
                    class="<?php echo invalidClass($errors['titre']); ?>"
                >
                <span class="field-error" id="err-titre"><?php echo e($errors['titre']); ?></span>
            </div>

            <div>
                <select
                    name="type_post"
                    id="type_post"
                    class="<?php echo invalidClass($errors['type_post']); ?>"
                >
                    <option value="">Type de post</option>
                    <option value="Discussion" <?php echo $old['type_post'] === 'Discussion' ? 'selected' : ''; ?>>Discussion</option>
                    <option value="Conseil" <?php echo $old['type_post'] === 'Conseil' ? 'selected' : ''; ?>>Conseil</option>
                    <option value="Question" <?php echo $old['type_post'] === 'Question' ? 'selected' : ''; ?>>Question</option>
                </select>
                <span class="field-error" id="err-type_post"><?php echo e($errors['type_post']); ?></span>
            </div>

            <div>
                <input
                    type="file"
                    name="image"
                    id="image"
                    accept=".jpg,.jpeg,.png,.webp"
                    class="<?php echo invalidClass($errors['image']); ?>"
                >
                <span class="field-error" id="err-image"><?php echo e($errors['image']); ?></span>
            </div>

            <div>
                <select
                    name="statut_post"
                    id="statut_post"
                    class="<?php echo invalidClass($errors['statut_post']); ?>"
                >
                    <option value="">Statut</option>
                    <option value="Visible" <?php echo $old['statut_post'] === 'Visible' ? 'selected' : ''; ?>>Visible</option>
                    <option value="Brouillon" <?php echo $old['statut_post'] === 'Brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                </select>
                <span class="field-error" id="err-statut_post"><?php echo e($errors['statut_post']); ?></span>
            </div>

            <div style="grid-column: 1 / -1;">
                <textarea
                    name="contenu"
                    id="contenu"
                    placeholder="Contenu du post..."
                    class="<?php echo invalidClass($errors['contenu']); ?>"
                ><?php echo e($old['contenu']); ?></textarea>
                <span class="field-error" id="err-contenu"><?php echo e($errors['contenu']); ?></span>
            </div>
        </div>

        <div class="icon-actions" style="margin-top:14px;">
            <button class="solid-btn" type="submit" name="publish_post">Publier</button>
            <button class="outline-btn" type="reset">Mettre à jour</button>
        </div>
    </form>
</article>
        <article class="panel" style="margin-top:18px;">
            <span class="section-badge">Fonctions prêtes</span>
            <div class="feature-list">
                <div class="feature-item">Consulter les posts</div>
                <div class="feature-item">Créer un post</div>
                <div class="feature-item">Commenter un post</div>
                <div class="feature-item">Ajouter une image au commentaire</div>
                <div class="feature-item">Réagir à un post</div>
                <div class="feature-item">Partager un post</div>
                <div class="feature-item">Signaler un post</div>
            </div>
        </article>
    </div>
</section>

<script>
const form = document.getElementById('postForm');

const rules = {
    titre: {
        validate: value => value.trim().length >= 3,
        message: 'Le titre doit contenir au moins 3 caractères.'
    },
    type_post: {
        validate: value => value !== '',
        message: 'Veuillez choisir le type du post.'
    },
    statut_post: {
        validate: value => value !== '',
        message: 'Veuillez choisir le statut.'
    },
    contenu: {
        validate: value => value.trim().length >= 5,
        message: 'Le contenu doit contenir au moins 5 caractères.'
    }
};

function setError(field, message) {
    field.classList.add('field-invalid');
    const errorBox = document.getElementById('err-' + field.id);
    if (errorBox) {
        errorBox.textContent = message;
    }
}

function clearError(field) {
    field.classList.remove('field-invalid');
    const errorBox = document.getElementById('err-' + field.id);
    if (errorBox) {
        errorBox.textContent = '';
    }
}

function validateField(field) {
    const rule = rules[field.id];
    if (!rule) return true;

    if (!rule.validate(field.value)) {
        setError(field, rule.message);
        return false;
    }

    clearError(field);
    return true;
}

Object.keys(rules).forEach(id => {
    const field = document.getElementById(id);
    if (!field) return;

    field.addEventListener('input', () => validateField(field));
    field.addEventListener('change', () => validateField(field));
});

const imageField = document.getElementById('image');
if (imageField) {
    imageField.addEventListener('change', function () {
        const errorBox = document.getElementById('err-image');
        this.classList.remove('field-invalid');
        errorBox.textContent = '';

        if (this.files.length > 0) {
            const file = this.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;

            if (!allowedTypes.includes(file.type)) {
                this.classList.add('field-invalid');
                errorBox.textContent = 'Formats autorisés : JPG, JPEG, PNG, WEBP.';
            } else if (file.size > maxSize) {
                this.classList.add('field-invalid');
                errorBox.textContent = "L'image ne doit pas dépasser 5 Mo.";
            }
        }
    });
}

form.addEventListener('submit', function (e) {
    let isValid = true;

    Object.keys(rules).forEach(id => {
        const field = document.getElementById(id);
        if (field && !validateField(field)) {
            isValid = false;
        }
    });

    if (imageField && imageField.files.length > 0) {
        const file = imageField.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;
        const errorBox = document.getElementById('err-image');

        if (!allowedTypes.includes(file.type)) {
            imageField.classList.add('field-invalid');
            errorBox.textContent = 'Formats autorisés : JPG, JPEG, PNG, WEBP.';
            isValid = false;
        } else if (file.size > maxSize) {
            imageField.classList.add('field-invalid');
            errorBox.textContent = "L'image ne doit pas dépasser 5 Mo.";
            isValid = false;
        }
    }

    if (!isValid) {
        e.preventDefault();
    }
});
</script>