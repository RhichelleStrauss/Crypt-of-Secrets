<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user    = currentUser($pdo);
$error   = null;
$title   = '';
$body    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!checkCsrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $title  = trim($_POST['title'] ?? '');
        $body   = trim($_POST['body'] ?? '');
        $action = $_POST['action'] ?? 'post';

        if ($title === '' || $body === '') {
            $error = 'Both a title and a confession are required.';
        } elseif (mb_strlen($title) > 200) {
            $error = 'That title is too long.';
        } elseif (mb_strlen($body) > 4000) {
            $error = 'That confession is too long.';
        } else {
            $status = $action === 'draft' ? 'draft' : 'pending';

            $stmt = $pdo->prepare(
                'INSERT INTO posts (author_id, title, content, status, posted_anonymously)
                 VALUES (:author_id, :title, :content, :status, :anon)'
            );
            $stmt->execute([
                'author_id' => $user['user_id'],
                'title'     => $title,
                'content'   => $body,
                'status'    => $status,
                'anon'      => $user['is_anonymous'] ? 1 : 0,
            ]);

            header('Location: ' . ($action === 'draft' ? 'profile.php' : 'home.php?submitted=1'));
            exit;
        }
    }
}
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

        <header class="flex justify-end items-center border-b border-[#FAEAC9] pb-3 mb-8">

            <div class="flex items-center gap-5">
                <a href="create-post.php"
                    class="w-16 h-16 flex items-center justify-center text-[#121110] text-xl font-black hover:bg-white transition-colors">
                    <img src="<?= BASE_URL ?>assets/images/icons/tempAddIcon.png" alt="Create post" class="w-full h-full object-cover">
                </a>
                <div class="flex flex-col items-center text-[16px]">
                    <div class="w-12 h-12 rounded-full border border-red-900 overflow-hidden mb-1">
                        <img src="<?= BASE_URL ?>assets/images/icons/profileDummy.png" alt="Profile" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>

        </header>

        <form method="POST" action="create-post.php" class="w-full max-w-4xl flex flex-col gap-6 mx-auto flex-1">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">

            <div class="flex items-center justify-center gap-4 md:gap-8 mb-10">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-10 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-4xl tracking-widest uppercase">Create Post</h1>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-10 object-contain">
            </div>

            <?php if ($error): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <div class="flex flex-col gap-2">
                <label for="postTitle" class="text-[#eaddc5] uppercase text-xl tracking-widest">Title</label>
                <div class="relative">
                    <input id="postTitle" type="text" name="title" maxlength="200"
                        value="<?= htmlspecialchars($title) ?>"
                        class="peer relative z-10 w-full rounded-xl bg-[#121110]/40 text-[#FAEAC9] px-3 py-2 font-['Fira_Sans'] focus:outline-none">
                    <div class="absolute inset-0 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                </div>
            </div>

            <div class="flex flex-col gap-2 flex-1">
                <label for="postBody" class="text-[#eaddc5] uppercase text-xl tracking-widest">Post</label>
                <div class="relative flex-1">
                    <textarea id="postBody" name="body" maxlength="4000" placeholder="Text Area"
                        class="peer relative z-10 w-full h-64 rounded-xl resize-none bg-[#121110]/40 font-['Fira_Sans'] text-[#FAEAC9] placeholder:text-[#6b6b6b] text-sm p-3 focus:outline-none"><?= htmlspecialchars($body) ?></textarea>
                    <div class="absolute inset-0 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                </div>
            </div>

            <div class="flex justify-end items-start mt-2">
                <div class="flex gap-4">
                    <button type="submit" name="action" value="draft"
                        class="relative mt-2 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt="" class="w-60 h-auto drop-shadow-md">
                        <span class="absolute text-[#FAEAC9] text-xl tracking-widest uppercase transition-colors">
                            Save Draft
                        </span>
                    </button>

                    <button type="submit" name="action" value="post"
                        class="relative mt-2 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-60 h-auto drop-shadow-md">
                        <span class="absolute text-[#FAEAC9] text-xl tracking-widest uppercase transition-colors">
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