<?php
/**
 * Задание 6: Админ-панель с HTTP-авторизацией
 * - HTTP Basic Auth (1 балл)
 * - Отображение всех данных (1 балл)
 * - Удаление данных (1 балл)
 * - Редактирование данных (2 балла)
 * - Статистика по языкам (1 балл)
 * - Логин и хеш в отдельной таблице (1 балл)
 * - DRY и KISS (1 балл)
 */

// === DRY: Функция подключения к БД ===
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=localhost;dbname=u82291;charset=utf8",
                'u82291',
                '7595792',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Ошибка подключения к БД: " . $e->getMessage());
        }
    }
    return $db;
}

// === DRY: Функция получения языков заявки ===
function getApplicationLanguages($appId, $db) {
    $stmt = $db->prepare("
        SELECT pl.name 
        FROM application_languages al 
        JOIN programming_languages pl ON al.language_id = pl.id 
        WHERE al.application_id = ?
        ORDER BY pl.name
    ");
    $stmt->execute([$appId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// === DRY: Функция удаления заявки ===
function deleteApplication($id, $db) {
    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// === DRY: Функция сохранения заявки ===
function saveApplication($id, $data, $languages, $db) {
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            UPDATE applications 
            SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_agreed=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['full_name'],
            $data['phone'],
            $data['email'],
            $data['birth_date'],
            $data['gender'],
            $data['biography'],
            (int)$data['contract_agreed'],
            $id
        ]);

        $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);

        $langStmt = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $insertLang = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

        foreach ($languages as $lang) {
            $langStmt->execute([$lang]);
            $langData = $langStmt->fetch();
            if ($langData) {
                $insertLang->execute([$id, $langData['id']]);
            }
        }

        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

// === HTTP-авторизация (Basic Auth) ===
function httpAuth($db) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Админ-панель"');
        header('HTTP/1.0 401 Unauthorized');
        echo '<h1>Требуется авторизация</h1><p>Введите логин и пароль администратора.</p>';
        exit;
    }

    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    $stmt = $db->prepare("SELECT id, login, password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        header('WWW-Authenticate: Basic realm="Админ-панель"');
        header('HTTP/1.0 401 Unauthorized');
        echo '<h1>Неверный логин или пароль</h1>';
        exit;
    }

    return $admin;
}

// === Основная логика ===
$db = getDB();
$admin = httpAuth($db);

// Обработка удаления
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    deleteApplication($id, $db);
    header('Location: admin.php');
    exit;
}

// Обработка редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'birth_date' => $_POST['birth_date'] ?? '',
        'gender' => $_POST['gender'] ?? 'male',
        'biography' => trim($_POST['biography'] ?? ''),
        'contract_agreed' => isset($_POST['contract']) ? 1 : 0
    ];
    $languages = $_POST['languages'] ?? [];
    saveApplication($id, $data, $languages, $db);
    header('Location: admin.php');
    exit;
}

// Получение всех заявок
$applications = $db->query("SELECT * FROM applications ORDER BY id DESC")->fetchAll();

// Загрузка языков для каждой заявки
foreach ($applications as &$app) {
    $app['langs'] = getApplicationLanguages($app['id'], $db);
}
unset($app);

// Статистика по языкам (GROUP BY)
$stats = $db->query("
    SELECT pl.name, COUNT(al.application_id) as cnt
    FROM programming_languages pl
    LEFT JOIN application_languages al ON pl.id = al.language_id
    GROUP BY pl.id, pl.name
    ORDER BY cnt DESC
")->fetchAll();

// Данные для редактирования
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    if ($editData) {
        $editData['languages'] = getApplicationLanguages($editId, $db);
    }
}

$allLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Задание 6</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1300px; margin: 0 auto; background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #667eea; margin-bottom: 5px; }
        h2 { color: #333; margin: 25px 0 15px; border-bottom: 2px solid #667eea; padding-bottom: 8px; }
        .admin-info { color: #666; margin-bottom: 20px; }
        .stats-row { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 12px; min-width: 150px; text-align: center; }
        .stat-card h3 { font-size: 32px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background: #667eea; color: white; position: sticky; top: 0; }
        tr:hover { background: #f5f5f5; }
        .lang-badge { background: #e8f4fd; color: #2196F3; padding: 2px 8px; border-radius: 12px; font-size: 11px; display: inline-block; margin: 2px; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 5px; font-size: 12px; display: inline-block; border: none; cursor: pointer; }
        .btn-edit { background: #2196F3; color: white; }
        .btn-delete { background: #f44336; color: white; }
        .btn-save { background: #4CAF50; color: white; padding: 10px 25px; font-size: 14px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-cancel { background: #999; color: white; padding: 10px 25px; font-size: 14px; text-decoration: none; border-radius: 5px; }
        .btn-edit:hover { background: #1976D2; }
        .btn-delete:hover { background: #d32f2f; }
        .edit-form { background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px 0; border: 2px solid #667eea; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .form-group textarea { min-height: 60px; }
        .stat-bar-container { margin: 8px 0; }
        .stat-bar { background: #e0e0e0; border-radius: 10px; height: 28px; overflow: hidden; }
        .stat-fill { background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; text-align: right; padding-right: 10px; color: white; border-radius: 10px; line-height: 28px; font-weight: bold; font-size: 13px; min-width: 30px; }
        .back-link { display: inline-block; margin-top: 20px; color: #667eea; text-decoration: none; font-weight: bold; }
        .back-link:hover { text-decoration: underline; }
        .empty { text-align: center; color: #999; padding: 40px; font-size: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h1>👑 Админ-панель</h1>
    <p class="admin-info">Вы вошли как: <strong><?= htmlspecialchars($admin['login']) ?></strong> (HTTP Basic Auth)</p>

    <!-- Статистика -->
    <div class="stats-row">
        <div class="stat-card">
            <h3><?= count($applications) ?></h3>
            <p>Всего анкет</p>
        </div>
        <div class="stat-card">
            <h3><?= count($allLanguages) ?></h3>
            <p>Языков</p>
        </div>
    </div>

    <!-- Форма редактирования -->
    <?php if ($editData): ?>
    <div class="edit-form">
        <h2>✏️ Редактирование записи #<?= $editData['id'] ?></h2>
        <form method="POST" action="?edit=<?= $editData['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($editData['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($editData['phone']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($editData['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="birth_date" value="<?= $editData['birth_date'] ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Пол</label>
                    <select name="gender">
                        <option value="male" <?= $editData['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= $editData['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Языки (Ctrl для выбора)</label>
                    <select name="languages[]" multiple size="5">
                        <?php foreach ($allLanguages as $lang): ?>
                            <option value="<?= $lang ?>" <?= in_array($lang, $editData['languages']) ? 'selected' : '' ?>><?= $lang ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Биография</label>
                <textarea name="biography" rows="3"><?= htmlspecialchars($editData['biography']) ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label>
                    <input type="checkbox" name="contract" value="1" <?= $editData['contract_agreed'] ? 'checked' : '' ?>>
                    Ознакомлен с контрактом
                </label>
            </div>
            <button type="submit" class="btn-save">💾 Сохранить изменения</button>
            <a href="admin.php" class="btn-cancel" style="margin-left:10px;">Отмена</a>
        </form>
    </div>
    <?php endif; ?>

    <!-- Таблица заявок -->
    <h2>📋 Список всех заявок</h2>
    <?php if (empty($applications)): ?>
        <div class="empty">Нет данных</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Пол</th>
                    <th>Языки</th>
                    <th>Контракт</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?= $app['id'] ?></td>
                        <td><?= htmlspecialchars($app['full_name']) ?></td>
                        <td><?= htmlspecialchars($app['phone']) ?></td>
                        <td><?= htmlspecialchars($app['email']) ?></td>
                        <td><?= $app['birth_date'] ?></td>
                        <td><?= $app['gender'] == 'male' ? 'М' : 'Ж' ?></td>
                        <td>
                            <?php foreach ($app['langs'] as $l): ?>
                                <span class="lang-badge"><?= htmlspecialchars($l) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= $app['contract_agreed'] ? '✅' : '❌' ?></td>
                        <td>
                            <a href="?edit=<?= $app['id'] ?>" class="btn btn-edit">✏️ Ред.</a>
                            <a href="?delete=<?= $app['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить заявку #<?= $app['id'] ?>?')">🗑️ Удал.</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Статистика по языкам -->
    <h2>📊 Статистика по языкам программирования</h2>
    <?php
    $max = 1;
    if (!empty($stats)) {
        $max = max(array_column($stats, 'cnt'));
        if ($max == 0) $max = 1;
    }
    foreach ($stats as $s):
        $pct = ($s['cnt'] / $max) * 100;
    ?>
        <div class="stat-bar-container">
            <strong><?= htmlspecialchars($s['name']) ?></strong> (<?= $s['cnt'] ?> чел.)
            <div class="stat-bar">
                <div class="stat-fill" style="width: <?= max($pct, 5) ?>%"><?= $s['cnt'] ?></div>
            </div>
        </div>
    <?php endforeach; ?>

    <hr style="margin-top:30px;">
    <a href="../webback-5/index.php" class="back-link">← Вернуться к форме регистрации</a>
</div>
</body>
</html>