<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tarot.php';

requireLogin();

$user = currentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !checkCsrf($_POST['csrf'] ?? null)) {
    header('Location: home.php');
    exit;
}

$postId = (int)($_POST['post_id'] ?? 0);


$stmt = $pdo->prepare('SELECT author_id FROM posts WHERE post_id = :pid AND status = "approved"');
$stmt->execute(['pid' => $postId]);
$authorId = $stmt->fetchColumn();

if ($authorId && (int)$authorId !== (int)$user['user_id']) {
    $result = giftRandomFragment($pdo, $user['user_id'], (int)$authorId);

    if ($result === null) {
        $_SESSION['flash_award_error'] = "You have no fragments to give.";
    } else {
        $pdo->prepare(
            'INSERT INTO post_awards (post_id, giver_id, tarot_id)
             VALUES (:pid, :gid, :tid)'
        )->execute(['pid' => $postId, 'gid' => $user['user_id'], 'tid' => $result['tarot_id']]);

        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, post_id)
             VALUES (:uid, "award_received", :pid)'
        )->execute(['uid' => $authorId, 'pid' => $postId]);

        $card = $pdo->prepare('SELECT tarot_name FROM tarot_card_buffs WHERE tarot_id = :tid');
        $card->execute(['tid' => $result['tarot_id']]);

        $_SESSION['flash_awarded'] = $card->fetchColumn();
    }
}

$back = 'home.php?sort=' . urlencode($_POST['sort'] ?? 'new')
      . '&filter=' . urlencode($_POST['filter'] ?? 'all');
header('Location: ' . $back);
exit;
