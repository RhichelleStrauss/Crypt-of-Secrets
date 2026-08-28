<?php
require_once __DIR__ . '/../includes/notifications.php';

$unreadCount = unreadNotificationCount($pdo, $user['user_id']);

$canClaimIdentity = $user['is_anonymous']
    && empty($user['animal_username'])
    && (int)$user['trust_index'] >= TRUST_THRESHOLD
    && !isLeader();

$defaultAvatar = BASE_URL . 'assets/images/animals/CryptDefaultLambIcon.png';

if ($user['is_anonymous']) {
    $iconRowAvatar = $defaultAvatar;
} elseif ($user['custom_avatar_status'] === 'approved' && !empty($user['custom_avatar'])) {
    $iconRowAvatar = BASE_URL . 'uploads/avatars/' . $user['custom_avatar'];
} elseif (!empty($user['avatar_id'])) {
    $stmt = $pdo->prepare('SELECT COALESCE(display_filename, filename) AS icon FROM animal_avatars WHERE avatar_id = :id');
    $stmt->execute(['id' => $user['avatar_id']]);
    $row = $stmt->fetch();
    $iconRowAvatar = $row
        ? BASE_URL . 'assets/images/animals/' . $row['icon']
        : $defaultAvatar;
} else {
    $iconRowAvatar = $defaultAvatar;
}
?>
<style>
    .glow-wrap { cursor: pointer; }
    .glow-wrap .glow-item {
        transition: filter 0.3s ease, transform 0.3s ease;
    }
    .glow-wrap:hover .glow-item {
        filter: drop-shadow(0 0 8px #E11C25) drop-shadow(0 0 30px #7A0A0A);
        transform: scale(1.08) translateY(-2px);
    }
    .glow-wrap:hover span:not(.glow-item) { color: #E11C25; }

    @media (max-width: 767px) {
        .icon-row-inline { display: none; }
        .icon-row-bottom { display: flex; }
        #mainContent { padding-bottom: 6rem; }
    }
    @media (min-width: 768px) {
        .icon-row-bottom { display: none; }
    }

    .icon-row-bottom a { width: 60px; height: 60px; }
    .icon-row-bottom img,
    .icon-row-bottom .glow-item { width: 52px; height: 52px; }
    .icon-row-bottom .glow-item img { width: 100%; height: 100%; }
</style>

<?php
$iconRowLinks = function (bool $bottom) use ($unreadCount, $iconRowAvatar, $canClaimIdentity) {
    $size = $bottom ? 'w-9 h-9' : 'w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12';
    ob_start(); ?>
    <?php if ($canClaimIdentity): ?>
    <a href="claim-profile.php" title="Shed your silence"
       class="glow-wrap flex items-center justify-center <?= $bottom ? 'w-12 h-12' : 'w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14' ?>">
        <img src="<?= BASE_URL ?>assets/images/icons/CryptHammerIcon.png" alt="Claim your name"
             class="glow-item <?= $bottom ? 'w-10 h-10' : 'w-8 h-8 sm:w-9 sm:h-9 md:w-11 md:h-11' ?> object-contain">
    </a>
    <?php endif; ?>

    <?php if (isLeader()): ?>
    <a href="leader.php" class="glow-wrap flex items-center justify-center <?= $bottom ? 'w-14 h-14' : 'w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14' ?>">
        <img src="<?= BASE_URL ?>assets/images/icons/LeaderDashIcon.png" alt="Leader dashboard"
             class="glow-item <?= $bottom ? 'w-11 h-11' : 'w-8 h-8 sm:w-9 sm:h-9 md:w-11 md:h-11' ?> object-contain">
    </a>
    <?php endif; ?>

    <a href="notifications.php" class="glow-wrap relative flex items-center justify-center <?= $bottom ? 'w-14 h-14' : 'w-11 h-11 sm:w-14 sm:h-14 md:w-16 md:h-16' ?>">
        <img src="<?= BASE_URL ?>assets/images/icons/CryptBellIcon.png" alt="Notifications"
             class="glow-item <?= $bottom ? 'w-12 h-12' : 'w-11 h-11 sm:w-14 sm:h-14 md:w-16 md:h-16' ?> object-contain">
        <?php if ($unreadCount > 0): ?>
        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-[#E11C25] text-[#FAEAC9] text-[10px] font-['Fira_Sans'] font-bold leading-none">
            <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
        </span>
        <?php endif; ?>
    </a>

    <a href="create-post.php" class="glow-wrap flex items-center justify-center <?= $bottom ? 'w-12 h-12' : 'w-9 h-9 sm:w-10 sm:h-10 md:w-12 md:h-12' ?>">
        <img src="<?= BASE_URL ?>assets/images/icons/CryptPlusIcon.png" alt="Add post"
             class="glow-item w-full h-full object-cover">
    </a>

    <a href="profile.php" class="glow-wrap flex items-center justify-center">
        <div class="glow-item <?= $bottom ? 'w-12 h-12' : 'w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14' ?>">
            <img src="<?= htmlspecialchars($iconRowAvatar) ?>" alt="Profile" class="w-full h-full object-contain">
        </div>
    </a>
    <?php
    return ob_get_clean();
};
?>

<div class="icon-row-inline flex items-center gap-2 sm:gap-4 md:gap-5">
    <?= $iconRowLinks(false) ?>
</div>

<div class="icon-row-bottom fixed left-0 right-0 bottom-0 z-[60] items-center justify-around px-3 py-2 bg-[#121110]/98 border-t border-[#3a332c]">
    <?= $iconRowLinks(true) ?>
</div>
