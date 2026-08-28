<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tarot.php';

requireLogin();

$user = currentUser($pdo);

$flashAwarded    = $_SESSION['flash_awarded'] ?? null;
$flashAwardError = $_SESSION['flash_award_error'] ?? null;
unset($_SESSION['flash_awarded'], $_SESSION['flash_award_error']);

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
        u.custom_avatar, u.custom_avatar_status, COALESCE(aa.display_filename, aa.filename) AS avatar_filename,
        COUNT(DISTINCT tv.vote_id) AS total_votes,
        SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count,
        SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE -1 END) AS score,
        (SELECT COUNT(*) FROM post_awards pa WHERE pa.post_id = p.post_id) AS award_count,
        (SELECT COUNT(*) FROM post_views pv WHERE pv.post_id = p.post_id) AS view_count,
        (t.effect_type = 'pin_position') AS is_pinned,
        MAX(CASE WHEN tv.voter_id = :uid THEN tv.is_true END) AS my_vote
     FROM posts p
     JOIN users u ON u.user_id = p.author_id
     LEFT JOIN truth_voting tv ON tv.post_id = p.post_id
     LEFT JOIN animal_avatars aa ON aa.avatar_id = u.avatar_id
     LEFT JOIN tarot_card_buffs t ON t.tarot_id = p.active_buff_id AND p.buff_expires_at > NOW()
     WHERE p.status = 'approved'
     GROUP BY p.post_id
     $having
     ORDER BY is_pinned DESC, $orderBy
     LIMIT 30"
);
$stmt->execute(['uid' => $user['user_id']]);
$posts = $stmt->fetchAll();

if ($posts) {
    $viewRows   = [];
    $viewParams = [];
    foreach ($posts as $p) {
        $viewRows[]   = '(?, ?)';
        $viewParams[] = $p['post_id'];
        $viewParams[] = $user['user_id'];
    }
    $pdo->prepare(
        'INSERT IGNORE INTO post_views (post_id, viewer_id) VALUES ' . implode(', ', $viewRows)
    )->execute($viewParams);
}

$stmt = $pdo->prepare('SELECT 1 FROM user_tarot_pieces WHERE user_id = :uid LIMIT 1');
$stmt->execute(['uid' => $user['user_id']]);
$hasFragmentsToGive = (bool)$stmt->fetchColumn();

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

//llooks at if anonymous
//when someone gets name it showd their usrname no matter how old
//to see if someone is trusty
function postAuthorName(array $post): string {
    if ($post['is_anonymous'] || empty($post['animal_username'])) {
        return $post['anon_handle'];
    }
    return $post['animal_username'];
}

function postAuthorAvatar(array $post): string {
    if ($post['is_anonymous']) {
        return BASE_URL . 'assets/images/animals/CryptDefaultLambIcon.png';
    }
    if ($post['custom_avatar_status'] === 'approved' && !empty($post['custom_avatar'])) {
        return BASE_URL . 'uploads/avatars/' . $post['custom_avatar'];
    }
    if (!empty($post['avatar_filename'])) {
        return BASE_URL . 'assets/images/animals/' . $post['avatar_filename'];
    }
    return BASE_URL . 'assets/images/animals/CryptDefaultLambIcon.png';
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
            filter: drop-shadow(0 0 8px #E11C25) drop-shadow(0 0 30px #7A0A0A);
            transform: scale(1.08) translateY(-2px);
        }
        .glow-wrap:hover span:not(.glow-item) { color: #E11C25; }

        .rough-border { filter: url(#rough-border); }

        .vote-active { color: #E11C25 !important; }

        .buff-pulse-border {
            border: 2px solid #4d4d4d;
            animation: buff-border-pulse 2.2s ease-in-out infinite;
        }
        @keyframes buff-border-pulse {
            0%, 100% { box-shadow: 0 0 6px rgba(77, 77, 77, 0.35); border-color: rgba(77, 77, 77, 0.5); }
            50%      { box-shadow: 0 0 22px rgba(77, 77, 77, 0.8); border-color: rgba(77, 77, 77, 1); }
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

        @media (max-width: 767px) {
            .menu-panel {
                left: auto;
                right: 0;
            }
        }

        .dropdown-link {
            position: relative;
        }
        @media (max-width: 767px) {
            .feed-header { justify-content: flex-end; }
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

        <div class="w-full max-w-4xl mx-auto flex flex-col gap-3">

           <header class="feed-header flex flex-wrap gap-3 justify-between items-center border-b border-[#FAEAC9] px-3 sm:px-5 sm:-mx-5 pb-3 mb-8">

                <div class="flex items-center gap-4 sm:gap-5 text-[#FAEAC9] uppercase text-lg sm:text-xl md:text-2xl tracking-wide">

                    <details class="menu relative" id="filterMenu">
                        <summary class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">FILTER</summary>
                        <div class="menu-panel absolute left-0 top-full mt-2 z-40 w-60">
                            <div class="absolute inset-0 bg-[#4d4d4d]/60 backdrop-blur-sm border-[3px] border-[#7A0A0A] rounded-lg rough-border pointer-events-none"></div>
                            <div class="relative z-10 py-2">
                                <?php foreach ($filterOptions as $key => $opt): ?>
                                <a href="<?= viewUrl($sort, $key) ?>"
                                   class="dropdown-link block px-4 py-2 font-['Fira_Sans'] text-sm normal-case tracking-normal transition-colors <?= $filter === $key ? 'text-[#E11C25] font-bold' : 'text-[#e4d5b7] hover:text-[#E11C25]' ?>">
                                    <?= htmlspecialchars($opt['label']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>

                    <details class="menu relative" id="sortMenu">
                        <summary class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">SORT</summary>
                        <div class="menu-panel absolute left-0 top-full mt-2 z-40 w-60">
                            <div class="absolute inset-0 bg-[#4d4d4d]/60 backdrop-blur-sm border-[3px] border-[#7A0A0A] rounded-lg rough-border pointer-events-none"></div>
                            <div class="relative z-10 py-2">
                                <?php foreach ($sortOptions as $key => $opt): ?>
                                <a href="<?= viewUrl($key, $filter) ?>"
                                   class="dropdown-link block px-4 py-2 font-['Fira_Sans'] text-sm normal-case tracking-normal transition-colors <?= $sort === $key ? 'text-[#E11C25] font-bold' : 'text-[#e4d5b7] hover:text-[#E11C25]' ?>">
                                    <?= htmlspecialchars($opt['label']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>

                </div>

                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>

            <?php if ($flashAwarded): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/20 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#FAEAC9]">
                    A fragment of <span class="text-[#E11C25]"><?= htmlspecialchars($flashAwarded) ?></span> slips into their keeping.
                </p>
            </div>
            <?php elseif ($flashAwardError): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/10 px-4 py-3 mb-2">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($flashAwardError) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($sort !== 'new' || $filter !== 'all'): ?>
            <div class="flex items-center gap-3 text-base text-[#72685F] -mt-6 mb-1">
                <span class="uppercase tracking-wide"><?= htmlspecialchars($filterOptions[$filter]['label']) ?>, sorted by <?= strtolower($sortOptions[$sort]['label']) ?></span>
                <a href="home.php" class="uppercase tracking-wide text-[#E11C25] hover:text-[#FAEAC9] transition-colors">clear</a>
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
            <article id="post-<?= $post['post_id'] ?>" class="flex flex-col gap-3 border-b border-[#3a332c] pb-6 mb-2 px-3 sm:px-5 sm:-mx-5 pt-4 rounded-xl hover:bg-[#1c1a18]/50 transition-colors duration-200 <?= $isBuffed ? 'buff-pulse-border pb-5' : '' ?>">

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button type="button" class="profile-peek w-14 h-14 shrink-0 transition-transform duration-200 hover:scale-110"
                                data-user-id="<?= (int)$post['author_user_id'] ?>" title="View profile">
                            <img src="<?= htmlspecialchars(postAuthorAvatar($post)) ?>" alt="" class="w-full h-full object-contain">
                        </button>
                        <span class="text-lg sm:text-xl tracking-wider break-all"><?= htmlspecialchars(postAuthorName($post)) ?></span>
                        <span class="w-2 h-2 rounded-full bg-[#72685F] shrink-0"></span>
                        <span class="font-['Fira_Sans'] text-sm text-[#72685F]"><?= timeAgo($post['created_at']) ?></span>
                    </div>
                </div>

                <?php if ($isBuffed): ?>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#4d4d4d] buff-pulse"></span>
                    <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#4d4d4d]">
                        Buffed by <?= htmlspecialchars($post['buff_name']) ?>
                    </span>
                </div>
                <?php endif; ?>

                <h2 class="uppercase text-lg sm:text-xl tracking-widest"><?= htmlspecialchars($post['title']) ?></h2>

                <div class="relative w-full min-h-[160px] flex items-center px-6 py-5">
                    <div class="absolute inset-0 bg-[#121110] opacity-80 border-[4px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <p class="relative z-10 text-[#e4d5b7] font-['Fira_Sans'] text-base sm:text-lg leading-relaxed"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex gap-4 sm:gap-6 text-[#E11C25] font-['Fira_Sans'] font-medium text-m">

                        <button type="button" class="vote-btn glow-wrap flex flex-col items-center gap-1 transition-colors <?= $myVote === '1' ? 'vote-active' : '' ?>"
                                data-post-id="<?= $post['post_id'] ?>" data-vote="true">
                            <span class="icon-swap w-10 h-10 glow-item">
                                <img src="<?= BASE_URL ?>assets/images/icons/CryptTrueIcon.png" alt="">
                            </span>
                            <span class="transition-colors">True</span>
                        </button>

                        <button type="button" class="vote-btn glow-wrap flex flex-col items-center gap-1 transition-colors <?= $myVote === '0' ? 'vote-active' : '' ?>"
                                data-post-id="<?= $post['post_id'] ?>" data-vote="false">
                            <span class="icon-swap w-10 h-10 glow-item">
                                <img src="<?= BASE_URL ?>assets/images/icons/CryptFalseIcon.png" alt="">
                            </span>
                            <span class="transition-colors">False</span>
                        </button>

                        <?php
                      
                        $isOwnPost = (int)$post['author_user_id'] === (int)$user['user_id'];
                        $canAward  = $hasFragmentsToGive && !$isOwnPost;
                        ?>
                        <?php if ($canAward): ?>
                        <form method="POST" action="give-award.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <button type="submit" class="glow-wrap flex flex-col items-center gap-1 transition-colors">
                                <span class="icon-swap w-10 h-10 glow-item">
                                    <img src="<?= BASE_URL ?>assets/images/icons/CryptTarotIcon.png" alt="">
                                </span>
                                <span class="transition-colors">Award</span>
                            </button>
                        </form>
                        <?php else: ?>
                        <button type="button" disabled title="<?= $isOwnPost ? "You can't award your own post" : 'No fragments to give' ?>" class="flex flex-col items-center gap-1 opacity-30 cursor-not-allowed">
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
                        <div class="flex flex-col items-end gap-0.5">
                            <span class="vote-stats text-[#FAEAC9]"><?= $pct !== null ? $pct . '% true · ' . $total . ' votes' : 'No votes yet' ?></span>
                            <span class="text-[#72685F]">
                                <?= (int)$post['view_count'] ?> <?= (int)$post['view_count'] === 1 ? 'view' : 'views' ?>
                            </span>
                        </div>
                    </div>
                </div>

            </article>
            <?php endforeach; ?>

        </div>
    </main>

         Reddit-hovercard style. The transparent backdrop only exists to catch
         the click that dismisses it. -->
    <div id="profilePeekBackdrop" class="hidden fixed inset-0 z-[55]"></div>
    <div id="profilePeek" class="hidden fixed z-[60] w-[320px] rounded-2xl overflow-hidden shadow-2xl"
         style="background-image: url('<?= BASE_URL ?>assets/images/CryptProfileTarotBg.png'); background-size: 100% 100%; background-repeat: no-repeat;">
        <div class="px-10 pt-10 pb-8 flex flex-col items-center text-center gap-4">

            <div class="flex flex-col items-center gap-2">
                <img id="peekAvatar" src="" alt="" class="w-20 h-20 object-contain">
                <span id="peekName" class="text-[#7A0A0A] text-lg tracking-widest uppercase leading-tight"></span>
            </div>

            <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full h-auto">

            <div class="grid grid-cols-2 gap-x-6 gap-y-4 w-full">
                <div class="flex flex-col items-center gap-0.5">
                    <span id="peekConfessions" class="text-[#7A0A0A] text-2xl"></span>
                    <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#121110]">Confessions</span>
                </div>
                <div class="flex flex-col items-center gap-0.5">
                    <span id="peekTrust" class="text-[#7A0A0A] text-2xl"></span>
                    <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#121110]">Trust</span>
                </div>
                <div class="flex flex-col items-center gap-0.5">
                    <span id="peekCards" class="text-[#7A0A0A] text-2xl"></span>
                    <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#121110]">Cards</span>
                </div>
                <div class="flex flex-col items-center gap-0.5">
                    <span id="peekTruePct" class="text-[#7A0A0A] text-2xl"></span>
                    <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#121110]">Believed true</span>
                </div>
            </div>

            <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#121110]">
                Member since <span id="peekJoined" class="text-[#7A0A0A]"></span>
            </span>
        </div>
    </div>
    </div>
    </div>
    </div>
    </div>

    

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

    <script>
        (function () {
            var filterMenu = document.getElementById('filterMenu');
            var sortMenu = document.getElementById('sortMenu');
            var navMenus = [filterMenu, sortMenu].filter(Boolean);
            if (!navMenus.length) return;

            navMenus.forEach(function (menu) {
                menu.addEventListener('toggle', function () {
                    if (menu.open) {
                        navMenus.forEach(function (other) {
                            if (other !== menu) other.removeAttribute('open');
                        });
                    }
                });
            });

            document.addEventListener('click', function (e) {
                navMenus.forEach(function (menu) {
                    if (menu.open && !menu.contains(e.target)) {
                        menu.removeAttribute('open');
                    }
                });
            });
        })();
    </script>

    <script>
        (function () {
            var CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;

            function bumpNotificationBadge() {
                var bell = document.querySelector('a[href="notifications.php"]');
                if (!bell) return;
                var badge = bell.querySelector('span');
                if (badge) {
                    var current = parseInt(badge.textContent, 10) || 0;
                    badge.textContent = current >= 9 ? '9+' : String(current + 1);
                } else {
                    badge = document.createElement('span');
                    badge.className = "absolute top-2 right-2 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-[#E11C25] text-[#FAEAC9] text-[10px] font-['Fira_Sans'] font-bold leading-none";
                    badge.textContent = '1';
                    bell.appendChild(badge);
                }
            }

            document.querySelectorAll('.vote-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var postId = btn.dataset.postId;
                    var voteValue = btn.dataset.vote;
                    var article = document.getElementById('post-' + postId);
                    if (!article) return;

                    btn.disabled = true;

                    fetch('vote.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            csrf: CSRF_TOKEN,
                            vote_post_id: postId,
                            vote_value: voteValue
                        })
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.ok) return;

                            article.querySelectorAll('.vote-btn').forEach(function (b) {
                                b.classList.toggle('vote-active', b.dataset.vote === data.my_vote);
                            });

                            var stats = article.querySelector('.vote-stats');
                            if (stats) {
                                stats.textContent = data.pct !== null
                                    ? data.pct + '% true · ' + data.total_votes + ' votes'
                                    : 'No votes yet';
                            }

                            if (data.gained_fragment) {
                                bumpNotificationBadge();
                            }
                        })
                        .catch(function () { /* vote just won't update visually */ })
                        .finally(function () { btn.disabled = false; });
                });
            });
        })();
    </script>

    <script>
        (function () {
            var peek = document.getElementById('profilePeek');
            var backdrop = document.getElementById('profilePeekBackdrop');
            if (!peek || !backdrop) return;

            function closePeek() {
                peek.classList.add('hidden');
                backdrop.classList.add('hidden');
            }

            function positionPeek(btn) {
                var r = btn.getBoundingClientRect();
                var w = peek.offsetWidth;
                var h = peek.offsetHeight;
                var gap = 10;

                var left = r.right + gap;
                if (left + w > window.innerWidth - 8) left = r.left - w - gap;
                if (left < 8) left = 8;

                var top = r.top;
                if (top + h > window.innerHeight - 8) top = window.innerHeight - h - 8;
                if (top < 8) top = 8;

                peek.style.left = left + 'px';
                peek.style.top = top + 'px';
            }

            document.querySelectorAll('.profile-peek').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    fetch('profile-card.php?user_id=' + encodeURIComponent(btn.dataset.userId))
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (!data.ok) return;

                            document.getElementById('peekAvatar').src = data.avatar;
                            document.getElementById('peekName').textContent = data.name;
                            document.getElementById('peekConfessions').textContent = data.confessions;
                            document.getElementById('peekTrust').textContent = data.trust;
                            document.getElementById('peekJoined').textContent = data.joined;

                            document.getElementById('peekCards').textContent = data.cards;
                            document.getElementById('peekTruePct').textContent =
                                data.true_pct === null ? '–' : data.true_pct + '%';

                            peek.classList.remove('hidden');
                            backdrop.classList.remove('hidden');
                            positionPeek(btn);
                        })
                        .catch(function () { /* leave the card closed */ });
                });
            });

            backdrop.addEventListener('click', closePeek);
            peek.addEventListener('click', closePeek);
            window.addEventListener('resize', closePeek);
            window.addEventListener('scroll', closePeek, true);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closePeek();
            });

        })();
    </script>

</body>
</html>