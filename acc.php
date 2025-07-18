<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] !== true) {
    header("Location: login.php");
    exit;
}


$currentUserID = $_SESSION['userID'] ?? 1;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_users";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}


if (isset($_POST['delete_post_id'])) {
    $postID = (int)$_POST['delete_post_id'];

    $checkStmt = $conn->prepare("SELECT * FROM posts WHERE postID = ? AND userID = ?");
    $checkStmt->execute([$postID, $currentUserID]);
    $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $deleteStmt = $conn->prepare("DELETE FROM posts WHERE postID = ?");
        $deleteStmt->execute([$postID]);
    }

    header("Location: acc.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE userID = ?");
$stmt->execute([$currentUserID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM posts WHERE userID = ? ORDER BY created_at DESC");
$stmt->execute([$currentUserID]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 tusahunter - Мой аккаунт</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="web3-style.css">
    <style>
        .account-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: var(--spacing-xl) 0;
        }

        .account-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--spacing-2xl);
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
        }

        .profile-sidebar {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin: 0 auto var(--spacing-md);
        }

        .profile-info {
            margin-bottom: var(--spacing-lg);
        }

        .profile-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md) 0;
            border-bottom: 1px solid var(--border-light);
        }

        .profile-item:last-child {
            border-bottom: none;
        }

        .profile-item-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: var(--bg-elevated);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }

        .profile-item-content {
            flex: 1;
        }

        .profile-item-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: var(--spacing-xs);
        }

        .profile-item-value {
            font-weight: 500;
            color: var(--text-primary);
        }

        .posts-section {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
        }

        .posts-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xl);
        }

        .posts-header h2 {
            margin: 0;
            color: var(--text-primary);
        }

        .posts-count {
            background: var(--accent-gradient);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .user-post {
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .user-post:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--border-medium);
        }

        .post-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .post-content {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: var(--spacing-lg);
        }

        .post-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .logout-section {
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-xl);
            border-top: 1px solid var(--border-light);
        }

        @media (max-width: 768px) {
            .account-wrapper {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
            
            .profile-sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
<header class="header">
    <div class="container">
        <nav class="nav">
                    <div class="nav-brand">
            <i class="fas fa-rocket"></i> tusahunter
        </div>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link"><i class="fas fa-home"></i> Лента</a></li>
                <li><a href="create.php" class="nav-link"><i class="fas fa-plus"></i> Создать</a></li>
                          <li><a href="friends.php" class="nav-link"><i class="fas fa-users"></i> Друзья</a></li>
          <li><a href="acc.php" class="nav-link active"><i class="fas fa-user"></i> Аккаунт</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="account-container main-content">
    <div class="account-wrapper">
        <div class="profile-sidebar animate-fade-in">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['firstName'], 0, 1)) ?>
                </div>
                <h2 style="margin: 0; color: var(--text-primary);">
                    <?= htmlspecialchars($user['firstName']) ?> <?= htmlspecialchars($user['lastName']) ?>
                </h2>
                <p style="color: var(--text-muted); margin: 0;">@<?= htmlspecialchars($user['username']) ?></p>
            </div>

            <div class="profile-info">
                <div class="profile-item">
                    <div class="profile-item-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-item-content">
                        <div class="profile-item-label">Имя</div>
                        <div class="profile-item-value"><?= htmlspecialchars($user['firstName']) ?></div>
                    </div>
                </div>

                <div class="profile-item">
                    <div class="profile-item-icon">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="profile-item-content">
                        <div class="profile-item-label">Фамилия</div>
                        <div class="profile-item-value"><?= htmlspecialchars($user['lastName']) ?></div>
                    </div>
                </div>

                <div class="profile-item">
                    <div class="profile-item-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="profile-item-content">
                        <div class="profile-item-label">Email</div>
                        <div class="profile-item-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                </div>
            </div>

            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="btn btn-secondary w-full">
                        <i class="fas fa-sign-out-alt"></i> Выйти из аккаунта
                    </button>
                </form>
            </div>
        </div>

        <div class="posts-section animate-fade-in">
            <div class="posts-header">
                <i class="fas fa-edit"></i>
                <h2>Мои события</h2>
                <span class="posts-count"><?= count($posts) ?></span>
            </div>

            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="user-post">
                        <h3 class="post-title"><?= htmlspecialchars($post['name']) ?></h3>
                        
                        <div class="post-meta">
                            <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($post['created_at']) ?></span>
                            <?php if (!empty($post['category'])): ?>
                                <span><i class="fas fa-tag"></i> <?= htmlspecialchars($post['category']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($post['description'])) ?>
                        </div>

                        <div class="post-actions">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="delete_post_id" value="<?= (int)$post['postID'] ?>">
                                <button type="submit" 
                                        class="btn btn-secondary btn-sm" 
                                        onclick="return confirm('Вы точно хотите удалить этот пост?');">
                                    <i class="fas fa-trash"></i> Удалить
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center p-4">
                    <i class="fas fa-plus-circle" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3 style="color: var(--text-muted); margin-bottom: 0.5rem;">Пока нет событий</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Создайте ваше первое событие!</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Создать событие
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
