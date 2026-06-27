<?php
header('Content-Type: text/html; charset=UTF-8');

$db_user = 'u82291';
$db_pass = '7595792';
$db_name = 'u82291';

try {
    $db = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCredentials() {
    $login = 'user_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < 8; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return ['login' => $login, 'password' => $password];
}

function validateField($value, $pattern, $required = true) {
    if ($required && empty($value)) return false;
    if (!empty($value) && !preg_match($pattern, $value)) return false;
    return true;
}

function getFieldName($field) {
    $names = [
        'fio' => 'ФИО', 'phone' => 'Телефон', 'email' => 'E-mail',
        'birth_date' => 'Дата рождения', 'gender' => 'Пол',
        'languages' => 'Языки', 'biography' => 'Биография',
        'contract' => 'Согласие с контрактом'
    ];
    return $names[$field] ?? $field;
}

$validLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

// ===== GET запрос =====
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];
    $isAuthorized = false;
    $currentUserId = null;

    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        $messages[] = '<div class="success-message">✅ Спасибо, данные успешно сохранены!</div>';

        if (!empty($_COOKIE['generated_login']) && !empty($_COOKIE['generated_pass'])) {
            $messages[] = sprintf(
                '<div class="success-message credentials">
                🔐 <strong>Ваши данные для входа:</strong><br>
                Логин: <strong>%s</strong><br>
                Пароль: <strong>%s</strong><br>
                <em>Запишите их! Они показываются только один раз.</em><br>
                <a href="login.php">Войти и отредактировать данные</a>
                </div>',
                htmlspecialchars($_COOKIE['generated_login']),
                htmlspecialchars($_COOKIE['generated_pass'])
            );
            setcookie('generated_login', '', time() - 3600);
            setcookie('generated_pass', '', time() - 3600);
        }
    }

    if (!empty($_SESSION['user_id'])) {
        $isAuthorized = true;
        $currentUserId = $_SESSION['user_id'];
        $messages[] = '<div class="success-message auth-info">👋 Вы вошли как: ' . htmlspecialchars($_SESSION['login']) . ' | <a href="logout.php">Выйти</a></div>';

        $stmt = $db->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$currentUserId]);
        $userData = $stmt->fetch();

        if ($userData) {
            $values['fio'] = $userData['full_name'];
            $values['phone'] = $userData['phone'];
            $values['email'] = $userData['email'];
            $values['birth_date'] = $userData['birth_date'];
            $values['gender'] = $userData['gender'];
            $values['biography'] = $userData['biography'];
            $values['contract'] = $userData['contract_agreed'] ? '1' : '';

            $langStmt = $db->prepare("SELECT pl.name FROM application_languages al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.application_id = ?");
            $langStmt->execute([$userData['id']]);
            $values['languages'] = $langStmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    if (!$isAuthorized) {
        $fields = ['fio', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract'];
        foreach ($fields as $field) {
            $errors[$field] = !empty($_COOKIE[$field . '_error']);
            if ($errors[$field]) {
                $errorMsg = $_COOKIE[$field . '_error_msg'] ?? '';
                $messages[] = '<div class="error-item">❌ ' . htmlspecialchars(getFieldName($field)) . ': ' . htmlspecialchars($errorMsg) . '</div>';
                setcookie($field . '_error', '', time() - 3600);
                setcookie($field . '_error_msg', '', time() - 3600);
            }
            $values[$field] = $_COOKIE[$field . '_value'] ?? '';
        }

        $errors['languages'] = !empty($_COOKIE['languages_error']);
        if ($errors['languages']) {
            $messages[] = '<div class="error-item">❌ ' . htmlspecialchars($_COOKIE['languages_error_msg'] ?? '') . '</div>';
            setcookie('languages_error', '', time() - 3600);
            setcookie('languages_error_msg', '', time() - 3600);
        }
        $langsValue = $_COOKIE['languages_value'] ?? '';
        $values['languages'] = empty($langsValue) ? [] : explode(',', $langsValue);
    }

    include('form.php');
    exit();
}

// ===== POST запрос =====
else {
    $hasErrors = false;
    $isAuthorized = !empty($_SESSION['user_id']);
    $currentUserId = $_SESSION['user_id'] ?? null;

    $fio = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $languages = $_POST['languages'] ?? [];
    $biography = trim($_POST['biography'] ?? '');
    $contract = isset($_POST['contract']) && $_POST['contract'] == '1' ? '1' : '';

    // 1. ФИО
    if (!validateField($fio, '/^[а-яА-ЯёЁa-zA-Z\s\-]{2,150}$/u', true)) {
        setcookie('fio_error', '1', time() + 86400);
        setcookie('fio_error_msg', 'Только буквы, пробелы и дефисы (2-150 символов)', time() + 86400);
        $hasErrors = true;
    }
    setcookie('fio_value', $fio, time() + 365 * 86400);

    // 2. Телефон
    if (!validateField($phone, '/^[\+\d\s\(\)\-]{10,20}$/', true)) {
        setcookie('phone_error', '1', time() + 86400);
        setcookie('phone_error_msg', 'Формат: +7 (123) 456-78-90 (10-20 символов)', time() + 86400);
        $hasErrors = true;
    }
    setcookie('phone_value', $phone, time() + 365 * 86400);

    // 3. Email
    if (!validateField($email, '/^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/', true)) {
        setcookie('email_error', '1', time() + 86400);
        setcookie('email_error_msg', 'Неверный формат email', time() + 86400);
        $hasErrors = true;
    }
    setcookie('email_value', $email, time() + 365 * 86400);

    // 4. Дата рождения
    if (!validateField($birth_date, '/^\d{4}-\d{2}-\d{2}$/', true)) {
        setcookie('birth_date_error', '1', time() + 86400);
        setcookie('birth_date_error_msg', 'Формат: ГГГГ-ММ-ДД', time() + 86400);
        $hasErrors = true;
    } elseif (!empty($birth_date)) {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date) {
            setcookie('birth_date_error', '1', time() + 86400);
            setcookie('birth_date_error_msg', 'Неверная дата', time() + 86400);
            $hasErrors = true;
        } else {
            $age = date_diff($date, new DateTime())->y;
            if ($age < 18 || $age > 120) {
                setcookie('birth_date_error', '1', time() + 86400);
                setcookie('birth_date_error_msg', 'Возраст от 18 до 120 лет', time() + 86400);
                $hasErrors = true;
            }
        }
    }
    setcookie('birth_date_value', $birth_date, time() + 365 * 86400);

    // 5. Пол
    if (!validateField($gender, '/^(male|female)$/', true)) {
        setcookie('gender_error', '1', time() + 86400);
        setcookie('gender_error_msg', 'Выберите пол', time() + 86400);
        $hasErrors = true;
    }
    setcookie('gender_value', $gender, time() + 365 * 86400);

    // 6. Языки
    if (empty($languages)) {
        setcookie('languages_error', '1', time() + 86400);
        setcookie('languages_error_msg', 'Выберите хотя бы один язык', time() + 86400);
        $hasErrors = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $validLanguages)) {
                setcookie('languages_error', '1', time() + 86400);
                setcookie('languages_error_msg', 'Недопустимый язык', time() + 86400);
                $hasErrors = true;
                break;
            }
        }
    }
    setcookie('languages_value', implode(',', $languages), time() + 365 * 86400);

    // 7. Биография
    if (strlen($biography) > 5000) {
        setcookie('biography_error', '1', time() + 86400);
        setcookie('biography_error_msg', 'Максимум 5000 символов', time() + 86400);
        $hasErrors = true;
    }
    setcookie('biography_value', $biography, time() + 365 * 86400);

    // 8. Контракт
    if (empty($contract)) {
        setcookie('contract_error', '1', time() + 86400);
        setcookie('contract_error_msg', 'Необходимо согласие', time() + 86400);
        $hasErrors = true;
    }
    setcookie('contract_value', $contract, time() + 365 * 86400);

    if ($hasErrors) {
        header('Location: index.php');
        exit();
    }

    // Удаляем куки с ошибками
    $errorCookies = ['fio', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'contract'];
    foreach ($errorCookies as $cookie) {
        setcookie($cookie . '_error', '', time() - 3600);
        setcookie($cookie . '_error_msg', '', time() - 3600);
    }

    try {
        $db->beginTransaction();

        if ($isAuthorized && $currentUserId) {
            // Обновление
            $stmt = $db->prepare("SELECT id FROM applications WHERE user_id=? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$currentUserId]);
            $app = $stmt->fetch();
            $app_id = $app['id'];

            $stmt = $db->prepare("UPDATE applications SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_agreed=? WHERE id=?");
            $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, (int)$contract, $app_id]);

            $db->prepare("DELETE FROM application_languages WHERE application_id=?")->execute([$app_id]);
        } else {
            // Новая регистрация
            $credentials = generateCredentials();
            $login = $credentials['login'];
            $passwordHash = password_hash($credentials['password'], PASSWORD_DEFAULT);

            $stmt = $db->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $passwordHash]);
            $userId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_agreed, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fio, $phone, $email, $birth_date, $gender, $biography, (int)$contract, $userId]);
            $app_id = $db->lastInsertId();

            setcookie('generated_login', $login, time() + 60);
            setcookie('generated_pass', $credentials['password'], time() + 60);
        }

        // Вставка языков
        $langStmt = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $insertLang = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $langName) {
            $langStmt->execute([$langName]);
            $lang = $langStmt->fetch();
            if ($lang) {
                $insertLang->execute([$app_id, $lang['id']]);
            }
        }

        $db->commit();
        setcookie('save', '1', time() + 30);
        header('Location: index.php');
        exit();
    } catch(PDOException $e) {
        $db->rollBack();
        die("Ошибка БД: " . $e->getMessage());
    }
}
?>