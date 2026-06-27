<?php
/**
 * Выход из админ-панели (HTTP Basic Auth)
 */

// Отправляем заголовок, который заставляет браузер "забыть" credentials
header('WWW-Authenticate: Basic realm="Админ-панель"');
header('HTTP/1.0 401 Unauthorized');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Выход из админ-панели</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        p { color: #666; margin-bottom: 20px; }
        a {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin: 5px;
        }
        a:hover { background: #5568d3; }
        .hint {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #856404;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚪 Выход из админ-панели</h1>
        <p>Вы успешно вышли из системы администратора.</p>
        <a href="admin.php">Войти снова</a>
        <a href="index.php">К форме регистрации</a>
        <div class="hint">
            <strong>⚠️ Важно:</strong> Если браузер всё ещё показывает админ-панель без запроса пароля — 
            закройте вкладку браузера полностью и откройте <code>admin.php</code> заново.
        </div>
    </div>
</body>
</html>