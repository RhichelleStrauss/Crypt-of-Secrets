<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser($pdo);


if (!$user['is_anonymous'] && !empty($user['animal_username'])) {
    header('Location: profile.php');
    exit;
}

$trust     = (int)$user['trust_index'];
$eligible  = $trust >= TRUST_THRESHOLD;
$error     = null;

$adjectives = [
    'Stinky', 'Silent', 'Hollow', 'Weeping', 'Grim',
    'Ashen', 'Sullen', 'Restless', 'Wicked', 'Quiet',
];


$stmt = $pdo->prepare(
    'SELECT avatar_id, animal_name, filename, min_trust
     FROM animal_avatars
     WHERE min_trust <= :trust
     ORDER BY min_trust, animal_name'
);
$stmt->execute(['trust' => $trust]);
$available = $stmt->fetchAll();


$stmt = $pdo->prepare(
    'SELECT animal_name, filename, min_trust
     FROM animal_avatars
     WHERE min_trust > :trust
     ORDER BY min_trust, animal_name'
);
$stmt->execute(['trust' => $trust]);
$locked = $stmt->fetchAll();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $eligible) {

    if (!checkCsrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $avatarId  = (int)($_POST['avatar_id'] ?? 0);
        $adjective = $_POST['adjective'] ?? '';

        
        $stmt = $pdo->prepare(
            'SELECT animal_name FROM animal_avatars
             WHERE avatar_id = :id AND min_trust <= :trust'
        );
        $stmt->execute(['id' => $avatarId, 'trust' => $trust]);
        $animal = $stmt->fetch();

        if (!$animal) {
            $error = 'That animal is not yours to take.';
        } elseif (!in_array($adjective, $adjectives, true)) {
            $error = 'Choose a name.';
        } else {
            $base = $adjective . ucfirst($animal['animal_name']);

            $check = $pdo->prepare('SELECT 1 FROM users WHERE animal_username = :name');
            $attempts = 0;
            do {
                $username = $base . random_int(10, 99);
                $check->execute(['name' => $username]);
                $taken = $check->fetch();
                $attempts++;
            } while ($taken && $attempts < 50);

            if ($taken) {
                $error = 'That name is too crowded. Try another.';
            } else {
              $pdo->prepare(
    'UPDATE users
     SET animal_username = :name, avatar_id = :avatar, is_anonymous = FALSE
     WHERE user_id = :id'
)->execute([
    'name'   => $username,
    'avatar' => $avatarId,
    'id'     => $user['user_id'],
]);;

                header('Location: profile.php?claimed=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Your Name - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }

        .animal-pick input:checked + div {
            border-color: #E11C25;
            background: rgba(122, 10, 10, 0.35);
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
        <div class="w-full max-w-3xl mx-auto flex flex-col gap-8">

            <div class="flex items-center justify-center gap-4 md:gap-8">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-10 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-4xl tracking-widest uppercase">Step Forward</h1>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-10 object-contain">
            </div>

            <p class="text-center font-['Fira_Sans'] text-sm text-[#9b9186] max-w-xl mx-auto">
                You have confessed as <span class="text-[#FAEAC9]"><?= htmlspecialchars($user['anon_handle']) ?></span>.
                Earn the crypt's trust and it will grant you a face and a name.
            </p>

            <div class="flex flex-col gap-2">
                <div class="flex justify-between font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">
                    <span>Trust</span>
                    <span><?= $trust ?> / <?= TRUST_THRESHOLD ?></span>
                </div>
                <div class="w-full h-2 bg-[#1c1a18] rounded-full overflow-hidden border border-[#3a332c]">
                    <div class="h-full bg-[#7A0A0A]"
                         style="width: <?= min(100, $trust / TRUST_THRESHOLD * 100) ?>%"></div>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!$eligible): ?>

            <div class="relative p-10 text-center">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-sm text-[#9b9186]">
                    The crypt does not yet know you.
                    <?= TRUST_THRESHOLD - $trust ?> more trust and you may step forward.
                </p>
            </div>

            <?php else: ?>

            <form method="POST" action="claim-profile.php" class="flex flex-col gap-8">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">

                <div class="flex flex-col gap-4">
                    <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest">Choose your face</h2>

                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-4">
                        <?php foreach ($available as $i => $animal): ?>
                        <label class="animal-pick cursor-pointer">
                            <input type="radio" name="avatar_id" value="<?= $animal['avatar_id'] ?>"
                                   class="sr-only" <?= $i === 0 ? 'checked' : '' ?> required>
                            <div class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-[#3a332c] transition-colors hover:border-[#7A0A0A]">
                                <div class="w-11 h-11 rounded-full overflow-hidden bg-[#1c1a18]">
                                    <img src="<?= BASE_URL ?>assets/images/animals/<?= htmlspecialchars($animal['filename']) ?>"
                                         alt="<?= htmlspecialchars($animal['animal_name']) ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.style.display='none'">
                                </div>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">
                                    <?= htmlspecialchars($animal['animal_name']) ?>
                                </span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($locked)): ?>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-4 opacity-30 pointer-events-none mt-2">
                        <?php foreach ($locked as $animal): ?>
                        <div class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 border-dashed border-[#3a332c]">
                            <div class="w-11 h-11 rounded-full overflow-hidden bg-[#1c1a18] grayscale">
                                <img src="<?= BASE_URL ?>assets/images/animals/<?= htmlspecialchars($animal['filename']) ?>"
                                     alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
                            </div>
                            <span class="font-['Fira_Sans'] text-[10px] uppercase tracking-widest text-[#9b9186]">
                                <?= (int)$animal['min_trust'] ?> trust
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-4">
                    <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest">Choose your name</h2>

                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($adjectives as $i => $adj): ?>
                        <label class="animal-pick cursor-pointer">
                            <input type="radio" name="adjective" value="<?= htmlspecialchars($adj) ?>"
                                   class="sr-only" <?= $i === 0 ? 'checked' : '' ?> required>
                            <div class="px-4 py-2 rounded-full border-2 border-[#3a332c] font-['Fira_Sans'] text-sm transition-colors hover:border-[#7A0A0A]">
                                <?= htmlspecialchars($adj) ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <p class="font-['Fira_Sans'] text-xs text-[#9b9186]">
                        A number is added to keep your name your own.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="relative flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-60 h-auto drop-shadow-md">
                        <span class="absolute text-[#FAEAC9] text-xl tracking-widest uppercase transition-colors">
                            Take It
                        </span>
                    </button>
                </div>

            </form>

            <?php endif; ?>

        </div>
    </main>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>