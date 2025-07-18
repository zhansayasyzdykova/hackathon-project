<?php
$servername = "localhost";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$servername;dbname=db_users", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (isset($_SESSION["auth"]) && $_SESSION["auth"] === true) {
    header("Location: index.php");
    exit;
}

$msg = "";

if (!empty($_POST['email']) && !empty($_POST['password'])) {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT userID, password FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if (password_verify($pass, $result["password"])) {
            $_SESSION["auth"] = true;
            $_SESSION["userID"] = $result["userID"];
            header("Location: index.php");
            exit;
        } else {
            $msg = "Неверный пароль";
        }
    } else {
        $msg = "No such user, please sign up";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 tusahunter - Вход</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="web3-style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .auth-card {
            background: var(--bg-card);
            padding: var(--spacing-2xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-light);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .auth-brand h1 {
            font-size: 2rem;
            margin-bottom: var(--spacing-sm);
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-brand p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .error-message {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .auth-links {
            text-align: center;
            margin-top: var(--spacing-xl);
        }

        .auth-links a {
            color: var(--text-secondary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            transition: all 0.3s ease;
        }

        .auth-links a:hover {
            background: var(--bg-elevated);
            color: var(--text-primary);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: var(--spacing-lg) 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-light);
        }

        .divider span {
            padding: 0 var(--spacing-md);
            color: var(--text-muted);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card animate-fade-in">
            <div class="auth-brand">
                <h1><i class="fas fa-rocket"></i> tusahunter</h1>
                <p>Войдите в мир интересных мероприятий</p>
            </div>

            <form method="post">
                <?php if ($msg): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                                    <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Эл. почта
                </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Введите ваш email" 
                           class="form-input"
                           required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Пароль
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Введите ваш пароль" 
                           class="form-input"
                           required>
                </div>

                <button type="submit" class="btn btn-primary w-full btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Войти
                </button>
            </form>

            <div class="divider">
                <span>или</span>
            </div>

            <div class="auth-links">
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Создать аккаунт
                </a>
            </div>
        </div>
    </div>
</body>
</html>
