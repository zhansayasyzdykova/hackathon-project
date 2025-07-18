<?php
session_start();

if (isset($_SESSION["auth"]) && $_SESSION["auth"] === true) {
    header("Location: index.php");
    exit;
}

include_once 'conn.php';

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $uname = $_POST['uname'];
    $password = $_POST['password'];
    $password_repeat = $_POST['password-repeat'];

    if ($password !== $password_repeat) {
        $msg = "Пароли не совпадают.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param('ss', $email, $uname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $msg = "Эл. почта или имя пользователя уже существует.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, username, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $fname, $lname, $email, $uname, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $msg = "Ошибка регистрации: " . $stmt->error;
            }
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 tusahunter - Регистрация</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="web3-style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            position: relative;
            overflow: hidden;
            padding: var(--spacing-xl) var(--spacing-md);
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><polygon points="50,10 90,90 10,90" fill="rgba(255,255,255,0.05)"/></svg>') repeat;
            background-size: 80px 80px;
            animation: float 25s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        .auth-card {
            background: var(--bg-card);
            padding: var(--spacing-2xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-light);
            width: 100%;
            max-width: 450px;
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
            background: var(--accent-gradient);
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

        .form-row {
            display: flex;
            gap: var(--spacing-md);
        }

        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card animate-fade-in">
            <div class="auth-brand">
                <h1><i class="fas fa-rocket"></i> tusahunter</h1>
                <p>Создайте аккаунт и начните находить мероприятия</p>
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
                                    <label for="uname" class="form-label">
                    <i class="fas fa-at"></i> Имя пользователя
                </label>
                    <input type="text" 
                           id="uname" 
                           name="uname" 
                           placeholder="Выберите имя пользователя" 
                           class="form-input"
                           required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fname" class="form-label">
                            <i class="fas fa-user"></i> Имя
                        </label>
                        <input type="text" 
                               id="fname" 
                               name="fname" 
                               placeholder="Ваше имя" 
                               class="form-input"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="lname" class="form-label">
                            <i class="fas fa-user"></i> Фамилия
                        </label>
                        <input type="text" 
                               id="lname" 
                               name="lname" 
                               placeholder="Ваша фамилия" 
                               class="form-input"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Пароль
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Создайте пароль" 
                           class="form-input"
                           required>
                </div>

                <div class="form-group">
                    <label for="password-repeat" class="form-label">
                        <i class="fas fa-shield-alt"></i> Повторите пароль
                    </label>
                    <input type="password" 
                           id="password-repeat" 
                           name="password-repeat" 
                           placeholder="Повторите пароль" 
                           class="form-input"
                           required>
                </div>

                <button type="submit" class="btn btn-accent w-full btn-lg">
                    <i class="fas fa-user-plus"></i> Создать аккаунт
                </button>
            </form>

            <div class="divider">
                <span>или</span>
            </div>

            <div class="auth-links">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Уже есть аккаунт? Войти
                </a>
            </div>
        </div>
    </div>
</body>
</html>
