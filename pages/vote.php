<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tarot.php';
require_once __DIR__ . '/../includes/notifications.php';

requireLogin();

$user = currentUser($pdo);

header('Content-Type: application/json');

const PIECE_DROP_CHANCE = 100;
const TRUST_PER_TRUE_VOTE = 50;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['vote_post_id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad request.']);
    exit;
}

if (!checkCsrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Your session expired. Refresh and try again.']);
    exit;
}

$postId = (int)$_POST['vote_post_id'];
$isTrue = $_POST['vote_value'] === 'true' ? 1 : 0;

$stmt = $pdo->prepare(
    //switching votes true false false true
    // no duplicates
    'INSERT INTO truth_voting (post_id, voter_id, is_true)
     VALUES (:post_id, :voter_id, :is_true)
     ON DUPLICATE KEY UPDATE is_true = :is_true2'
);
$stmt->execute([
    'post_id'  => $postId,
    'voter_id' => $user['user_id'],
    'is_true'  => $isTrue,
    'is_true2' => $isTrue,
]);

$isNewVote = $stmt->rowCount() === 1;

$author = $pdo->prepare('SELECT author_id FROM posts WHERE post_id = :pid');
$author->execute(['pid' => $postId]);
$authorId = (int)$author->fetchColumn();

if ($isTrue) {
    $before = $pdo->prepare('SELECT trust_index, is_anonymous FROM users WHERE user_id = :uid');
    $before->execute(['uid' => $authorId]);
    $authorRow   = $before->fetch();
    $trustBefore = (int)$authorRow['trust_index'];

    $pdo->prepare('UPDATE users SET trust_index = trust_index + :amount WHERE user_id = :uid')
        ->execute(['amount' => TRUST_PER_TRUE_VOTE, 'uid' => $authorId]);

    $trustAfter = $trustBefore + TRUST_PER_TRUE_VOTE;

    //notification for animal avatr thingy only comes at 50
    //ths checks previous and current trsut

    if ($authorRow['is_anonymous'] && $trustBefore < TRUST_THRESHOLD && $trustAfter >= TRUST_THRESHOLD) {
        notify($pdo, $authorId, 'trust_threshold');
    }

    if ($isNewVote && $authorId !== (int)$user['user_id']) {
        notify($pdo, $authorId, 'vote_received', $postId);
    }
}

$gotFragment = false;

if ($isNewVote) {
    if (random_int(1, 100) <= PIECE_DROP_CHANCE) {
        grantFragment($pdo, $user['user_id']);
        $gotFragment = true;
    }
//this stacks with drop chance - cuirrently too much
//if real site drop chance shoiuld be 25%, it is 100% currently

    $tollStmt = $pdo->prepare(
        "SELECT 1 FROM posts p
         JOIN tarot_card_buffs t ON t.tarot_id = p.active_buff_id
         WHERE p.post_id = :post_id
           AND p.buff_expires_at > NOW()
           AND t.effect_type = 'voter_reward'"
    );
    $tollStmt->execute(['post_id' => $postId]);

    if ($tollStmt->fetchColumn()) {
        grantFragment($pdo, $user['user_id']);
        $gotFragment = true;
    }

    if ($gotFragment) {
        notify($pdo, $user['user_id'], 'fragment_gained', $postId);
    }
}

$stmt = $pdo->prepare(
    "SELECT
        COUNT(DISTINCT tv.vote_id) AS total_votes,
        SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count,
        MAX(CASE WHEN tv.voter_id = :uid THEN tv.is_true END) AS my_vote
     FROM truth_voting tv
     WHERE tv.post_id = :post_id"
);
$stmt->execute(['uid' => $user['user_id'], 'post_id' => $postId]);
$counts = $stmt->fetch();

$total = (int)$counts['total_votes'];
$trueC = (int)$counts['true_count'];

echo json_encode([
    'ok'              => true,
    'total_votes'     => $total,
    'true_count'      => $trueC,
    'pct'             => $total > 0 ? round(($trueC / $total) * 100) : null,
    'my_vote'         => $counts['my_vote'] === null ? null : ((int)$counts['my_vote'] === 1 ? 'true' : 'false'),
    'gained_fragment' => $gotFragment,
]);
