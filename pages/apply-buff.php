<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

//timed buffs show the buffed post in the home pafe woht pulsating stuf 
//instant one time immedatiely apply effect
//instant dont show buff
$user = currentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !checkCsrf($_POST['csrf'] ?? null)) {
    header('Location: analytics.php');
    exit;
}

$postId  = (int)($_POST['post_id'] ?? 0);
$tarotId = (int)($_POST['tarot_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT active_buff_id, buff_expires_at, title FROM posts
     WHERE post_id = :pid AND author_id = :uid AND status = "approved"'
);
$stmt->execute(['pid' => $postId, 'uid' => $user['user_id']]);
$post = $stmt->fetch();

if ($post) {
    $alreadyBuffed = $post['active_buff_id'] !== null
        && $post['buff_expires_at'] !== null
        && strtotime($post['buff_expires_at']) > time();

    if ($alreadyBuffed) {
        $_SESSION['flash_buff_error'] = 'That confession is already buffed.';
    } else {
        $deduct = $pdo->prepare(
            'UPDATE award_collection SET quantity = quantity - 1
             WHERE user_id = :uid AND tarot_id = :tid AND quantity > 0'
        );
        $deduct->execute(['uid' => $user['user_id'], 'tid' => $tarotId]);

        if ($deduct->rowCount() === 1) {
            $card = $pdo->prepare('SELECT tarot_name, buff_duration FROM tarot_card_buffs WHERE tarot_id = :tid');
            $card->execute(['tid' => $tarotId]);
            $cardData = $card->fetch();

            if ($cardData['buff_duration'] === null) {
                $pdo->prepare('UPDATE posts SET active_buff_id = :tid, buff_expires_at = NOW() WHERE post_id = :pid')
                    ->execute(['tid' => $tarotId, 'pid' => $postId]);
            } else {
                $pdo->prepare(
                    'UPDATE posts SET active_buff_id = :tid,
                     buff_expires_at = DATE_ADD(NOW(), INTERVAL :dur MINUTE)
                     WHERE post_id = :pid'
                )->execute(['tid' => $tarotId, 'dur' => $cardData['buff_duration'], 'pid' => $postId]);
            }

            $_SESSION['flash_buff_applied'] = $cardData['tarot_name'];
            $_SESSION['flash_buff_title']   = $post['title'];
        } else {
            $_SESSION['flash_buff_error'] = "You don't hold that card.";
        }
    }
}

header('Location: analytics.php?tab=buffs');
exit;
