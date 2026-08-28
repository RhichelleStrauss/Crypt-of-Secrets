<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$targetId = (int)($_GET['user_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT u.user_id, u.anon_handle, u.animal_username, u.is_anonymous,
            u.trust_index, u.created_at,
            u.custom_avatar, u.custom_avatar_status,
            COALESCE(aa.display_filename, aa.filename) AS avatar_filename,
            (SELECT COUNT(*) FROM posts p
              WHERE p.author_id = u.user_id AND p.status = "approved") AS post_count
     FROM users u
     LEFT JOIN animal_avatars aa ON aa.avatar_id = u.avatar_id
     WHERE u.user_id = :id'
);
$stmt->execute(['id' => $targetId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(quantity), 0) FROM award_collection WHERE user_id = :id'
);
$stmt->execute(['id' => $targetId]);
$cardsHeld = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS total,
            SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count
     FROM truth_voting tv
     JOIN posts p ON p.post_id = tv.post_id
     WHERE p.author_id = :id AND p.status = "approved"'
);
$stmt->execute(['id' => $targetId]);
$votes = $stmt->fetch();
$totalVotes = (int)$votes['total'];
$truePct = $totalVotes > 0 ? round(((int)$votes['true_count'] / $totalVotes) * 100) : null;

if ($row['is_anonymous'] || empty($row['animal_username'])) {
    $name = $row['anon_handle'];
} else {
    $name = $row['animal_username'];
}

if ($row['is_anonymous']) {
    $avatar = BASE_URL . 'assets/images/animals/CryptDefaultLambIcon.png';
} elseif ($row['custom_avatar_status'] === 'approved' && !empty($row['custom_avatar'])) {
    $avatar = BASE_URL . 'uploads/avatars/' . $row['custom_avatar'];
} elseif (!empty($row['avatar_filename'])) {
    $avatar = BASE_URL . 'assets/images/animals/' . $row['avatar_filename'];
} else {
    $avatar = BASE_URL . 'assets/images/animals/CryptDefaultLambIcon.png';
}

echo json_encode([
    'ok'          => true,
    'name'        => $name,
    'avatar'      => $avatar,
    'trust'       => (int)$row['trust_index'],
    'confessions' => (int)$row['post_count'],
    'joined'      => date('j M Y', strtotime($row['created_at'])),
    'cards'       => $cardsHeld,
    'true_pct'    => $truePct,
]);
