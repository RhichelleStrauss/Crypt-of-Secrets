<?php


//counts eaxct piece number a user hpolds for each card
//exact - 1/4 is 1/4 so if you have 4 of the same 1/4, it no count as card
//when 4 is got fragments go bye and card is made

function tryAssembleCard(PDO $pdo, int $userId, int $tarotId): bool {
    $stmt = $pdo->prepare(
        //checks if the users got all four unique pieces
        'SELECT COUNT(DISTINCT piece_number) FROM user_tarot_pieces
         WHERE user_id = :uid AND tarot_id = :tid'
    );
    $stmt->execute(['uid' => $userId, 'tid' => $tarotId]);

    if ((int)$stmt->fetchColumn() < 4) {
        return false;
    }

    $pdo->prepare(
        'DELETE FROM user_tarot_pieces WHERE user_id = :uid AND tarot_id = :tid'
    )->execute(['uid' => $userId, 'tid' => $tarotId]);

    $pdo->prepare(
        'INSERT INTO award_collection (user_id, tarot_id, quantity)
         VALUES (:uid, :tid, 1)
         ON DUPLICATE KEY UPDATE quantity = quantity + 1'
    )->execute(['uid' => $userId, 'tid' => $tarotId]);

    return true;
}



function grantFragment(PDO $pdo, int $userId): ?int {
    $tarotId = (int)$pdo->query('SELECT tarot_id FROM tarot_card_buffs ORDER BY RAND() LIMIT 1')->fetchColumn();

    $pdo->prepare(
        'INSERT INTO user_tarot_pieces (user_id, tarot_id, piece_number)
         VALUES (:uid, :tid, :piece)'
    )->execute([
        'uid'   => $userId,
        'tid'   => $tarotId,
        'piece' => random_int(1, 4),
    ]);

    return tryAssembleCard($pdo, $userId, $tarotId) ? $tarotId : null;
}


//user gifts, gives a piece from their collection instead of crafting one from like nothing
//ranom fragment
//deletes the piece for user giving aka giver and inserts it to the user
//awarding is a transfer
function giftRandomFragment(PDO $pdo, int $giverId, int $recipientId): ?array {
    $stmt = $pdo->prepare(
        'SELECT piece_id, tarot_id, piece_number FROM user_tarot_pieces
         WHERE user_id = :uid ORDER BY RAND() LIMIT 1'
    );
    $stmt->execute(['uid' => $giverId]);
    $piece = $stmt->fetch();

    if (!$piece) {
        return null;
    }

    $pdo->prepare('DELETE FROM user_tarot_pieces WHERE piece_id = :pid')
        ->execute(['pid' => $piece['piece_id']]);

    $pdo->prepare(
        'INSERT INTO user_tarot_pieces (user_id, tarot_id, piece_number)
         VALUES (:uid, :tid, :piece)'
    )->execute([
        'uid'   => $recipientId,
        'tid'   => $piece['tarot_id'],
        'piece' => $piece['piece_number'],
    ]);

    return [
        'tarot_id'  => (int)$piece['tarot_id'],
        'assembled' => tryAssembleCard($pdo, $recipientId, (int)$piece['tarot_id']),
    ];
}
