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


if (isset($_POST['send_request_id'])) {
    $receiverID = (int)$_POST['send_request_id'];

    $stmt = $conn->prepare("SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$currentUserID, $receiverID]);
    $existsRequest = $stmt->fetch();

    $stmt = $conn->prepare("SELECT * FROM friends WHERE userID = ? AND friendID = ?");
    $stmt->execute([$currentUserID, $receiverID]);
    $existsFriend = $stmt->fetch();

    if (!$existsRequest && !$existsFriend && $receiverID !== $currentUserID) {
        $stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
        $stmt->execute([$currentUserID, $receiverID]);
    }

    header("Location: friends.php");
    exit;
}


if (isset($_POST['accept_request_id'])) {
    $requestID = (int)$_POST['accept_request_id'];

    $stmt = $conn->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$requestID, $currentUserID]);
    $request = $stmt->fetch();

    if ($request && $request['status'] === 'pending') {
        $stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
        $stmt->execute([$requestID]);

        $stmt = $conn->prepare("INSERT IGNORE INTO friends (userID, friendID) VALUES (?, ?), (?, ?)");
        $stmt->execute([$request['sender_id'], $request['receiver_id'], $request['receiver_id'], $request['sender_id']]);
    }

    header("Location: friends.php");
    exit;
}


if (isset($_POST['decline_request_id'])) {
    $requestID = (int)$_POST['decline_request_id'];

    $stmt = $conn->prepare("UPDATE friend_requests SET status = 'declined' WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$requestID, $currentUserID]);

    header("Location: friends.php");
    exit;
}


if (isset($_POST['remove_friend_id'])) {
    $friendID = (int)$_POST['remove_friend_id'];

    $stmt = $conn->prepare("DELETE FROM friends WHERE (userID = ? AND friendID = ?) OR (userID = ? AND friendID = ?)");
    $stmt->execute([$currentUserID, $friendID, $friendID, $currentUserID]);

    header("Location: friends.php");
    exit;
}

$stmt = $conn->prepare("
  SELECT u.* FROM friends f 
  JOIN users u ON f.friendID = u.userID 
  WHERE f.userID = ?
");
$stmt->execute([$currentUserID]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
  SELECT u.* FROM friend_requests fr 
  JOIN users u ON fr.receiver_id = u.userID 
  WHERE fr.sender_id = ? AND fr.status = 'pending'
");
$stmt->execute([$currentUserID]);
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
  SELECT fr.*, u.firstName, u.lastName, u.username FROM friend_requests fr 
  JOIN users u ON fr.sender_id = u.userID 
  WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$stmt->execute([$currentUserID]);
$incomingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$searchResults = [];
$searchQuery = '';

if (isset($_GET['q'])) {
    $searchQuery = trim($_GET['q']);
    if ($searchQuery !== '') {
        $stmt = $conn->prepare("
          SELECT * FROM users 
          WHERE (firstName LIKE ? OR lastName LIKE ? OR username LIKE ?) 
          AND userID != ?
        ");
        $like = '%' . $searchQuery . '%';
        $stmt->execute([$like, $like, $like, $currentUserID]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 tusahunter - Друзья</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="web3-style.css">
    <style>
        .friends-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #81ffef 0%, #f067b4 100%);
            padding: var(--spacing-xl) 0;
        }

        .friends-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--spacing-2xl);
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
        }

        .friends-sidebar {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .friends-content {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .friend-item, .request-item, .result-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .friend-item:last-child, .request-item:last-child, .result-item:last-child {
            border-bottom: none;
        }

        .friend-item:hover, .request-item:hover, .result-item:hover {
            background: var(--bg-elevated);
            border-radius: var(--radius-sm);
        }

        .friend-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .friend-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .friend-details {
            display: flex;
            flex-direction: column;
        }

        .friend-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .friend-username {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .friend-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .search-section {
            margin-bottom: var(--spacing-xl);
        }

        .search-form {
            display: flex;
            gap: var(--spacing-md);
        }

        .search-input {
            flex: 1;
        }

        .results-grid {
            display: grid;
            gap: var(--spacing-md);
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }

        .badge-count {
            background: var(--secondary-gradient);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: var(--spacing-sm);
        }

        @media (max-width: 768px) {
            .friends-wrapper {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
            
            .friends-sidebar {
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
                <li><a href="friends.php" class="nav-link active"><i class="fas fa-users"></i> Друзья</a></li>
                <li><a href="acc.php" class="nav-link"><i class="fas fa-user"></i> Аккаунт</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="friends-container main-content">
    <div class="friends-wrapper">
        <div class="friends-sidebar animate-fade-in">
            <div class="section-title">
                <i class="fas fa-users"></i> Мои друзья
                <?php if (count($friends) > 0): ?>
                    <span class="badge-count"><?= count($friends) ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($friends): ?>
                <?php foreach ($friends as $friend): ?>
                    <div class="friend-item">
                        <div class="friend-info">
                            <div class="friend-avatar">
                                <?= strtoupper(substr($friend['firstName'], 0, 1)) ?>
                            </div>
                            <div class="friend-details">
                                <div class="friend-name"><?= htmlspecialchars($friend['firstName']) ?> <?= htmlspecialchars($friend['lastName']) ?></div>
                                <div class="friend-username">@<?= htmlspecialchars($friend['username']) ?></div>
                            </div>
                        </div>
                        <div class="friend-actions">
                            <form method="post">
                                <input type="hidden" name="remove_friend_id" value="<?= (int)$friend['userID'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-user-times"></i> Удалить
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <p>Пока нет друзей</p>
                </div>
            <?php endif; ?>

            <div class="section-title" style="margin-top: var(--spacing-xl);">
                <i class="fas fa-paper-plane"></i> Отправленные заявки
                <?php if (count($pendingRequests) > 0): ?>
                    <span class="badge-count"><?= count($pendingRequests) ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($pendingRequests): ?>
                <?php foreach ($pendingRequests as $p): ?>
                    <div class="friend-item">
                        <div class="friend-info">
                            <div class="friend-avatar">
                                <?= strtoupper(substr($p['firstName'], 0, 1)) ?>
                            </div>
                            <div class="friend-details">
                                <div class="friend-name"><?= htmlspecialchars($p['firstName']) ?> <?= htmlspecialchars($p['lastName']) ?></div>
                                <div class="friend-username">@<?= htmlspecialchars($p['username']) ?></div>
                            </div>
                        </div>
                        <div class="friend-actions">
                            <span class="btn btn-sm" style="background: var(--accent-gradient); color: white; cursor: default;">
                                <i class="fas fa-clock"></i> Ожидание
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-envelope"></i>
                    <p>Нет активных заявок</p>
                </div>
            <?php endif; ?>

            <div class="section-title" style="margin-top: var(--spacing-xl);">
                <i class="fas fa-inbox"></i> Входящие заявки
                <?php if (count($incomingRequests) > 0): ?>
                    <span class="badge-count"><?= count($incomingRequests) ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($incomingRequests): ?>
                <?php foreach ($incomingRequests as $in): ?>
                    <div class="request-item">
                        <div class="friend-info">
                            <div class="friend-avatar">
                                <?= strtoupper(substr($in['firstName'], 0, 1)) ?>
                            </div>
                            <div class="friend-details">
                                <div class="friend-name"><?= htmlspecialchars($in['firstName']) ?> <?= htmlspecialchars($in['lastName']) ?></div>
                                <div class="friend-username">@<?= htmlspecialchars($in['username']) ?></div>
                            </div>
                        </div>
                        <div class="friend-actions">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="accept_request_id" value="<?= (int)$in['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Принять
                                </button>
                            </form>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="decline_request_id" value="<?= (int)$in['id'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Отклонить
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell"></i>
                    <p>Нет входящих заявок</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="friends-content animate-fade-in">
            <div class="section-title">
                <i class="fas fa-search"></i> Поиск друзей
            </div>
            
            <div class="search-section">
                <form method="get" class="search-form">
                    <input type="text" 
                           name="q" 
                           placeholder="Введите имя или имя пользователя" 
                           value="<?= htmlspecialchars($searchQuery) ?>" 
                           class="form-input search-input"
                           required>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Найти
                    </button>
                </form>
            </div>

            <?php if ($searchQuery): ?>
                <div class="section-title">
                    <i class="fas fa-list"></i> Результаты поиска
                </div>
                
                <?php if ($searchResults): ?>
                    <div class="results-grid">
                        <?php foreach ($searchResults as $result): ?>
                            <div class="result-item">
                                <div class="friend-info">
                                    <div class="friend-avatar">
                                        <?= strtoupper(substr($result['firstName'], 0, 1)) ?>
                                    </div>
                                    <div class="friend-details">
                                        <div class="friend-name"><?= htmlspecialchars($result['firstName']) ?> <?= htmlspecialchars($result['lastName']) ?></div>
                                        <div class="friend-username">@<?= htmlspecialchars($result['username']) ?></div>
                                    </div>
                                </div>
                                <div class="friend-actions">
                                    <form method="post">
                                        <input type="hidden" name="send_request_id" value="<?= (int)$result['userID'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-user-plus"></i> Отправить заявку
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>Пользователи не найдены</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>Введите имя или имя пользователя для поиска</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
