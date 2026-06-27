<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета разработчика - Задание 5</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input[type="text"], input[type="tel"], input[type="email"],
        input[type="date"], select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .error-field { border: 2px solid #c33 !important; background-color: #fee; }
        .radio-group label { display: inline; font-weight: normal; margin-right: 15px; }
        .radio-group input { margin-right: 5px; }
        select[multiple] { height: 150px; }
        textarea { resize: vertical; min-height: 100px; }
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { margin-right: 10px; width: auto; }
        .checkbox-group label { margin: 0; font-weight: normal; }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        .success-message {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #2e7d32;
        }
        .credentials {
            background: #fff3cd !important;
            border-left-color: #ffc107 !important;
            color: #856404 !important;
            text-align: center;
            font-size: 16px;
        }
        .credentials a { color: #667eea; font-weight: bold; }
        .auth-info {
            background: #e3f2fd !important;
            border-left-color: #2196f3 !important;
            color: #1565c0 !important;
        }
        .auth-info a { color: #c33; }
        .error-item {
            background: #fee;
            border-left: 4px solid #c33;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            color: #c33;
        }
        .hint { font-size: 12px; color: #666; margin-top: 5px; }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .login-link a { color: #667eea; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Анкета разработчика</h1>
        <p class="subtitle">Задание 5 - Авторизация и редактирование данных</p>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <?= $msg ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!$isAuthorized): ?>
            <div class="login-link">
                🔐 Уже регистрировались? <a href="login.php">Войти для редактирования</a>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST" style="margin-top: 20px;">
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="fio" value="<?= htmlspecialchars($values['fio'] ?? '') ?>" class="<?= !empty($errors['fio']) ? 'error-field' : '' ?>">
                <div class="hint">Только буквы, пробелы и дефисы. От 2 до 150 символов.</div>
            </div>

            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($values['phone'] ?? '') ?>" class="<?= !empty($errors['phone']) ? 'error-field' : '' ?>">
                <div class="hint">Формат: +7 (123) 456-78-90 (10-20 символов)</div>
            </div>

            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>" class="<?= !empty($errors['email']) ? 'error-field' : '' ?>">
                <div class="hint">Пример: user@domain.com</div>
            </div>

            <div class="form-group">
                <label>Дата рождения</label>
                <input type="date" name="birth_date" value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>" class="<?= !empty($errors['birth_date']) ? 'error-field' : '' ?>">
                <div class="hint">Возраст должен быть от 18 до 120 лет</div>
            </div>

            <div class="form-group radio-group">
                <label>Пол</label>
                <label>
                    <input type="radio" name="gender" value="male" <?= (($values['gender'] ?? '') == 'male') ? 'checked' : '' ?> class="<?= !empty($errors['gender']) ? 'error-field' : '' ?>">
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" <?= (($values['gender'] ?? '') == 'female') ? 'checked' : '' ?> class="<?= !empty($errors['gender']) ? 'error-field' : '' ?>">
                    Женский
                </label>
            </div>

            <div class="form-group">
                <label>Любимые языки программирования</label>
                <select name="languages[]" multiple class="<?= !empty($errors['languages']) ? 'error-field' : '' ?>">
                    <?php foreach ($validLanguages as $lang): ?>
                        <option value="<?= $lang ?>" <?= in_array($lang, $values['languages'] ?? []) ? 'selected' : '' ?>><?= $lang ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Удерживайте Ctrl (Cmd на Mac) для множественного выбора. Выберите хотя бы один язык.</div>
            </div>

            <div class="form-group">
                <label>Биография</label>
                <textarea name="biography" class="<?= !empty($errors['biography']) ? 'error-field' : '' ?>"><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
                <div class="hint">Необязательно. Максимум 5000 символов.</div>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" name="contract" value="1" <?= (($values['contract'] ?? '') == '1') ? 'checked' : '' ?> class="<?= !empty($errors['contract']) ? 'error-field' : '' ?>">
                <label>Я ознакомлен(а) с контрактом и согласен(на) с условиями</label>
            </div>

            <button type="submit"><?= $isAuthorized ? '💾 Обновить данные' : '💾 Сохранить' ?></button>
        </form>
    </div>
</body>
</html>