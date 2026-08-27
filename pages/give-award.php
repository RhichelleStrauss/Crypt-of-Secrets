<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !checkCsrf($_POST['csrf'] ?? null)) {
    header('Location: home.php');
    exit;
}

$postId  = (int)($_POST['post_id'] ?? 0);
$tarotId = (int)($_POST['tarot_id'] ?? 0);


$stmt = $pdo->prepare('SELECT post_id FROM posts WHERE post_id = :pid AND status = "approved"');
$stmt->execute(['pid' => $postId]);

if ($stmt->fetch()) {
   
    $stmt = $pdo->prepare(
        'UPDATE award_collection
         SET quantity = quantity - 1
         WHERE user_id = :uid AND tarot_id = :tid AND quantity > 0'
    );
    $stmt->execute(['uid' => $user['user_id'], 'tid' => $tarotId]);

    if ($stmt->rowCount() === 1) {
        $pdo->prepare(
            'INSERT INTO post_awards (post_id, giver_id, tarot_id)
             VALUES (:pid, :gid, :tid)'
        )->execute(['pid' => $postId, 'gid' => $user['user_id'], 'tid' => $tarotId]);

        $card = $pdo->prepare('SELECT tarot_name, buff_duration FROM tarot_card_buffs WHERE tarot_id = :tid');
        $card->execute(['tid' => $tarotId]);
        $cardData = $card->fetch();

        if ($cardData['buff_duration'] === null) {
          
            $pdo->prepare(
                'UPDATE posts SET active_buff_id = :tid, buff_expires_at = NOW()
                 WHERE post_id = :pid'
            )->execute(['tid' => $tarotId, 'pid' => $postId]);
        } else {
            $pdo->prepare(
                'UPDATE posts
                 SET active_buff_id = :tid,
                     buff_expires_at = DATE_ADD(NOW(), INTERVAL :dur MINUTE)
                 WHERE post_id = :pid'
            )->execute(['tid' => $tarotId, 'dur' => $cardData['buff_duration'], 'pid' => $postId]);
        }

       
        $author = $pdo->prepare('SELECT author_id FROM posts WHERE post_id = :pid');
        $author->execute(['pid' => $postId]);
        $authorId = $author->fetchColumn();

        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, post_id)
             VALUES (:uid, "award_received", :pid)'
        )->execute(['uid' => $authorId, 'pid' => $postId]);

        $_SESSION['flash_awarded'] = $cardData['tarot_name'];
    } else {
        $_SESSION['flash_award_error'] = "You don't hold that card.";
    }
}

$back = 'home.php?sort=' . urlencode($_POST['sort'] ?? 'new')
      . '&filter=' . urlencode($_POST['filter'] ?? 'all');
header('Location: ' . $back);
exit;