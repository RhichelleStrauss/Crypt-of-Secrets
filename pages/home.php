<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser($pdo);

const PIECE_DROP_CHANCE = 100; 
const TRUST_PER_TRUE_VOTE = 50;


function tryAssembleCard(PDO $pdo, int $userId, int $tarotId): bool {
    $stmt = $pdo->prepare(
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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_post_id'])) {
    if (checkCsrf($_POST['csrf'] ?? null)) {
        $postId = (int)$_POST['vote_post_id'];
        $isTrue = $_POST['vote_value'] === 'true' ? 1 : 0;

        $stmt = $pdo->prepare(
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

        if ($isTrue) {
            $pdo->prepare(
                'UPDATE users u
                 JOIN posts p ON p.author_id = u.user_id
                 SET u.trust_index = u.trust_index + :amount
                 WHERE p.post_id = :post_id'
            )->execute(['amount' => TRUST_PER_TRUE_VOTE, 'post_id' => $postId]);
        }

        if ($isNewVote && random_int(1, 100) <= PIECE_DROP_CHANCE) {
            $tarotId = $pdo->query('SELECT tarot_id FROM tarot_card_buffs ORDER BY RAND() LIMIT 1')->fetchColumn();

            $pdo->prepare(
                'INSERT INTO user_tarot_pieces (user_id, tarot_id, piece_number)
                 VALUES (:uid, :tid, :piece)'
            )->execute([
                'uid'   => $user['user_id'],
                'tid'   => $tarotId,
                'piece' => random_int(1, 4),
            ]);

            if (tryAssembleCard($pdo, $user['user_id'], $tarotId)) {
                $_SESSION['flash_assembled'] = $tarotId;
            } else {
                $_SESSION['flash_piece'] = true;
            }
        }
    }
   
    $back = 'home.php?sort=' . urlencode($_POST['sort'] ?? 'new')
          . '&filter=' . urlencode($_POST['filter'] ?? 'all');
    header('Location: ' . $back);
    exit;
}


$flashAssembledId = $_SESSION['flash_assembled'] ?? null;
$flashPiece       = $_SESSION['flash_piece'] ?? false;
$flashAwarded     = $_SESSION['flash_awarded'] ?? null;
$flashAwardError  = $_SESSION['flash_award_error'] ?? null;
unset($_SESSION['flash_assembled'], $_SESSION['flash_piece'], $_SESSION['flash_awarded'], $_SESSION['flash_award_error']);

$assembledCardName = null;
if ($flashAssembledId) {
    $stmt = $pdo->prepare('SELECT tarot_name FROM tarot_card_buffs WHERE tarot_id = :id');
    $stmt->execute(['id' => $flashAssembledId]);
    $assembledCardName = $stmt->fetchColumn();
}

$submitted = isset($_GET['submitted']);



$sortOptions = [
    'new'    => ['label' => 'Newest',        'sql' => 'p.created_at DESC'],
    'top'    => ['label' => 'Most believed', 'sql' => 'score DESC, p.created_at DESC'],
    'votes'  => ['label' => 'Most judged',   'sql' => 'total_votes DESC, p.created_at DESC'],
    'awards' => ['label' => 'Most awarded',  'sql' => 'award_count DESC, p.created_at DESC'],
    'old'    => ['label' => 'Oldest',        'sql' => 'p.created_at ASC'],
];

$filterOptions = [
    'all'      => ['label' => 'All confessions',      'sql' => ''],
    'true'     => ['label' => 'Believed true',        'sql' => 'HAVING true_count > (total_votes - true_count)'],
    'false'    => ['label' => 'Believed false',       'sql' => 'HAVING total_votes > 0 AND true_count < (total_votes - true_count)'],
    'unjudged' => ['label' => 'Unjudged',             'sql' => 'HAVING total_votes = 0'],
    'mine'     => ['label' => 'Awaiting your verdict', 'sql' => 'HAVING my_vote IS NULL'],
];

$sort   = array_key_exists($_GET['sort']   ?? '', $sortOptions)   ? $_GET['sort']   : 'new';
$filter = array_key_exists($_GET['filter'] ?? '', $filterOptions) ? $_GET['filter'] : 'all';

$orderBy = $sortOptions[$sort]['sql'];
$having  = $filterOptions[$filter]['sql'];

$stmt = $pdo->prepare(
    "SELECT
        p.post_id, p.title, p.content, p.created_at, p.posted_anonymously,
        p.active_buff_id, p.buff_expires_at, t.tarot_name AS buff_name,
        u.user_id AS author_user_id, u.anon_handle, u.animal_username, u.is_anonymous,
        u.custom_avatar, u.custom_avatar_status, aa.filename AS avatar_filename,
        COUNT(DISTINCT tv.vote_id) AS total_votes,
        SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count,
        SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE -1 END) AS score,
        (SELECT COUNT(*) FROM post_awards pa WHERE pa.post_id = p.post_id) AS award_count,
        MAX(CASE WHEN tv.voter_id = :uid THEN tv.is_true END) AS my_vote
     FROM posts p
     JOIN users u ON u.user_id = p.author_id
     LEFT JOIN truth_voting tv ON tv.post_id = p.post_id
     LEFT JOIN animal_avatars aa ON aa.avatar_id = u.avatar_id
     LEFT JOIN tarot_card_buffs t ON t.tarot_id = p.active_buff_id AND p.buff_expires_at > NOW()
     WHERE p.status = 'approved'
     GROUP BY p.post_id
     $having
     ORDER BY $orderBy
     LIMIT 30"
);
$stmt->execute(['uid' => $user['user_id']]);
$posts = $stmt->fetchAll();


$stmt = $pdo->prepare(
    'SELECT t.tarot_id, t.tarot_name, ac.quantity
     FROM award_collection ac
     JOIN tarot_card_buffs t ON t.tarot_id = ac.tarot_id
     WHERE ac.user_id = :uid AND ac.quantity > 0
     ORDER BY t.tarot_name'
);
$stmt->execute(['uid' => $user['user_id']]);
$heldCards = $stmt->fetchAll();

function viewUrl(string $sort, string $filter): string {
    return 'home.php?sort=' . urlencode($sort) . '&filter=' . urlencode($filter);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return floor($diff / 2592000) . 'mo ago';
}

function postAuthorName(array $post): string {
    if ($post['posted_anonymously'] || $post['is_anonymous'] || empty($post['animal_username'])) {
        return $post['anon_handle'];
    }
    return $post['animal_username'];
}

function postAuthorAvatar(array $post): string {
   
    if ($post['posted_anonymously'] || $post['is_anonymous']) {
        return BASE_URL . 'assets/images/icons/profileDummy.png';
    }
    if ($post['custom_avatar_status'] === 'approved' && !empty($post['custom_avatar'])) {
        return BASE_URL . 'uploads/avatars/' . $post['custom_avatar'];
    }
    if (!empty($post['avatar_filename'])) {
        return BASE_URL . 'assets/images/animals/' . $post['avatar_filename'];
    }
    return BASE_URL . 'assets/images/icons/profileDummy.png';
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }

        .glow-wrap { cursor: pointer; }
        .glow-wrap .glow-item {
            transition: filter 0.3s ease, transform 0.3s ease;
        }
        .glow-wrap:hover .glow-item {
            filter: drop-shadow(0 0 12px rgb(255, 28, 37));
            transform: scale(1.08) translateY(-2px);
        }
        .glow-wrap:hover span:not(.glow-item) { color: #E11C25; }

        .rough-border { filter: url(#rough-border); }

        .vote-active { color: #E11C25 !important; }

        .buff-pulse-border {
            border: 2px solid #0D366E;
            animation: buff-border-pulse 2.2s ease-in-out infinite;
        }
        @keyframes buff-border-pulse {
            0%, 100% { box-shadow: 0 0 6px rgba(13, 54, 110, 0.35); border-color: rgba(13, 54, 110, 0.5); }
            50%      { box-shadow: 0 0 22px rgba(13, 54, 110, 0.8); border-color: rgba(13, 54, 110, 1); }
        }
        .buff-pulse {
            animation: buff-pulse 1.6s ease-in-out infinite;
        }
        @keyframes buff-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.4; transform: scale(1.4); }
        }

        .menu > summary {
            list-style: none;
            cursor: pointer;
        }
        .menu > summary::-webkit-details-marker { display: none; }
        .menu[open] > summary { color: #E11C25; }
    </style>
</head>

<body class="relative w-full min-h-screen bg-[#121110] text-[#e4d5b7] overflow-x-hidden">

    <div id="ferrofluid-container" class="fixed inset-0 w-full h-full z-0"></div>

    <svg class="absolute w-0 h-0 overflow-hidden" xmlns="http://www.w3.org/2000/svg">
        <filter id="rough-border" color-interpolation-filters="sRGB">
            <feTurbulence type="fractalNoise" baseFrequency="0.4" numOctaves="3" result="noise" />
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="4" xChannelSelector="R" yChannelSelector="G" />
        </filter>
    </svg>

    <?php include ROOT_PATH . 'components/sidenav.php'; ?>

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-8 py-6">

        <div class="w-full max-w-4xl mx-auto flex flex-col gap-3">

            <header class="flex justify-between items-center border-b border-[#FAEAC9] pb-3 mb-8">

                <div class="flex items-center gap-5 text-[#FAEAC9] uppercase text-2xl tracking-wide">

                    <details class="menu relative">
                        <summary class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">FILTER</summary>
                        <div class="absolute left-0 top-full mt-2 z-40 w-60 bg-[#1c1a18] border border-[#7A0A0A] rounded-lg py-2 shadow-xl">
                            <?php foreach ($filterOptions as $key => $opt): ?>
                            <a href="<?= viewUrl($sort, $key) ?>"
                               class="block px-4 py-2 font-['Fira_Sans'] text-sm normal-case tracking-normal transition-colors <?= $filter === $key ? 'text-[#E11C25]' : 'text-[#e4d5b7] hover:text-[#FAEAC9] hover:bg-[#7A0A0A]/20' ?>">
                                <?= htmlspecialchars($opt['label']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="menu relative">
                        <summary class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">SORT</summary>
                        <div class="absolute left-0 top-full mt-2 z-40 w-60 bg-[#1c1a18] border border-[#7A0A0A] rounded-lg py-2 shadow-xl">
                            <?php foreach ($sortOptions as $key => $opt): ?>
                            <a href="<?= viewUrl($key, $filter) ?>"
                               class="block px-4 py-2 font-['Fira_Sans'] text-sm normal-case tracking-normal transition-colors <?= $sort === $key ? 'text-[#E11C25]' : 'text-[#e4d5b7] hover:text-[#FAEAC9] hover:bg-[#7A0A0A]/20' ?>">
                                <?= htmlspecialchars($opt['label']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </details>

                </div>

                <div class="flex items-center gap-5">
                    <a href="create-post.php"
                       class="glow-wrap w-16 h-16 flex items-center justify-center text-[#121110] text-xl font-black">
                        <img src="<?= BASE_URL ?>assets/images/icons/CryptPlusIcon.png" alt="add post" class="glow-item w-full h-full object-cover">
                    </a>
                    <a href="profile.php" class="glow-wrap flex flex-col items-center text-[16px]">
                        <div class="glow-item w-12 h-12 rounded-full border border-red-900 overflow-hidden mb-1">
                            <img src="<?= BASE_URL ?>assets/images/icons/CryptProfileIcon.png" alt="Profile" class="w-full h-full object-cover">
                        </div>
                    </a>
                </div>
            </header>

            <?php if ($assembledCardName): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/20 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#FAEAC9]">
                    A card completes itself in your hand — <span class="text-[#E11C25]"><?= htmlspecialchars($assembledCardName) ?></span> is yours.
                </p>
            </div>
            <?php elseif ($flashPiece): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/10 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#FAEAC9]">A fragment falls into your keeping.</p>
            </div>
            <?php endif; ?>

            <?php if ($flashAwarded): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/20 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#FAEAC9]">
                    You bestow <span class="text-[#E11C25]"><?= htmlspecialchars($flashAwarded) ?></span> upon the confession.
                </p>
            </div>
            <?php elseif ($flashAwardError): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/10 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($flashAwardError) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($sort !== 'new' || $filter !== 'all'): ?>
            <div class="flex items-center gap-3 font-['Fira_Sans'] text-sm text-[#9b9186] mb-1">
                <span><?= htmlspecialchars($filterOptions[$filter]['label']) ?>, sorted by <?= strtolower($sortOptions[$sort]['label']) ?></span>
                <a href="home.php" class="text-[#E11C25] hover:text-[#FAEAC9] transition-colors">clear</a>
            </div>
            <?php endif; ?>

            <?php if ($submitted): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#FAEAC9]">Your confession has been sent to the leader for review.</p>
            </div>
            <?php endif; ?>

            <?php if (empty($posts)): ?>
            <div class="relative p-8 text-center">
                <div class="absolute inset-0 border-[4px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-sm text-[#9b9186]">
                    <?= $filter === 'all' ? 'No confessions yet. Be the first.' : 'Nothing here matches that.' ?>
                </p>
            </div>
            <?php endif; ?>

            <?php foreach ($posts as $post):
                $total  = (int)$post['total_votes'];
                $trueC  = (int)$post['true_count'];
                $pct    = $total > 0 ? round(($trueC / $total) * 100) : null;
                $myVote = $post['my_vote'];
                $isBuffed = !empty($post['buff_name']);
            ?>
            <article class="flex flex-col gap-3 border-b border-[#2a2622] pb-6 mb-2 px-5 -mx-5 pt-4 rounded-xl hover:bg-[#1c1a18]/50 transition-colors duration-200 <?= $isBuffed ? 'buff-pulse-border pb-5' : '' ?>">

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full border border-red-900 overflow-hidden bg-[#1c1a18] p-1">
                        <img src="<?= htmlspecialchars(postAuthorAvatar($post)) ?>" alt="" class="w-full h-full object-contain">
                    </div>
                    <span class="text-xl tracking-wider"><?= htmlspecialchars(postAuthorName($post)) ?></span>
                    <span class="w-2 h-2 rounded-full bg-[#72685F] shrink-0"></span>
                    <span class="font-['Fira_Sans'] text-sm text-[#72685F]"><?= timeAgo($post['created_at']) ?></span>
                </div>

                <?php if ($isBuffed): ?>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0D366E] buff-pulse"></span>
                    <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#0D366E]">
                        Blessed by <?= htmlspecialchars($post['buff_name']) ?>
                    </span>
                </div>
                <?php endif; ?>

                <h2 class="uppercase text-xl tracking-widest"><?= htmlspecialchars($post['title']) ?></h2>

                <div class="relative w-full min-h-[160px] flex items-center px-6 py-5">
                    <div class="absolute inset-0 bg-[#121110] opacity-80 border-[4px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <p class="relative z-10 text-[#e4d5b7] font-['Fira_Sans'] text-lg leading-relaxed"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex gap-6 text-[#E11C25] font-['Fira_Sans'] font-medium text-m">

                        <form method="POST" action="home.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="vote_post_id" value="<?= $post['post_id'] ?>">
                            <input type="hidden" name="vote_value" value="true">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="glow-wrap flex flex-col items-center gap-1 transition-colors <?= $myVote === '1' ? 'vote-active' : '' ?>">
                                <span class="icon-swap w-10 h-10 glow-item">
                                    <img src="<?= BASE_URL ?>assets/images/icons/CryptTrueIcon.png" alt="">
                                </span>
                                <span class="transition-colors">True</span>
                            </button>
                        </form>

                        <form method="POST" action="home.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="vote_post_id" value="<?= $post['post_id'] ?>">
                            <input type="hidden" name="vote_value" value="false">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="glow-wrap flex flex-col items-center gap-1 transition-colors <?= $myVote === '0' ? 'vote-active' : '' ?>">
                                <span class="icon-swap w-10 h-10 glow-item">
                                    <img src="<?= BASE_URL ?>assets/images/icons/CryptFalseIcon.png" alt="">
                                </span>
                                <span class="transition-colors">False</span>
                            </button>
                        </form>

                        <?php if (!empty($heldCards)): ?>
                        <details class="menu relative">
                            <summary class="glow-wrap flex flex-col items-center gap-1 transition-colors list-none">
                                <span class="icon-swap w-10 h-10 glow-item">
                                    <img src="<?= BASE_URL ?>assets/images/icons/CryptTarotIcon.png" alt="">
                                </span>
                                <span class="transition-colors">Award</span>
                            </summary>
                            <div class="absolute left-0 top-full mt-2 z-40 w-64 bg-[#1c1a18] border border-[#7A0A0A] rounded-lg py-2 shadow-xl">
                                <?php foreach ($heldCards as $held): ?>
                                <form method="POST" action="give-award.php">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                    <input type="hidden" name="tarot_id" value="<?= $held['tarot_id'] ?>">
                                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 font-['Fira_Sans'] text-sm text-[#e4d5b7] hover:text-[#FAEAC9] hover:bg-[#7A0A0A]/20 transition-colors flex justify-between items-center">
                                        <span><?= htmlspecialchars($held['tarot_name']) ?></span>
                                        <span class="text-[#9b9186] text-xs">×<?= (int)$held['quantity'] ?></span>
                                    </button>
                                </form>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        <?php else: ?>
                        <button type="button" disabled class="flex flex-col items-center gap-1 opacity-30 cursor-not-allowed">
                            <span class="icon-swap w-10 h-10">
                                <img src="<?= BASE_URL ?>assets/images/icons/CryptTarotIcon.png" alt="">
                            </span>
                            <span>Award</span>
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center gap-4 font-['Fira_Sans'] text-sm">
                        <?php if ((int)$post['award_count'] > 0): ?>
                        <span class="text-[#7A0A0A]"><?= (int)$post['award_count'] ?> awarded</span>
                        <?php endif; ?>
                        <span class="text-[#FAEAC9]"><?= $pct !== null ? $pct . '% true · ' . $total . ' votes' : 'No votes yet' ?></span>
                    </div>
                </div>

            </article>
            <?php endforeach; ?>

        </div>
    </main>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>