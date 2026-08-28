<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser($pdo);

$flashBuffApplied = $_SESSION['flash_buff_applied'] ?? null;
$flashBuffTitle   = $_SESSION['flash_buff_title'] ?? null;
$flashBuffError   = $_SESSION['flash_buff_error'] ?? null;
unset($_SESSION['flash_buff_applied'], $_SESSION['flash_buff_title'], $_SESSION['flash_buff_error']);

$activeTab = $_GET['tab'] ?? 'overview';
if (!in_array($activeTab, ['overview', 'buffs'], true)) {
    $activeTab = 'overview';
}

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM truth_voting tv
     JOIN posts p ON tv.post_id = p.post_id
     WHERE p.author_id = :id AND tv.is_true = 1'
);
$stmt->execute(['id' => $user['user_id']]);
$trueVotesReceived = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM user_tarot_pieces WHERE user_id = :id');
$stmt->execute(['id' => $user['user_id']]);
$fragmentsHeld = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM award_collection WHERE user_id = :id');
$stmt->execute(['id' => $user['user_id']]);
$cardsCollected = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM post_views pv
     JOIN posts p ON pv.post_id = p.post_id
     WHERE p.author_id = :id'
);
$stmt->execute(['id' => $user['user_id']]);
$totalViews = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM posts
     WHERE author_id = :id AND active_buff_id IS NOT NULL AND buff_expires_at > NOW()"
);
$stmt->execute(['id' => $user['user_id']]);
$postsBuffed = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE author_id = :id AND status = "approved"');
$stmt->execute(['id' => $user['user_id']]);
$postCount = (int)$stmt->fetchColumn();

$daysAsUser = (int)floor((time() - strtotime($user['created_at'])) / 86400);

if (isLeader()) {
    $trustTarget = max(1, (int)$user['trust_index']);
    $trustLabel  = 'The crypt answers to you';
} elseif ($user['is_anonymous']) {
    $trustTarget = TRUST_THRESHOLD;
    $trustLabel  = 'Toward stepping forward';
} else {
    $stmt = $pdo->prepare('SELECT MIN(min_trust) FROM animal_avatars WHERE min_trust > :trust');
    $stmt->execute(['trust' => (int)$user['trust_index']]);
    $nextTier = $stmt->fetchColumn();

    if ($nextTier !== false && $nextTier !== null) {
        $trustTarget = (int)$nextTier;
        $trustLabel  = 'Toward your next animal';
    } else {
        $trustTarget = max(1, (int)$user['trust_index']);
        $trustLabel  = 'Highest tier reached';
    }
}
$trustCurrent  = (int)$user['trust_index'];
$trustFraction = $trustTarget > 0 ? min(1, $trustCurrent / $trustTarget) : 1;

$buffedFraction = $postCount > 0 ? $postsBuffed / $postCount : 0;

$ringRadius        = 42;
$ringCircumference = 2 * M_PI * $ringRadius;

$rings = [
    [
        'label'    => $trustLabel,
        'sub'      => "$trustCurrent / $trustTarget trust",
        'fraction' => $trustFraction,
    ],
    [
        'label'    => 'Posts currently buffed',
        'sub'      => "$postsBuffed / $postCount",
        'fraction' => $buffedFraction,
    ],
];

$stmt = $pdo->prepare(
    "SELECT DATE(tv.created_at) AS day, COUNT(*) AS c
     FROM truth_voting tv
     JOIN posts p ON tv.post_id = p.post_id
     WHERE p.author_id = :id AND tv.created_at >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(tv.created_at)"
);
$stmt->execute(['id' => $user['user_id']]);
$votesByDay = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $date    = date('Y-m-d', strtotime("-$i days"));
    $trend[] = [
        'label' => date('D', strtotime($date)),
        'count' => (int)($votesByDay[$date] ?? 0),
    ];
}
$trendMax   = max(1, max(array_column($trend, 'count')));
$trendBarPx = 96;

$stmt = $pdo->prepare(
    "SELECT p.post_id, p.title, p.content, p.created_at, p.buff_expires_at, t.tarot_name AS buff_name,
        COUNT(DISTINCT tv.vote_id) AS total_votes,
        SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count,
        (SELECT COUNT(*) FROM post_awards pa WHERE pa.post_id = p.post_id) AS award_count,
        (SELECT COUNT(*) FROM post_views pv WHERE pv.post_id = p.post_id) AS view_count
     FROM posts p
     LEFT JOIN truth_voting tv ON tv.post_id = p.post_id
     LEFT JOIN tarot_card_buffs t ON t.tarot_id = p.active_buff_id AND p.buff_expires_at > NOW()
     WHERE p.author_id = :id AND p.status = 'approved'
     GROUP BY p.post_id
     ORDER BY p.created_at DESC"
);
$stmt->execute(['id' => $user['user_id']]);
$myPosts = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT t.tarot_id, t.tarot_name, ac.quantity
     FROM award_collection ac
     JOIN tarot_card_buffs t ON t.tarot_id = ac.tarot_id
     WHERE ac.user_id = :id AND ac.quantity > 0
     ORDER BY t.tarot_name'
);
$stmt->execute(['id' => $user['user_id']]);
$heldCards = $stmt->fetchAll();

function timeRemaining(string $expiresAt): string {
    $diff = strtotime($expiresAt) - time();
    if ($diff < 3600)  return ceil($diff / 60) . 'm';
    if ($diff < 86400) return ceil($diff / 3600) . 'h';
    return ceil($diff / 86400) . 'd';
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return floor($diff / 2592000) . 'mo ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }
        .tab-active {
            border-bottom: 2px solid #E11C25;
            color: #E11C25;
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

        .post-toggle > summary {
            list-style: none;
            cursor: pointer;
        }
        .post-toggle > summary::-webkit-details-marker { display: none; }
        .post-toggle .chevron {
            transition: transform 0.2s ease;
        }
        .post-toggle[open] .chevron {
            transform: rotate(90deg);
        }
        .summary-body {
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
        }
        .summary-body .collapsed-buff { margin-left: auto; flex-shrink: 0; }
        @media (max-width: 767px) {
            .summary-body {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
            .summary-body .collapsed-buff { margin-left: 0; }
        }
        .post-toggle[open] .collapsed-buff {
            display: none;
        }
        .dropdown-link {
            position: relative;
        }
        .dropdown-link:not(:last-child)::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 4%;
            width: 86%;
            border-bottom: 1px solid rgba(228, 213, 183, 0.25);
        }
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

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-4 sm:px-6 md:px-8 py-6">
        <div class="w-full max-w-4xl mx-auto flex flex-col gap-5 md:gap-6">

            <header class="flex justify-end items-center border-b border-[#FAEAC9] pb-3 mb-6 md:mb-8">
                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>

            <div class="flex flex-col items-center gap-3 mb-2">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h1 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Analytics</h1>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
            </div>

            <div class="w-full border-t border-[#72685F]"></div>

            <nav class="w-full flex gap-6 border-b border-[#72685F] uppercase text-base font-semibold tracking-widest">
                <a href="analytics.php?tab=overview" class="<?= $activeTab === 'overview' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">Overview</a>
                <a href="analytics.php?tab=buffs" class="<?= $activeTab === 'buffs' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">Buffs</a>
            </nav>

            <?php if ($activeTab === 'overview'): ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">

                <?php foreach ($rings as $ring): $ringOffset = $ringCircumference * (1 - $ring['fraction']); ?>
                <div class="relative p-5 flex items-center gap-4">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative w-20 h-20 shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="<?= $ringRadius ?>" fill="none" stroke="#3a332c" stroke-width="8" />
                            <circle cx="50" cy="50" r="<?= $ringRadius ?>" fill="none" stroke="#E11C25" stroke-width="8"
                                    stroke-linecap="round"
                                    stroke-dasharray="<?= $ringCircumference ?>"
                                    stroke-dashoffset="<?= $ringOffset ?>" />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-base text-[#FAEAC9] font-bold"><?= round($ring['fraction'] * 100) ?>%</span>
                        </div>
                    </div>
                    <div class="relative flex flex-col gap-0.5">
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]"><?= htmlspecialchars($ring['label']) ?></span>
                        <span class="font-['Fira_Sans'] text-base text-[#72685F]"><?= htmlspecialchars($ring['sub']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $trueVotesReceived ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">True votes received</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $fragmentsHeld ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">Fragments held</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $cardsCollected ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">Cards collected</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $totalViews ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">Total views</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $postCount ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">Confessions made</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $daysAsUser ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">Days in the crypt</span>
                    </div>
                </div>

            </div>

            <div class="relative p-5">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <div class="relative flex flex-col gap-4">
                    <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#9b9186]">Votes received, last 7 days</span>
                    <div class="flex items-end gap-3" style="height: <?= $trendBarPx ?>px;">
                        <?php foreach ($trend as $day): $barPx = max(4, round($day['count'] / $trendMax * $trendBarPx)); ?>
                        <div class="flex-1 h-full flex flex-col justify-end">
                            <div class="w-full bg-[#E11C25]/70 rounded-t" style="height: <?= $barPx ?>px;" title="<?= $day['count'] ?> votes"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex gap-3">
                        <?php foreach ($trend as $day): ?>
                        <span class="flex-1 text-center font-['Fira_Sans'] text-sm text-[#72685F]"><?= $day['label'] ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php else /* buffs */: ?>

            <?php if ($flashBuffApplied): ?>
            <div class="border border-[#4d4d4d] bg-[#4d4d4d]/10 rounded-xl px-5 py-4">
                <p class="font-['Fira_Sans'] text-base text-[#4d4d4d]">
                    <span class="text-[#E11C25]"><?= htmlspecialchars($flashBuffApplied) ?></span>
                    settles over your confession<?php if ($flashBuffTitle): ?>
                    <span class="text-[#e4d5b7]"><?= htmlspecialchars($flashBuffTitle) ?></span><?php endif; ?>.
                </p>
            </div>
            <?php elseif ($flashBuffError): ?>
            <div class="border border-[#4d4d4d] bg-[#4d4d4d]/10 rounded-xl px-5 py-4">
                <p class="font-['Fira_Sans'] text-base text-[#E11C25]"><?= htmlspecialchars($flashBuffError) ?></p>
            </div>
            <?php endif; ?>

            <p class="font-['Fira_Sans'] text-base text-[#9b9186]">
                Spend a fully assembled card to buff one of your own confessions. Applying a card never affects anyone else's post.
            </p>

            <?php if (empty($myPosts)): ?>
            <div class="relative p-8 text-center">
                <div class="absolute inset-0 border-[4px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-sm text-[#9b9186]">You have no confessions yet.</p>
            </div>
            <?php endif; ?>

            <div class="flex flex-col gap-3">
                <?php foreach ($myPosts as $post):
                    $isBuffed = !empty($post['buff_name']);
                    $total    = (int)$post['total_votes'];
                    $trueC    = (int)$post['true_count'];
                    $pct      = $total > 0 ? round(($trueC / $total) * 100) : null;
                ?>
                <div class="relative p-4">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-3">

                        <details class="post-toggle w-full">
                            <summary class="flex items-start gap-2">
                                <svg class="chevron w-3 h-3 shrink-0 mt-2 text-[#9b9186]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="summary-body flex-1 min-w-0">
                                    <span class="font-['Fira_Sans'] text-lg text-[#e4d5b7]"><?= htmlspecialchars($post['title']) ?></span>
                                    <?php if ($isBuffed): ?>
                                    <span class="collapsed-buff font-['Fira_Sans'] text-base uppercase tracking-widest text-[#4d4d4d]">
                                        Buffed by <?= htmlspecialchars($post['buff_name']) ?> · <?= timeRemaining($post['buff_expires_at']) ?> left
                                    </span>
                                    <?php endif; ?>
                                </span>
                            </summary>

                            <div class="mt-3 flex flex-col gap-3">
                                <?php if ($isBuffed): ?>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[#7A0A0A]"></span>
                                    <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#4d4d4d]">
                                        Buffed by <?= htmlspecialchars($post['buff_name']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <div class="relative w-full min-h-[120px] flex items-center px-6 py-5">
                                    <div class="absolute inset-0 bg-[#121110] opacity-80 border-[4px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                                    <p class="relative z-10 text-[#e4d5b7] font-['Fira_Sans'] text-base leading-relaxed"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                </div>

                                <div class="flex items-center gap-4 font-['Fira_Sans'] text-base text-[#72685F] flex-wrap">
                                    <span><?= timeAgo($post['created_at']) ?></span>
                                    <span><?= (int)$post['view_count'] ?> <?= (int)$post['view_count'] === 1 ? 'view' : 'views' ?></span>
                                    <?php if ((int)$post['award_count'] > 0): ?>
                                    <span class="text-[#7A0A0A]"><?= (int)$post['award_count'] ?> awarded</span>
                                    <?php endif; ?>
                                    <span class="text-[#FAEAC9]"><?= $pct !== null ? $pct . '% true · ' . $total . ' votes' : 'No votes yet' ?></span>
                                </div>
                            </div>
                        </details>

                        <?php if ($isBuffed): ?>
                        <?php /* status shown inside the expanded post instead */ ?>
                        <?php elseif (empty($heldCards)): ?>
                        <span class="font-['Fira_Sans'] text-base text-[#72685F] self-start">No cards to spend</span>
                        <?php else: ?>
                        <details class="menu relative self-start">
                            <summary class="relative flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95">
                                <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-40 h-auto drop-shadow-md">
                                <span class="absolute text-[#121110] text-sm font-semibold tracking-widest uppercase">
                                    Buff
                                </span>
                            </summary>
                            <div class="absolute left-0 top-full mt-2 z-40 w-64">
                                <div class="absolute inset-0 bg-[#4d4d4d]/60 backdrop-blur-sm border-[3px] border-[#7A0A0A] rounded-lg rough-border pointer-events-none"></div>
                                <div class="relative z-10 py-2">
                                    <?php foreach ($heldCards as $held): ?>
                                    <form method="POST" action="apply-buff.php">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                        <input type="hidden" name="tarot_id" value="<?= $held['tarot_id'] ?>">
                                        <button type="submit"
                                                class="dropdown-link w-full text-left px-4 py-2 font-['Fira_Sans'] text-sm text-[#e4d5b7] hover:text-[#E11C25] transition-colors flex justify-between items-center">
                                            <span><?= htmlspecialchars($held['tarot_name']) ?></span>
                                            <span class="text-[#72685F] text-sm">×<?= (int)$held['quantity'] ?></span>
                                        </button>
                                    </form>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </details>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>

        </div>
    </main>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>
