<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireLogin();

$user    = currentUser($pdo);
$error   = null;
$title   = '';
$body    = '';
$draftId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!checkCsrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $title    = trim($_POST['title'] ?? '');
        $body     = trim($_POST['body'] ?? '');
        $action   = $_POST['action'] ?? 'post';
        $draftId  = !empty($_POST['draft_id']) ? (int)$_POST['draft_id'] : null;

        if ($title === '' || $body === '') {
            $error = 'Both a title and a confession are required.';
        } elseif (mb_strlen($title) > 200) {
            $error = 'That title is too long.';
        } elseif (mb_strlen($body) > 4000) {
            $error = 'That confession is too long.';
        } else {
            $isLeaderPost = $action !== 'draft' && isLeader();
            $status = $action === 'draft' ? 'draft' : ($isLeaderPost ? 'approved' : 'pending');

            if ($draftId !== null) {
                $stmt = $pdo->prepare(
                    'UPDATE posts SET title = :title, content = :content, status = :status,
                            reviewed_by = :reviewer,
                            reviewed_at = ' . ($isLeaderPost ? 'NOW()' : 'NULL') . '
                     WHERE post_id = :id AND author_id = :author_id AND status = "draft"'
                );
                $stmt->execute([
                    'title'     => $title,
                    'content'   => $body,
                    'status'    => $status,
                    'reviewer'  => $isLeaderPost ? $user['user_id'] : null,
                    'id'        => $draftId,
                    'author_id' => $user['user_id'],
                ]);
                $postId = $draftId;
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO posts (author_id, title, content, status, posted_anonymously,
                                        reviewed_by, reviewed_at)
                     VALUES (:author_id, :title, :content, :status, :anon,
                             :reviewer, ' . ($isLeaderPost ? 'NOW()' : 'NULL') . ')'
                );
                $stmt->execute([
                    'author_id' => $user['user_id'],
                    'title'     => $title,
                    'content'   => $body,
                    'status'    => $status,
                    'anon'      => $user['is_anonymous'] ? 1 : 0,
                    'reviewer'  => $isLeaderPost ? $user['user_id'] : null,
                ]);
                $postId = (int)$pdo->lastInsertId();
            }

            if ($action === 'draft') {
                header('Location: profile.php');
                exit;
            }

            notify(
                $pdo,
                $user['user_id'],
                $isLeaderPost ? 'post_approved' : 'post_submitted',
                $postId
            );

            header('Location: create-post.php?sent=1' . ($isLeaderPost ? '&live=1' : ''));
            exit;
        }
    }
} elseif (!empty($_GET['draft'])) {
    $stmt = $pdo->prepare(
        'SELECT post_id, title, content FROM posts
         WHERE post_id = :id AND author_id = :author_id AND status = "draft"'
    );
    $stmt->execute(['id' => (int)$_GET['draft'], 'author_id' => $user['user_id']]);
    if ($draft = $stmt->fetch()) {
        $draftId = (int)$draft['post_id'];
        $title   = $draft['title'];
        $body    = $draft['content'];
    }
}

$sent = isset($_GET['sent']);
$sentLive = isset($_GET['live']);

$stmt = $pdo->prepare(
    'SELECT post_id, title FROM posts
     WHERE author_id = :id AND status = "draft"
     ORDER BY created_at DESC'
);
$stmt->execute(['id' => $user['user_id']]);
$drafts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body {
            font-family: 'Eczar', serif;
        }

        .icon-swap {
            position: relative;
            display: inline-block;
        }

        .icon-swap img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .icon-swap .icon-hover {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 150ms ease;
        }

        button:hover .icon-swap .icon-hover,
        a:hover .icon-swap .icon-hover {
            opacity: 1;
        }

        .rough-border {
            filter: url(#rough-border);
        }

        @media (max-width: 767px) {
            .feed-header { justify-content: flex-end; }
        }

        .menu > summary {
            list-style: none;
            cursor: pointer;
        }
        .menu > summary::-webkit-details-marker { display: none; }
        .menu[open] > summary { color: #E11C25; }

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

    <?php if ($sent): ?>
    <div id="sentPopup" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="relative w-full max-w-md mx-4 p-8 text-center">
            <div class="absolute inset-0 bg-[#121110]/90 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
            <div class="relative flex flex-col items-center gap-3">
                <div class="flex items-center justify-center gap-4">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-6 object-contain">
                    <h2 class="text-[#FAEAC9] text-xl tracking-widest uppercase whitespace-nowrap">
                        <?= $sentLive ? 'Admitted' : 'Sent for review' ?>
                    </h2>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-6 object-contain scale-x-[-1]">
                </div>
                <p class="font-['Fira_Sans'] text-sm text-[#72685F]">
                    <?= $sentLive
                        ? 'Your confession enters the crypt at once.'
                        : 'Your confession has been sent to the leader for review.' ?>
                </p>
                <button type="button" onclick="document.getElementById('sentPopup').remove()"
                        class="relative mt-3 flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95">
                    <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-40 h-auto drop-shadow-md">
                    <span class="absolute text-[#121110] text-base font-semibold tracking-widest uppercase">
                        Dismiss
                    </span>
                </button>
            </div>
        </div>
    </div>
    <script>
        setTimeout(function () {
            var popup = document.getElementById('sentPopup');
            if (popup) popup.remove();
        }, 4000);
    </script>
    <?php endif; ?>

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-4 sm:px-6 md:px-8 py-6">

        <div class="w-full max-w-4xl mx-auto">
            <header class="feed-header flex flex-wrap gap-3 justify-between items-center border-b border-[#FAEAC9] pb-3 mb-8">

                <?php if (empty($drafts)): ?>
                <span class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#3a332c]">No drafts</span>
                <?php else: ?>
                <details class="menu relative">
                    <summary class="font-['Fira_Sans'] text-base uppercase tracking-widest text-[#e4d5b7] hover:text-[#E11C25] transition-colors underline underline-offset-4">
                        View Drafts
                    </summary>
                    <div class="absolute left-0 top-full mt-2 z-40 w-64">
                        <div class="absolute inset-0 bg-[#4d4d4d]/60 backdrop-blur-sm border-[3px] border-[#7A0A0A] rounded-lg rough-border pointer-events-none"></div>
                        <div class="relative z-10 py-2">
                            <?php foreach ($drafts as $draft): ?>
                            <a href="create-post.php?draft=<?= $draft['post_id'] ?>"
                               class="dropdown-link block px-4 py-2 font-['Fira_Sans'] text-base text-[#e4d5b7] hover:text-[#E11C25] transition-colors truncate">
                                <?= htmlspecialchars($draft['title']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </details>
                <?php endif; ?>

                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>
        </div>

        <form method="POST" action="create-post.php" class="w-full max-w-4xl flex flex-col gap-6 mx-auto flex-1">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
            <?php if ($draftId !== null): ?>
            <input type="hidden" name="draft_id" value="<?= $draftId ?>">
            <?php endif; ?>

            <div class="flex flex-col items-center gap-3 mb-10">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="Ornament" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h1 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Create Post</h1>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="Ornament" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
            </div>

            <?php if ($error): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <div class="flex flex-col gap-2">
                <label for="postTitle" class="text-[#e4d5b7] uppercase text-xl tracking-widest">Title</label>
                <div class="relative">
                    <input id="postTitle" type="text" name="title" maxlength="200"
                        value="<?= htmlspecialchars($title) ?>"
                        class="peer relative z-10 w-full rounded-xl bg-[#121110]/40 text-[#FAEAC9] text-base px-3 py-2 font-['Fira_Sans'] focus:outline-none">
                    <div class="absolute inset-0 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                </div>
            </div>

            <div class="flex flex-col gap-2 flex-1">
                <label for="postBody" class="text-[#e4d5b7] uppercase text-xl tracking-widest">Post</label>
                <div class="relative flex-1">
                    <textarea id="postBody" name="body" maxlength="4000" placeholder="Text Area"
                        class="peer relative z-10 w-full h-64 rounded-xl resize-none bg-[#121110]/40 font-['Fira_Sans'] text-[#FAEAC9] placeholder:text-[#6b6b6b] text-base leading-relaxed p-3 focus:outline-none"><?= htmlspecialchars($body) ?></textarea>
                    <div class="absolute inset-0 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                </div>
            </div>

            <div class="flex justify-end items-start mt-2">
                <div class="flex gap-4">
                    <button type="submit" name="action" value="draft"
                        class="relative mt-2 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-60 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-xl tracking-widest uppercase transition-colors">
                            Save Draft
                        </span>
                    </button>

                    <button type="submit" name="action" value="post"
                        class="relative mt-2 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-60 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-xl tracking-widest uppercase transition-colors">
                            POST
                        </span>
                    </button>
                </div>
            </div>

        </form>

    </main>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>

</html>