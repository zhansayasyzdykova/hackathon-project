<?php
session_start();


if (!isset($_SESSION["auth"]) || $_SESSION["auth"] !== true) {
    header("Location: login.php");
    exit;
}

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

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $description = htmlspecialchars($_POST['description']);
    $location = htmlspecialchars($_POST['location']);
    $category = htmlspecialchars($_POST['category']);
    $eventDateRaw = $_POST['event_date']; 
    $eventDate = date('Y-m-d H:i:s', strtotime($eventDateRaw));

    $userID = $_SESSION["userID"];

    $image_name = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $tmp_name = $_FILES['image']['tmp_name'];
        $original_name = basename($_FILES['image']['name']);
        $image_name = uniqid() . "_" . $original_name;

        $target = $upload_dir . $image_name;

        if (!move_uploaded_file($tmp_name, $target)) {
            $msg = "Ошибка загрузки изображения.";
        }
    }

    if (empty($msg)) {
       $stmt = $conn->prepare("INSERT INTO posts (userID, name, description, image, location, category, event_date)
        VALUES (:userID, :name, :description, :image, :location, :category, :event_date)");

        $stmt->bindParam(':event_date', $eventDate);

        $stmt->bindParam(':userID', $userID);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image', $image_name);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':category', $category);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $msg = "Ошибка добавления поста.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 tusahunter - Создать событие</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="web3-style.css">
    <style>
        .create-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: var(--spacing-xl) 0;
        }

        .create-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .create-header {
            background: var(--primary-gradient);
            color: white;
            padding: var(--spacing-xl);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .create-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="20" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            background-size: 100px 100px;
            animation: float 30s ease-in-out infinite;
        }

        .create-header h1 {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-sm);
            color: white;
            background: none;
            -webkit-text-fill-color: white;
            position: relative;
            z-index: 1;
        }

        .create-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }

        .create-form {
            padding: var(--spacing-2xl);
        }

        .form-row {
            display: flex;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .form-row .form-group {
            flex: 1;
        }

        .image-upload {
            position: relative;
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-lg);
            padding: var(--spacing-2xl);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: var(--bg-elevated);
        }

        .image-upload:hover {
            border-color: var(--border-medium);
            background: var(--bg-card);
        }

        .image-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .image-upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-md);
        }

        .image-upload-icon {
            font-size: 3rem;
            color: var(--text-muted);
        }

        .success-message {
            background: var(--success-gradient);
            color: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .error-message {
            background: var(--secondary-gradient);
            color: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: var(--spacing-md);
        }

        .category-option {
            display: none;
        }

        .category-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-lg);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-elevated);
        }

        .category-label:hover {
            border-color: var(--border-medium);
            background: var(--bg-card);
        }

        .category-option:checked + .category-label {
            border-color: #667eea;
            background: var(--primary-gradient);
            color: white;
        }

        .category-icon {
            font-size: 2rem;
        }

        .category-text {
            font-size: 0.875rem;
            font-weight: 500;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
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
                    <li><a href="create.php" class="nav-link active"><i class="fas fa-plus"></i> Создать</a></li>
                              <li><a href="friends.php" class="nav-link"><i class="fas fa-users"></i> Друзья</a></li>
          <li><a href="acc.php" class="nav-link"><i class="fas fa-user"></i> Аккаунт</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="create-container main-content">
        <div class="container">
            <div class="create-card animate-fade-in">
                <div class="create-header">
                    <h1><i class="fas fa-magic"></i> Создать событие</h1>
                    <p>Поделитесь своим удивительным событием с миром</p>
                </div>

                <form method="post" enctype="multipart/form-data" class="create-form">
                    <?php if ($msg && strpos($msg, 'успешно') !== false): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php elseif ($msg): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= htmlspecialchars($msg) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name" class="form-label">
                            <i class="fas fa-tag"></i> Название события
                        </label>
                        <input type="text" 
                               id="name"
                               name="name" 
                               placeholder="Введите название вашего события"
                               class="form-input"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i> Описание
                        </label>
                        <textarea id="description"
                                  name="description" 
                                  rows="5" 
                                  placeholder="Расскажите подробнее о вашем событии..."
                                  class="form-textarea"
                                  required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="location" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Локация
                            </label>
                            <input type="text" 
                                   id="location"
                                   name="location" 
                                   placeholder="Где пройдет событие?"
                                   class="form-input"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="event_date" class="form-label">
                                <i class="fas fa-calendar-alt"></i> Дата и время
                            </label>
                            <input type="datetime-local" 
                                   id="event_date"
                                   name="event_date" 
                                   class="form-input"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-folder"></i> Категория
                        </label>
                        <div class="category-grid">
                            <div>
                                <input type="radio" id="music" name="category" value="Music" class="category-option" required>
                                <label for="music" class="category-label">
                                    <div class="category-icon">🎵</div>
                                    <div class="category-text">Music</div>
                                </label>
                            </div>
                            <div>
                                <input type="radio" id="education" name="category" value="Education" class="category-option">
                                <label for="education" class="category-label">
                                    <div class="category-icon">📚</div>
                                    <div class="category-text">Education</div>
                                </label>
                            </div>
                            <div>
                                <input type="radio" id="sports" name="category" value="Sports" class="category-option">
                                <label for="sports" class="category-label">
                                    <div class="category-icon">⚽</div>
                                    <div class="category-text">Sports</div>
                                </label>
                            </div>
                            <div>
                                <input type="radio" id="games" name="category" value="Games" class="category-option">
                                <label for="games" class="category-label">
                                    <div class="category-icon">🎮</div>
                                    <div class="category-text">Games</div>
                                </label>
                            </div>
                            <div>
                                <input type="radio" id="meetup" name="category" value="Meetup" class="category-option">
                                <label for="meetup" class="category-label">
                                    <div class="category-icon">🤝</div>
                                    <div class="category-text">Meetup</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i> Изображение события
                        </label>
                        <div class="image-upload">
                            <input type="file" name="image" accept="image/*">
                            <div class="image-upload-content">
                                <i class="fas fa-cloud-upload-alt image-upload-icon"></i>
                                <div>
                                    <p style="margin: 0; color: var(--text-primary);">Нажмите или перетащите изображение</p>
                                    <p style="margin: 0; color: var(--text-muted); font-size: 0.875rem;">PNG, JPG, JPEG до 5MB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-full btn-lg">
                        <i class="fas fa-rocket"></i> Создать событие
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
