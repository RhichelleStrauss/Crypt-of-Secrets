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

$avatarSrc = BASE_URL . 'assets/images/icons/profileDummy.png';
if ($user['custom_avatar_status'] === 'approved' && $user['custom_avatar']) {
    $avatarSrc = BASE_URL . 'uploads/avatars/' . $user['custom_avatar'];
} elseif (!empty($user['avatar_id'])) {
    $stmt = $pdo->prepare('SELECT filename FROM animal_avatars WHERE avatar_id = :id');
    $stmt->execute(['id' => $user['avatar_id']]);
    if ($row = $stmt->fetch()) {
        $avatarSrc = BASE_URL . 'assets/images/animals/' . $row['filename'];
    }
}

$memberSince = date('M Y', strtotime($user['created_at']));


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

$stmt = $pdo->prepare(
    "SELECT p.post_id, p.content, p.created_at,
            COUNT(DISTINCT tv.vote_id) AS total_votes,
            SUM(CASE WHEN tv.is_true = 1 THEN 1 ELSE 0 END) AS true_count,
            COUNT(DISTINCT pa.award_instance_id) AS award_count
     FROM posts p
     LEFT JOIN truth_voting tv ON tv.post_id = p.post_id
     LEFT JOIN post_awards pa  ON pa.post_id = p.post_id
     WHERE p.author_id = :id AND p.status = \"approved\"
     GROUP BY p.post_id
     $voteFilter
     ORDER BY p.created_at DESC
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
    if ($diff < 3600)  return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return floor($diff / 2592000) . ' months ago';
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
            color: #FAEAC9;
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

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-8 py-10">
        <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8 items-start">

            <div class="flex flex-col gap-6">

                <div class="flex items-center justify-between border-b border-[#72685F] pb-6">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 rounded-full border-2 border-[#7A0A0A] overflow-hidden shrink-0 bg-[#1c1a18] p-1">
                            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Profile" class="w-full h-full object-contain">
                        </div>
                        <div class="flex flex-col gap-1">
                            <h1 class="text-[#FAEAC9] text-2xl tracking-widest uppercase"><?= htmlspecialchars($name) ?></h1>
                            <span class="font-['Fira_Sans'] text-sm text-[#9b9186]">Member since <?= htmlspecialchars($memberSince) ?></span>
                        </div>
                    </div>

                    <a href="logout.php"
                       class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186] hover:text-[#E11C25] transition-colors border border-[#3a332c] hover:border-[#E11C25] rounded-full px-4 py-2">
                        Log out
                    </a>
                </div>

                <nav class="flex gap-6 border-b border-[#72685F] font-['Fira_Sans'] uppercase text-sm tracking-widest">
                    <a href="profile.php?tab=overview" class="<?= $activeTab === 'overview' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">Overview</a>
                    <a href="profile.php?tab=posts" class="<?= $activeTab === 'posts' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">Posts</a>
                    <a href="profile.php?tab=true" class="<?= $activeTab === 'true' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">True</a>
                    <a href="profile.php?tab=false" class="<?= $activeTab === 'false' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">False</a>
                </nav>

                <div class="flex flex-col gap-3">

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
                            <div class="flex items-center gap-2 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="text-[#FAEAC9]"><?= htmlspecialchars($name) ?></span>
                                <span>confessed</span>
                                <span><?= timeAgo($post['created_at']) ?></span>
                            </div>
                            <p class="font-['Fira_Sans'] text-sm text-[#e4d5b7]"><?= htmlspecialchars($post['content']) ?></p>
                            <div class="flex items-center gap-5 mt-1 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="<?= $pct !== null && $pct >= 70 ? 'text-[#E11C25]' : '' ?>">
                                    <?= $pct !== null ? $pct . '% true' : 'No votes yet' ?>
                                </span>
                                <span><?= $total ?> votes</span>
                                <span><?= (int)$post['award_count'] ?> awards</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <aside class="flex flex-col gap-5 sticky top-10">

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-4">

                        <?php if ($followerCount !== null): ?>
                        <span class="font-['Fira_Sans'] text-sm text-[#9b9186]"><?= $followerCount ?> followers</span>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4 <?= $followerCount !== null ? 'border-t border-[#3a332c] pt-4' : '' ?>">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]"><?= (int)$user['trust_index'] ?></span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">Trust index</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]"><?= $postCount ?></span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">Confessions</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]"><?= $trueVotes ?></span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">True votes</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]"><?= htmlspecialchars($memberSince) ?></span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">Joined</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-3">
                        <span class="font-['Fira_Sans'] uppercase text-xs tracking-widest text-[#9b9186]">Tarot cards</span>

                        <?php if (empty($cards)): ?>
                        <p class="font-['Fira_Sans'] text-xs text-[#9b9186]">None collected yet.</p>
                        <?php else: ?>
                        <div class="flex gap-2 flex-wrap">
                            <?php foreach (array_slice($cards, 0, 6) as $card): ?>
                            <div class="w-10 h-14 rounded overflow-hidden bg-[#1c1a18] border border-[#7A0A0A]">
                                <img src="<?= BASE_URL ?>assets/images/tarot/<?= htmlspecialchars($card['icon_filename']) ?>"
                                     alt="<?= htmlspecialchars($card['tarot_name']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <span class="font-['Fira_Sans'] text-xs text-[#9b9186]"><?= count($cards) ?> unlocked</span>
                        <?php endif; ?>

                        <a href="awards.php" class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#FAEAC9] hover:text-[#E11C25] transition-colors self-start">
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