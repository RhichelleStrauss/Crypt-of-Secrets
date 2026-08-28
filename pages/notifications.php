<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all'])
    && checkCsrf($_POST['csrf'] ?? null)) {
    $pdo->prepare('DELETE FROM notifications WHERE user_id = :uid')
        ->execute(['uid' => $user['user_id']]);
    header('Location: notifications.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT n.notification_id, n.type, n.post_id, n.is_read, n.created_at,
            p.title AS post_title, p.review_note
     FROM notifications n
     LEFT JOIN posts p ON p.post_id = n.post_id
     WHERE n.user_id = :uid
     ORDER BY n.created_at DESC
     LIMIT 50'
);
$stmt->execute(['uid' => $user['user_id']]);
$notifications = $stmt->fetchAll();

// Opening this page marks everything as read - capture which rows were still
// unread *before* that so they can be highlighted for this one visit.
$pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0')
    ->execute(['uid' => $user['user_id']]);

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return floor($diff / 2592000) . 'mo ago';
}

function notificationText(array $n): string {
    $title = $n['post_title'] !== null ? "'" . $n['post_title'] . "'" : 'a confession';
    switch ($n['type']) {
        case 'post_submitted':
            return "Your confession $title was sent to the leader for review.";
        case 'post_approved':
            return "Your confession $title was admitted to the crypt.";
        case 'post_rejected':
            return !empty($n['review_note'])
                ? "Your confession $title was denied - \"" . $n['review_note'] . '"'
                : "Your confession $title was denied.";
        case 'award_received':
            return "A fragment was gifted on your confession $title.";
        case 'fragment_gained':
            return "You gained a tarot fragment while passing judgement on $title.";
        case 'vote_received':
            return "Someone believed your confession $title was true.";
        case 'trust_threshold':
            return "Your trust has grown enough to shed your silence and take a name.";
        default:
            return 'Something stirred.';
    }
}

function notificationLink(array $n): ?string {
    if ($n['type'] === 'trust_threshold') {
        return 'claim-profile.php';
    }
    if ($n['type'] === 'post_submitted') {
        // Still pending - it won't show up on the feed yet, so there's
        // nowhere meaningful to send them.
        return null;
    }
    if ($n['post_id'] !== null) {
        return 'home.php';
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }
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
        <div class="w-full max-w-4xl mx-auto flex flex-col gap-6">

            <header class="flex justify-end items-center border-b border-[#FAEAC9] pb-3 mb-2">
                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>

            <div class="flex flex-col items-center gap-3 mb-2">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h1 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Notifications</h1>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
            </div>

            <?php if (!empty($notifications)): ?>
            <div class="flex justify-end -mt-2">
                <form method="POST" action="notifications.php" id="clearForm">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <input type="hidden" name="clear_all" value="1">
                    <button type="submit"
                            class="relative flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-40 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-base font-semibold tracking-widest uppercase">
                            Clear all
                        </span>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if (empty($notifications)): ?>
            <div class="relative p-10 text-center">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-base text-[#72685F]">Nothing stirs yet.</p>
            </div>
            <?php endif; ?>

            <?php foreach ($notifications as $n):
                $wasUnread = !$n['is_read'];
                $link      = notificationLink($n);
            ?>
            <div class="relative p-5 <?= $wasUnread ? 'bg-[#7A0A0A]/10' : '' ?>">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>

                <div class="relative flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <?php if ($wasUnread): ?>
                        <span class="w-2 h-2 rounded-full bg-[#E11C25] shrink-0"></span>
                        <?php endif; ?>
                        <p class="font-['Fira_Sans'] text-base text-[#e4d5b7]">
                            <?= htmlspecialchars(notificationText($n)) ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <?php if ($link): ?>
                        <a href="<?= htmlspecialchars($link) ?>" class="font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#E11C25] hover:text-[#FAEAC9] transition-colors">
                            View
                        </a>
                        <?php endif; ?>
                        <span class="font-['Fira_Sans'] text-sm text-[#72685F]"><?= timeAgo($n['created_at']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </main>

    <div id="clearConfirm" class="hidden fixed inset-0 z-[70] items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="relative w-full max-w-md mx-4 p-8 text-center">
            <div class="absolute inset-0 bg-[#121110]/95 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
            <div class="relative flex flex-col items-center gap-3">
                <div class="flex items-center justify-center gap-4">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-6 object-contain">
                    <h2 class="text-[#FAEAC9] text-xl tracking-widest uppercase whitespace-nowrap">Clear all</h2>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-6 object-contain scale-x-[-1]">
                </div>
                <p class="font-['Fira_Sans'] text-base text-[#72685F]">
                    Every notification will be forgotten. This cannot be undone.
                </p>
                <div class="flex items-center gap-3 mt-3">
                    <button type="button" id="clearCancel"
                            class="relative flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-36 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-sm font-semibold tracking-widest uppercase">Cancel</span>
                    </button>
                    <button type="button" id="clearConfirmBtn"
                            class="relative flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-36 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-sm font-semibold tracking-widest uppercase">Clear</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('clearForm');
            var modal = document.getElementById('clearConfirm');
            if (!form || !modal) return;

            function close() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            document.getElementById('clearCancel').addEventListener('click', close);
            document.getElementById('clearConfirmBtn').addEventListener('click', function () {
                form.submit();
            });
            modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
        })();
    </script>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>
