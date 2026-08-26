<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function isLeader(): bool {
    return ($_SESSION['role'] ?? '') === 'leader';
}


function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'pages/login.php');
        exit;
    }
}

function requireLeader(): void {
    requireLogin();
    if (!isLeader()) {
        http_response_code(403);
        exit('The crypt does not know you.');
    }
}


function generateAnonHandle(PDO $pdo): string {
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE anon_handle = :handle');

    do {
        $handle = 'anonymous_' . random_int(1000, 9999);
        $stmt->execute(['handle' => $handle]);
        $taken = $stmt->fetch();
    } while ($taken);

    return $handle;
}


function registerUser(PDO $pdo, string $email, string $password, string $confirm): array {
    $email = trim($email);

    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'All fields are required.', 'user_id' => null];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'That email address is not valid.', 'user_id' => null];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.', 'user_id' => null];
    }
    if ($password !== $confirm) {
        return ['ok' => false, 'error' => 'Passwords do not match.', 'user_id' => null];
    }

    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'An account already exists for that email.', 'user_id' => null];
    }

    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $handle = generateAnonHandle($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, anon_handle, is_anonymous, trust_index, role)
         VALUES (:email, :hash, :handle, TRUE, 0, "user")'
    );
    $stmt->execute([
        'email'  => $email,
        'hash'   => $hash,
        'handle' => $handle,
    ]);

    return ['ok' => true, 'error' => null, 'user_id' => (int)$pdo->lastInsertId()];
}



function loginUser(PDO $pdo, string $email, string $password): array {
    if (trim($email) === '' || $password === '') {
        return ['ok' => false, 'error' => 'Enter your email and password.'];
    }

    $stmt = $pdo->prepare(
        'SELECT user_id, password_hash, role FROM users WHERE email = :email'
    );
    $stmt->execute(['email' => trim($email)]);
    $user = $stmt->fetch();


    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Those credentials were not accepted.'];
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['role']    = $user['role'];

    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE user_id = :id')
        ->execute(['id' => $user['user_id']]);

    return ['ok' => true, 'error' => null];
}


function logoutUser(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}


function currentUser(PDO $pdo): ?array {
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare(
        'SELECT user_id, email, role, anon_handle, is_anonymous,
                animal_username, avatar_id, custom_avatar, custom_avatar_status,
                trust_index, created_at
         FROM users WHERE user_id = :id'
    );
    $stmt->execute(['id' => currentUserId()]);
    return $stmt->fetch() ?: null;
}



function displayName(array $user): string {
    if ($user['is_anonymous'] || empty($user['animal_username'])) {
        return $user['anon_handle'];
    }
    return $user['animal_username'];
}




function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(?string $token): bool {
    return !empty($_SESSION['csrf'])
        && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}