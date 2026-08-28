<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser($pdo);
if (!$user) {

 
    logoutUser();
    header('Location: login.php');
    exit;
}

$name = displayName($user);

$avatarSrc = BASE_URL . 'assets/images/animals/CryptDefaultLambIcon.png';
if ($user['is_anonymous']) {
} elseif ($user['custom_avatar_status'] === 'approved' && $user['custom_avatar']) {
    $avatarSrc = BASE_URL . 'uploads/avatars/' . $user['custom_avatar'];
} elseif (!empty($user['avatar_id'])) {
    $stmt = $pdo->prepare('SELECT COALESCE(display_filename, filename) AS icon FROM animal_avatars WHERE avatar_id = :id');
    $stmt->execute(['id' => $user['avatar_id']]);
    if ($row = $stmt->fetch()) {
        $avatarSrc = BASE_URL . 'assets/images/animals/' . $row['icon'];
    }
}

$joinedDate = date('j M Y', strtotime($user['created_at']));

$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS post_count
     FROM posts WHERE author_id = :id AND status = "approved"'
);
$stmt->execute(['id' => $user['user_id']]);
$postCount = (int)($stmt->fetch()['post_count'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS true_votes
     FROM truth_voting tv
     JOIN posts p ON tv.post_id = p.post_id
     WHERE p.author_id = :id AND tv.is_true = 1'
);
$stmt->execute(['id' => $user['user_id']]);
$trueVotes = (int)($stmt->fetch()['true_votes'] ?? 0);

$activeTab = $_GET['tab'] ?? 'overview';
if (!in_array($activeTab, ['overview', 'posts', 'true', 'false'], true)) {
    $activeTab = 'overview';
}

$voteFilter = '';
if ($activeTab === 'true') {
    $voteFilter = 'HAVING true_count > 0 AND true_count = total_votes';
} elseif ($activeTab === 'false') {
    $voteFilter = 'HAVING total_votes > 0 AND true_count = 0';
}

$postOrder = $activeTab === 'overview'
    ? 'true_count DESC, award_count DESC, view_count DESC, p.created_at DESC'
    : 'p.created_at DESC';

$stmt = $pdo->prepare(
    "SELECT p.post_id, p.title, p.content, p.created_at, p.posted_anonymously,
            COUNT(DISTINCT tv.vote_id) AS total_votes,
            SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count,
            COUNT(DISTINCT pa.award_instance_id) AS award_count,
            (SELECT COUNT(*) FROM post_views pv WHERE pv.post_id = p.post_id) AS view_count
     FROM posts p
     LEFT JOIN truth_voting tv ON tv.post_id = p.post_id
     LEFT JOIN post_awards pa  ON pa.post_id = p.post_id
     WHERE p.author_id = :id AND p.status = \"approved\"
     GROUP BY p.post_id
     $voteFilter
     ORDER BY $postOrder
     LIMIT 10"
);
$stmt->execute(['id' => $user['user_id']]);
$posts = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT t.tarot_id, t.tarot_name, t.icon_filename, ac.quantity
     FROM award_collection ac
     JOIN tarot_card_buffs t ON t.tarot_id = ac.tarot_id
     WHERE ac.user_id = :id AND ac.quantity > 0
     ORDER BY t.tarot_id'
);
$stmt->execute(['id' => $user['user_id']]);
$cards = $stmt->fetchAll();

$followerCount = null;
$hasFollowers = $pdo->query("SHOW TABLES LIKE 'followers'")->fetch();
if ($hasFollowers) {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM followers WHERE followed_id = :id');
    $stmt->execute(['id' => $user['user_id']]);
    $followerCount = (int)($stmt->fetch()['c'] ?? 0);
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . ' mins ago';
    if ($diff < 86400)   return floor($diff / 3600) . ' hrs ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return floor($diff / 2592000) . ' mos ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Crypt of Secrets</title>

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

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-4 sm:px-6 md:px-8 py-6 md:py-10">
        <div class="w-full max-w-6xl mx-auto">
            <header class="flex justify-end items-center border-b border-[#FAEAC9] pb-3 mb-6 md:mb-8">
                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>
        </div>

        <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-x-8 gap-y-6 items-start">

            <div class="col-start-1 lg:col-span-2 row-start-1 flex flex-col gap-6">

                <div class="flex flex-wrap items-end justify-between gap-4 pb-3">
                    <div class="flex items-center gap-6">
                        <div class="w-24 h-24 shrink-0">
                            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile" class="w-full h-full object-contain">
                        </div>
                        <div class="flex flex-col gap-1">
                            <h1 class="text-[#FAEAC9] text-2xl tracking-widest uppercase"><?= htmlspecialchars($name) ?></h1>
                            <span class="font-['Fira_Sans'] text-sm text-[#72685F]">Member since <?= htmlspecialchars($joinedDate) ?></span>
                        </div>
                    </div>

                    <a href="logout.php"
                       class="relative flex items-center justify-center shrink-0 ml-auto group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-40 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-base font-semibold tracking-widest uppercase">
                            Log out
                        </span>
                    </a>
                </div>

            </div>

                 matching the single-column analytics page. -->
            <div class="col-start-1 lg:col-span-2 row-start-2 flex flex-col gap-6">
                <div class="w-full border-t border-[#72685F]"></div>

                <nav class="w-full flex flex-wrap gap-4 sm:gap-6 border-b border-[#72685F] uppercase text-sm sm:text-base font-semibold tracking-widest">
                    <a href="profile.php?tab=overview" class="<?= $activeTab === 'overview' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">Overview</a>
                    <a href="profile.php?tab=posts" class="<?= $activeTab === 'posts' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">Posts</a>
                    <a href="profile.php?tab=true" class="<?= $activeTab === 'true' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">True</a>
                    <a href="profile.php?tab=false" class="<?= $activeTab === 'false' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">False</a>
                </nav>
            </div>

                <div class="col-start-1 row-start-3 flex flex-col gap-3">

                    <?php if (empty($posts)): ?>
                    <div class="relative p-6 text-center">
                        <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none bg-[#121110] opacity-90"></div>
                        <p class="relative font-['Fira_Sans'] text-sm text-[#9b9186]">No confessions yet.</p>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($posts as $post):
                        $total = (int)$post['total_votes'];
                        $trueC = (int)$post['true_count'];
                        $pct   = $total > 0 ? round(($trueC / $total) * 100) : null;
                    ?>
                    <div class="relative p-4">
                        <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none bg-[#121110] opacity-90"></div>
                        <div class="relative flex flex-col gap-2">
                            <div class="flex items-center gap-2 font-['Fira_Sans'] text-sm text-[#72685F]">
                                <span class="font-['Eczar'] text-base uppercase tracking-wide text-[#7A0A0A]"><?= htmlspecialchars($name) ?></span>
                                <span>confessed</span>
                                <span class="w-2 h-2 rounded-full bg-[#72685F] shrink-0"></span>
                                <span><?= timeAgo($post['created_at']) ?></span>
                            </div>
                            <h3 class="text-[#FAEAC9] text-lg tracking-widest uppercase"><?= htmlspecialchars($post['title']) ?></h3>
                            <p class="font-['Fira_Sans'] text-base text-[#e4d5b7]"><?= htmlspecialchars($post['content']) ?></p>
                            <div class="flex items-center gap-5 mt-1 font-['Fira_Sans'] text-sm text-[#72685F]">
                                <span class="<?= $pct !== null && $pct >= 70 ? 'text-[#E11C25]' : '' ?>">
                                    <?= $pct !== null ? $pct . '% true' : 'No votes yet' ?>
                                </span>
                                <span><?= $total ?> votes</span>
                                <span><?= (int)$post['award_count'] ?> awards</span>
                                <span><?= (int)$post['view_count'] ?> <?= (int)$post['view_count'] === 1 ? 'view' : 'views' ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <aside class="col-start-1 lg:col-start-2 row-start-4 lg:row-start-3 flex flex-col gap-5 sticky top-10">

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-4">

                        <?php if ($followerCount !== null): ?>
                        <span class="font-['Fira_Sans'] text-sm text-[#72685F]"><?= $followerCount ?> followers</span>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4 <?= $followerCount !== null ? 'border-t border-[#3a332c] pt-4' : '' ?>">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-2xl text-[#FAEAC9]"><?= (int)$user['trust_index'] ?></span>
                                <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#72685F]">Trust index</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-2xl text-[#FAEAC9]"><?= $postCount ?></span>
                                <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#72685F]">Confessions</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-2xl text-[#FAEAC9]"><?= $trueVotes ?></span>
                                <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#72685F]">True votes</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-2xl text-[#FAEAC9]"><?= htmlspecialchars($joinedDate) ?></span>
                                <span class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#72685F]">Joined</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-3">
                        <span class="uppercase text-lg tracking-widest text-[#FAEAC9]">Tarot cards</span>

                        <?php if (empty($cards)): ?>
                        <p class="font-['Fira_Sans'] text-sm text-[#72685F]">None collected yet.</p>
                        <?php else: ?>
                        <div class="grid grid-cols-3 gap-2">
                            <?php foreach ($cards as $card): ?>
                            <div class="relative rounded overflow-hidden bg-[#1c1a18] border border-[#7A0A0A] aspect-[2/3]"
                                 title="<?= htmlspecialchars($card['tarot_name']) ?>">
                                <img src="<?= BASE_URL ?>assets/tarot/<?= htmlspecialchars($card['icon_filename']) ?>"
                                     alt="<?= htmlspecialchars($card['tarot_name']) ?>"
                                     class="w-full h-full object-cover">
                                <?php if ((int)$card['quantity'] > 1): ?>
                                <span class="absolute top-1 right-1 bg-[#7A0A0A] text-[#121110] font-['Fira_Sans'] text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                                    <?= (int)$card['quantity'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <span class="font-['Fira_Sans'] text-sm text-[#72685F]">
                            <?= count($cards) ?> ready to use
                        </span>
                        <?php endif; ?>

                        <a href="awards.php" class="text-base uppercase tracking-widest text-[#FAEAC9] hover:text-[#E11C25] transition-colors self-start">
                            View all
                        </a>
                    </div>
                </div>

            </aside>

        </div>
    </main>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>