<?php

function notify(PDO $pdo, int $userId, string $type, ?int $postId = null): void {
    $pdo->prepare(
        'INSERT INTO notifications (user_id, type, post_id) VALUES (:uid, :type, :pid)'
    )->execute(['uid' => $userId, 'type' => $type, 'pid' => $postId]);
}

function unreadNotificationCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute(['uid' => $userId]);
    return (int)$stmt->fetchColumn();
}
