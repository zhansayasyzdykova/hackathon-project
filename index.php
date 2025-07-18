<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] !== true) {
    header("Location: login.php");
    exit;
}

$currentUserID = $_SESSION['userID'] ?? null;

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


if (isset($_POST['like_post_id'])) {
    $postId = (int)$_POST['like_post_id'];
    $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE postID = ?");
    $stmt->execute([$postId]);
    header("Location: index.php#post-$postId");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_post_id'], $_POST['comment_text'])) {
    $postID = (int)$_POST['comment_post_id'];
    $comment = trim($_POST['comment_text']);
    if ($comment !== '' && $currentUserID) {
        $stmt = $conn->prepare("INSERT INTO comments (postID, userID, comment) VALUES (?, ?, ?)");
        $stmt->execute([$postID, $currentUserID, $comment]);
    }
    header("Location: index.php#post-$postID");
    exit;
}


if (isset($_POST['attendance_post_id']) && isset($_POST['attendance_status'])) {
    $postID = (int)$_POST['attendance_post_id'];
    $status = $_POST['attendance_status'] === 'going' ? 'going' : 'not_going';

    $stmt = $conn->prepare("SELECT * FROM post_attendance WHERE postID = ? AND userID = ?");
    $stmt->execute([$postID, $currentUserID]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $stmt = $conn->prepare("UPDATE post_attendance SET status = ? WHERE postID = ? AND userID = ?");
        $stmt->execute([$status, $postID, $currentUserID]);
    } else {
        $stmt = $conn->prepare("INSERT INTO post_attendance (postID, userID, status) VALUES (?, ?, ?)");
        $stmt->execute([$postID, $currentUserID, $status]);
    }
    header("Location: index.php#post-$postID");
    exit;
}


$attendanceStmt = $conn->prepare("SELECT postID, status, COUNT(*) as count FROM post_attendance GROUP BY postID, status");
$attendanceStmt->execute();
$attendanceData = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

$attendanceMap = [];
foreach ($attendanceData as $row) {
    $attendanceMap[$row['postID']][$row['status']] = $row['count'];
}

$userAttendanceStmt = $conn->prepare("SELECT postID, status FROM post_attendance WHERE userID = ?");
$userAttendanceStmt->execute([$currentUserID]);
$userAttendance = $userAttendanceStmt->fetchAll(PDO::FETCH_KEY_PAIR);


$where = [];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "EXISTS (SELECT 1 FROM post_attendance WHERE post_attendance.postID = posts.postID AND post_attendance.userID = :userID AND post_attendance.status = :status)";
    $params[':userID'] = $currentUserID;
    $params[':status'] = $_GET['status'];
}

if (isset($_GET['friends_only']) && $_GET['friends_only'] === '1') {
    $where[] = "posts.userID IN (
        SELECT CASE WHEN friends.userID = :currentUserID THEN friends.friendID ELSE friends.userID END
        FROM friends
        WHERE friends.userID = :currentUserID OR friends.friendID = :currentUserID
    )";
    $params[':currentUserID'] = $currentUserID;
}

if (!empty($_GET['category'])) {
    $where[] = "posts.category = :category";
    $params[':category'] = $_GET['category'];
}

if (!empty($_GET['date_from'])) {
    $where[] = "posts.created_at >= :date_from";
    $params[':date_from'] = $_GET['date_from'] . " 00:00:00";
}
if (!empty($_GET['date_to'])) {
    $where[] = "posts.created_at <= :date_to";
    $params[':date_to'] = $_GET['date_to'] . " 23:59:59";
}

if (!empty($_GET['q'])) {
    $where[] = "(posts.name LIKE :q OR posts.description LIKE :q)";
    $params[':q'] = "%" . $_GET['q'] . "%";
}

$sql = "SELECT posts.*, users.firstName, users.lastName FROM posts JOIN users ON posts.userID = users.userID";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY posts.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);


$commentsStmt = $conn->prepare("SELECT comments.*, users.firstName, users.lastName FROM comments JOIN users ON comments.userID = users.userID");
$commentsStmt->execute();
$allComments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

$commentsMap = [];
foreach ($allComments as $c) {
    $commentsMap[$c['postID']][] = $c;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>🚀 tusahunter - Платформа мероприятий</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="web3-style.css">
</head>

<body>
  <header class="header">
    <div class="container">
      <nav class="nav">
                <div class="nav-brand">
            <i class="fas fa-rocket"></i> tusahunter
        </div>
        <ul class="nav-links">
          <li><a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Лента</a></li>
          <li><a href="create.php" class="nav-link"><i class="fas fa-plus"></i> Создать</a></li>
          <li><a href="friends.php" class="nav-link"><i class="fas fa-users"></i> Друзья</a></li>
          <li><a href="acc.php" class="nav-link"><i class="fas fa-user"></i> Аккаунт</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="container main-content">
    <form method="get" class="filter-form animate-fade-in">
      <div class="filter-field">
        <label class="form-label">Статус:</label>
        <select name="status" class="form-select">
          <option value="">-- Любой --</option>
          <option value="going">✅ Иду</option>
          <option value="not_going">❌ Не иду</option>
        </select>
      </div>

      <div class="filter-field">
        <label class="form-label">Категория:</label>
        <select name="category" class="form-select">
          <option value="">-- Любая --</option>
          <option value="music">🎵 Музыка</option>
          <option value="education">📚 Образование</option>
          <option value="sport">⚽ Спорт</option>
        </select>
      </div>

      <div class="filter-field">
        <label class="form-label">Дата с:</label>
        <input type="date" name="date_from" class="form-input">
      </div>

      <div class="filter-field">
        <label class="form-label">Дата по:</label>
        <input type="date" name="date_to" class="form-input">
      </div>

      <div class="filter-field">
        <label class="form-label">Поиск:</label>
        <input type="text" name="q" placeholder="Название или описание" class="form-input">
      </div>

      <div class="filter-field">
        <label class="form-label filter-checkbox">
          <input type="checkbox" name="friends_only" value="1">
          <span>Только друзья</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i> Фильтровать
      </button>
    </form>

    <div class="text-center mb-4">
      <h1 class="animate-fade-in">🌟 События</h1>
      <p class="animate-fade-in">Найдите интересные мероприятия в вашем городе</p>
    </div>
    <?php if (count($posts) > 0): ?>
      <?php foreach ($posts as $post): ?>
        <div class="post animate-fade-in" id="post-<?= (int)$post['postID'] ?>">
          <div class="post-header">
            <div>
              <h2 class="post-title"><?= htmlspecialchars($post['name']) ?></h2>
              <div class="post-meta">
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($post['firstName'] . ' ' . $post['lastName']) ?></span>
                <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($post['created_at']) ?></span>
                <?php if (!empty($post['event_time'])): ?>
                  <span><i class="fas fa-clock"></i> <?= htmlspecialchars($post['event_time']) ?></span>
                <?php endif; ?>
                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($post['location']) ?></span>
              </div>
            </div>
            <div class="post-category">
              <?php if ($post['category'] === 'music'): ?>
                <span class="badge badge-music">🎵 Музыка</span>
              <?php elseif ($post['category'] === 'education'): ?>
                <span class="badge badge-education">📚 Образование</span>
              <?php elseif ($post['category'] === 'sport'): ?>
                <span class="badge badge-sport">⚽ Спорт</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($post['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="post-image">
          <?php endif; ?>

          <div class="post-content">
            <?= nl2br(htmlspecialchars($post['description'])) ?>
          </div>

          <div class="post-stats">
            <div class="stat-item">
              <i class="fas fa-heart"></i>
              <span><?= (int)$post['likes'] ?> лайков</span>
            </div>
            <div class="stat-item">
              <i class="fas fa-check-circle"></i>
              <span><?= $attendanceMap[$post['postID']]['going'] ?? 0 ?> идут</span>
            </div>
            <div class="stat-item">
              <i class="fas fa-times-circle"></i>
              <span><?= $attendanceMap[$post['postID']]['not_going'] ?? 0 ?> не идут</span>
            </div>
          </div>

          <div class="post-actions">
            <form method="post" class="form-inline">
              <input type="hidden" name="like_post_id" value="<?= (int)$post['postID'] ?>">
              <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fas fa-heart"></i> Лайк
              </button>
            </form>

            <form method="post" class="form-inline">
              <input type="hidden" name="attendance_post_id" value="<?= (int)$post['postID'] ?>">
              <input type="hidden" name="attendance_status" value="going">
              <button type="submit" class="btn btn-success btn-sm" <?= (isset($userAttendance[$post['postID']]) && $userAttendance[$post['postID']] === 'going') ? 'disabled' : '' ?>>
                <i class="fas fa-check"></i> Иду
              </button>
            </form>

            <form method="post" class="form-inline">
              <input type="hidden" name="attendance_post_id" value="<?= (int)$post['postID'] ?>">
              <input type="hidden" name="attendance_status" value="not_going">
              <button type="submit" class="btn btn-secondary btn-sm" <?= (isset($userAttendance[$post['postID']]) && $userAttendance[$post['postID']] === 'not_going') ? 'disabled' : '' ?>>
                <i class="fas fa-times"></i> Не иду
              </button>
            </form>
          </div>

          <?php if (!empty($commentsMap[$post['postID']])): ?>
            <div class="comments-section">
              <h4><i class="fas fa-comments"></i> Комментарии:</h4>
              <?php foreach ($commentsMap[$post['postID']] as $c): ?>
                <div class="comment">
                  <div class="comment-author">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($c['firstName'] . ' ' . $c['lastName']) ?>
                  </div>
                  <div class="comment-text">
                    <?= htmlspecialchars($c['comment']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post" class="comment-form">
            <input type="hidden" name="comment_post_id" value="<?= (int)$post['postID'] ?>">
            <textarea name="comment_text" placeholder="Добавьте комментарий..." rows="3" class="form-textarea comment-input"></textarea>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane"></i> Отправить
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="text-center p-4">
        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
        <p style="color: var(--text-muted); font-size: 1.2rem;">Пока нет событий</p>
      </div>
    <?php endif; ?>
  </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.post-actions form').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const button = this.querySelector('button');
          const originalText = button.innerHTML;
          
          button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
          button.disabled = true;
          
          const formData = new FormData(this);
          
          fetch('index.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;
            
            const postId = this.closest('.post').id;
            const updatedPost = tempDiv.querySelector(`#${postId}`);
            
            if (updatedPost) {
              const currentStats = this.closest('.post').querySelector('.post-stats');
              const newStats = updatedPost.querySelector('.post-stats');
              if (newStats) {
                currentStats.innerHTML = newStats.innerHTML;
                
                currentStats.style.transform = 'scale(1.05)';
                currentStats.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                  currentStats.style.transform = 'scale(1)';
                }, 200);
              }
              
              const currentActions = this.closest('.post-actions');
              const newActions = updatedPost.querySelector('.post-actions');
              if (newActions) {
                currentActions.innerHTML = newActions.innerHTML;
                
                setupPostActions(currentActions);
              }
            }
          })
          .catch(error => {
            console.error('Ошибка:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            
            showNotification('Произошла ошибка. Попробуйте еще раз.', 'error');
          });
        });
      });
      
      function setupPostActions(container) {
        container.querySelectorAll('form').forEach(form => {
                  form.addEventListener('submit', function(e) {
          e.preventDefault();
          
          const button = this.querySelector('button');
          const originalText = button.innerHTML;
          
          button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
          button.disabled = true;
          
          const formData = new FormData(this);
          
          fetch('index.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;
            
            const postId = this.closest('.post').id;
            const updatedPost = tempDiv.querySelector(`#${postId}`);
            
            if (updatedPost) {
              const currentStats = this.closest('.post').querySelector('.post-stats');
              const newStats = updatedPost.querySelector('.post-stats');
              if (newStats) {
                currentStats.innerHTML = newStats.innerHTML;
                
                currentStats.style.transform = 'scale(1.05)';
                currentStats.style.transition = 'transform 0.2s ease';
                setTimeout(() => {
                  currentStats.style.transform = 'scale(1)';
                }, 200);
              }
              
              const currentActions = this.closest('.post-actions');
              const newActions = updatedPost.querySelector('.post-actions');
              if (newActions) {
                currentActions.innerHTML = newActions.innerHTML;
                setupPostActions(currentActions);
              }
            }
          })
          .catch(error => {
            console.error('Ошибка:', error);
            button.innerHTML = originalText;
            button.disabled = false;
            showNotification('Произошла ошибка. Попробуйте еще раз.', 'error');
          });
        });
        });
      }
      
      function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
          <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
          ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
          notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
          notification.classList.remove('show');
          setTimeout(() => {
            document.body.removeChild(notification);
          }, 300);
        }, 3000);
      }
    });
  </script>
</body>
</html>
