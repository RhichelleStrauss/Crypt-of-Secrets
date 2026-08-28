<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireLeader();

$user   = currentUser($pdo);
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'])) {

    if (!checkCsrf($_POST['csrf'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
    } else {
        $postId   = (int)$_POST['post_id'];
        $decision = $_POST['decision'] ?? '';
        $note     = trim($_POST['review_note'] ?? '');

        if ($note === '' || $decision === 'approved') {
            $note = null;
        } elseif (mb_strlen($note) > 255) {
            $note = mb_substr($note, 0, 255);
        }

        if (in_array($decision, ['approved', 'rejected'], true)) {

            $stmt = $pdo->prepare('SELECT author_id FROM posts WHERE post_id = :id AND status = "pending"');
            $stmt->execute(['id' => $postId]);
            $row = $stmt->fetch();

            if ($row) {
                $pdo->prepare(
                    'UPDATE posts
                     SET status = :status, review_note = :note,
                         reviewed_by = :reviewer, reviewed_at = NOW()
                     WHERE post_id = :id'
                )->execute([
                    'status'   => $decision,
                    'note'     => $note,
                    'reviewer' => $user['user_id'],
                    'id'       => $postId,
                ]);

                notify(
                    $pdo,
                    (int)$row['author_id'],
                    $decision === 'approved' ? 'post_approved' : 'post_rejected',
                    $postId
                );

                $notice = $decision === 'approved'
                    ? 'Confession admitted to the crypt.'
                    : 'Confession denied.';
            }
        }
    }
}

$activeTab = $_GET['tab'] ?? 'pending';
if (!in_array($activeTab, ['pending', 'approved', 'rejected'], true)) {
    $activeTab = 'pending';
}

$order = $activeTab === 'pending' ? 'p.created_at ASC' : 'p.reviewed_at DESC, p.created_at DESC';

$stmt = $pdo->prepare(
    "SELECT p.post_id, p.title, p.content, p.created_at, p.status,
            p.review_note, p.reviewed_at,
            u.anon_handle, u.animal_username, u.is_anonymous, u.trust_index,
            r.anon_handle AS reviewer_handle, r.animal_username AS reviewer_name
     FROM posts p
     JOIN users u ON u.user_id = p.author_id
     LEFT JOIN users r ON r.user_id = p.reviewed_by
     WHERE p.status = :status
     ORDER BY $order"
);
$stmt->execute(['status' => $activeTab]);
$posts = $stmt->fetchAll();

$counts = $pdo->query(
    'SELECT status, COUNT(*) AS c FROM posts GROUP BY status'
)->fetchAll(PDO::FETCH_KEY_PAIR);

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$decidedTotal = ($counts['approved'] ?? 0) + ($counts['rejected'] ?? 0);
$approvalRate = $decidedTotal > 0
    ? round(($counts['approved'] ?? 0) / $decidedTotal * 100)
    : null;

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . 'm ago';
    if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return floor($diff / 2592000) . 'mo ago';
}

function reviewerName(array $post): string {
    if (!empty($post['reviewer_name'])) return $post['reviewer_name'];
    if (!empty($post['reviewer_handle'])) return $post['reviewer_handle'];
    return 'the leader';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leader Dashboard - Crypt of Secrets</title>

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

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-4 sm:px-6 md:px-8 py-6">

        <div class="w-full max-w-4xl mx-auto flex flex-col gap-6">

            <header class="flex justify-end items-center border-b border-[#FAEAC9] pb-3 mb-6 md:mb-8">
                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>

            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h1 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Leader Dashboard</h1>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#E11C25]"><?= $counts['pending'] ?? 0 ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#72685F]">Awaiting judgement</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $counts['approved'] ?? 0 ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#72685F]">Admitted</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#72685F]"><?= $counts['rejected'] ?? 0 ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#72685F]">Denied</span>
                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-1">
                        <span class="text-4xl text-[#FAEAC9]"><?= $approvalRate !== null ? $approvalRate . '%' : '–' ?></span>
                        <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#72685F]">Admitted rate</span>
                    </div>
                </div>

            </div>

            <div class="flex items-center gap-6 font-['Fira_Sans'] text-base text-[#72685F]">
                <span><span class="text-[#FAEAC9]"><?= $totalUsers ?></span> souls in the crypt</span>
            </div>

            <?php if ($notice): ?>
            <div class="border border-[#4d4d4d] bg-[#4d4d4d]/10 rounded-xl px-5 py-4">
                <p class="font-['Fira_Sans'] text-base text-[#4d4d4d]"><?= htmlspecialchars($notice) ?></p>
            </div>
            <?php endif; ?>

            <nav class="flex gap-6 border-b border-[#72685F] uppercase text-base font-semibold tracking-widest">
                <a href="leader.php?tab=pending" class="<?= $activeTab === 'pending' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">
                    Awaiting (<?= $counts['pending'] ?? 0 ?>)
                </a>
                <a href="leader.php?tab=approved" class="<?= $activeTab === 'approved' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">
                    Admitted
                </a>
                <a href="leader.php?tab=rejected" class="<?= $activeTab === 'rejected' ? 'tab-active' : 'text-[#9b9186] hover:text-[#FAEAC9]' ?> pb-3 transition-colors">
                    Denied
                </a>
            </nav>

            <?php if (empty($posts)): ?>
            <div class="relative p-10 text-center">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-base text-[#72685F]">
                    <?php if ($activeTab === 'pending'): ?>
                    Nothing awaits judgement.
                    <?php elseif ($activeTab === 'approved'): ?>
                    Nothing has been admitted yet.
                    <?php else: ?>
                    Nothing has been denied yet.
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <?php foreach ($posts as $post): ?>
            <div class="relative p-6">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>

                <div class="relative flex flex-col gap-4">

                    <div class="flex items-center justify-between font-['Fira_Sans'] text-base text-[#72685F]">
                        <div class="flex items-center gap-3">
                            <span class="text-[#FAEAC9]"><?= htmlspecialchars($post['anon_handle']) ?></span>
                            <span>trust <?= (int)$post['trust_index'] ?></span>
                        </div>
                        <span><?= timeAgo($post['created_at']) ?></span>
                    </div>

                    <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest"><?= htmlspecialchars($post['title']) ?></h2>

                    <p class="font-['Fira_Sans'] text-base text-[#e4d5b7] leading-relaxed whitespace-pre-line"><?= htmlspecialchars($post['content']) ?></p>

                    <?php if ($activeTab === 'pending'): ?>
                    <div class="flex flex-col gap-3 pt-2">

                        <div class="flex gap-3 justify-end">

                                 denying outright, so the leader always gets a
                                 chance to explain (the reason stays optional). -->
                            <button type="button"
                                onclick="openDeny(<?= $post['post_id'] ?>)"
                                class="relative flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                                <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-32 h-auto drop-shadow-md">
                                <span class="absolute text-[#121110] text-base font-semibold tracking-widest uppercase">
                                    Deny
                                </span>
                            </button>

                            <form method="POST" action="leader.php?tab=pending">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                                <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit"
                                    class="relative flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                                    <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-32 h-auto drop-shadow-md">
                                    <span class="absolute text-[#121110] text-base font-semibold tracking-widest uppercase">
                                        Admit
                                    </span>
                                </button>
                            </form>

                        </div>

                        <form method="POST" action="leader.php?tab=pending"
                              id="deny-<?= $post['post_id'] ?>"
                              class="hidden flex-col gap-3 border-t border-[#3a332c] pt-3">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <input type="hidden" name="decision" value="rejected">

                            <label for="note-<?= $post['post_id'] ?>"
                                   class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#72685F]">
                                Why is this confession denied?
                            </label>

                            <div class="relative">
                                <textarea id="note-<?= $post['post_id'] ?>" name="review_note" rows="2" maxlength="255"
                                          placeholder="The author will see this. Leave blank to deny without a reason."
                                          class="peer relative z-10 w-full rounded-xl resize-none bg-[#121110]/40 text-[#FAEAC9] px-3 py-2 font-['Fira_Sans'] text-sm placeholder:text-[#6b6b6b] focus:outline-none"></textarea>
                                <div class="absolute inset-0 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                            </div>

                            <div class="flex gap-3 justify-end items-center">
                                <button type="button" onclick="closeDeny(<?= $post['post_id'] ?>)"
                                    class="relative flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95">
                                    <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-44 h-auto drop-shadow-md">
                                    <span class="absolute text-[#121110] text-sm font-semibold tracking-widest uppercase">
                                        Cancel
                                    </span>
                                </button>
                                <button type="submit"
                                    class="relative flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                                    <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-44 h-auto drop-shadow-md">
                                    <span class="absolute text-[#121110] text-sm font-semibold tracking-widest uppercase whitespace-nowrap">
                                        Confirm
                                    </span>
                                </button>
                            </div>
                        </form>

                    </div>
                    <?php else: ?>
                    <div class="flex flex-col gap-2 border-t border-[#3a332c] pt-3">
                        <div class="flex items-center justify-between font-['Fira_Sans'] text-base">
                            <span class="uppercase tracking-widest text-[#7A0A0A]">
                                <?= $post['status'] === 'approved' ? 'Admitted' : 'Denied' ?>
                                by <?= htmlspecialchars(reviewerName($post)) ?>
                            </span>
                            <?php if ($post['reviewed_at']): ?>
                            <span class="text-[#72685F]"><?= timeAgo($post['reviewed_at']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($post['review_note'])): ?>
                        <p class="font-['Fira_Sans'] text-base text-[#72685F] italic">
                            "<?= htmlspecialchars($post['review_note']) ?>"
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </main>

    <script>
        function openDeny(postId) {
            var form = document.getElementById('deny-' + postId);
            if (!form) return;
            form.classList.remove('hidden');
            form.classList.add('flex');
            var note = document.getElementById('note-' + postId);
            if (note) note.focus();
        }

        function closeDeny(postId) {
            var form = document.getElementById('deny-' + postId);
            if (!form) return;
            form.classList.add('hidden');
            form.classList.remove('flex');
            var note = document.getElementById('note-' + postId);
            if (note) note.value = '';
        }
    </script>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>
