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

if (isLeader()) {
    header('Location: profile.php');
    exit;
}

$trust     = (int)$user['trust_index'];
$eligible  = $trust >= TRUST_THRESHOLD;
$error     = null;

//each animal has adjectoivwes from the first letter
//i like it like this
//preset and dynamically chnages on screen
$adjectivesByAnimal = [
    'bat'       => ['Bitter', 'Blighted', 'Broken', 'Buried', 'Brittle', 'Bleak', 'Burnt', 'Bound', 'Blind', 'Barren'],
    'bee'       => ['Bygone', 'Bloodless', 'Beckoning', 'Banished', 'Belated', 'Bereft', 'Bowed', 'Braided', 'Bristling', 'Beguiled'],
    'seal'      => ['Silent', 'Sunken', 'Sullen', 'Solemn', 'Shrouded', 'Sorrowed', 'Salted', 'Stilled', 'Severed', 'Shivering'],
    'frog'      => ['Fallow', 'Forsaken', 'Fevered', 'Faded', 'Fickle', 'Furrowed', 'Frayed', 'Faithless', 'Forgotten', 'Fleeting'],
    'llama'     => ['Lonesome', 'Lowly', 'Listless', 'Lurking', 'Languid', 'Lamented', 'Loathsome', 'Lucid', 'Lulled', 'Lightless'],
    'turtle'    => ['Tarnished', 'Tireless', 'Trembling', 'Twisted', 'Tethered', 'Thankless', 'Toiling', 'Tattered', 'Tolling', 'Timeworn'],
    'bear'      => ['Brooding', 'Bellowing', 'Bruised', 'Baneful', 'Bloodshot', 'Burdened', 'Bygone', 'Brazen', 'Bramble', 'Blackened'],
    'panda'     => ['Pallid', 'Penitent', 'Plodding', 'Parched', 'Pining', 'Prowling', 'Petrified', 'Patient', 'Peculiar', 'Perished'],
    'capybara'  => ['Calm', 'Callous', 'Contrite', 'Cavernous', 'Ceaseless', 'Craven', 'Crestfallen', 'Coiled', 'Consumed', 'Cloistered'],
    'crocodile' => ['Cruel', 'Creeping', 'Cunning', 'Croaking', 'Chastened', 'Churning', 'Coldblooded', 'Clawed', 'Condemned', 'Circling'],
];

$defaultAdjectives = ['Silent', 'Hollow', 'Weeping', 'Grim', 'Ashen', 'Sullen', 'Restless', 'Wicked', 'Quiet', 'Shrouded'];

function adjectivesFor(string $animalName, array $sets, array $fallback): array {
    return $sets[strtolower($animalName)] ?? $fallback;
}

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

     
        //random number genrated 
        //number after nam egets generated
        //loop[ tries to give random 2 dig numbner]
        //prevent username clash
        $stmt = $pdo->prepare(
            'SELECT animal_name FROM animal_avatars
             WHERE avatar_id = :id AND min_trust <= :trust'
        );
        $stmt->execute(['id' => $avatarId, 'trust' => $trust]);
        $animal = $stmt->fetch();

        if (!$animal) {
            $error = 'That animal is not yours to take.';
        } elseif (!in_array($adjective, adjectivesFor($animal['animal_name'], $adjectivesByAnimal, $defaultAdjectives), true)) {
            $error = 'That name does not belong to that face.';
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
    <title>Shed Your Silence - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }

        .animal-pick:hover .card-edge { border-color: #7A0A0A; }
        .animal-pick input:checked ~ .animal-card .card-edge {
            border-color: #E11C25;
            background: rgba(122, 10, 10, 0.35);
        }

        .adj-pick .ribbon-on  { opacity: 0; }
        .adj-pick .ribbon-off { opacity: 1; }
        .adj-pick input:checked ~ .ribbon-wrap .ribbon-on  { opacity: 1; }
        .adj-pick input:checked ~ .ribbon-wrap .ribbon-off { opacity: 0; }
        .adj-pick input:checked ~ .ribbon-wrap .ribbon-label { color: #121110; }
        .adj-pick .ribbon-wrap {
            transition: transform 0.2s ease;
        }
        .adj-pick:hover .ribbon-wrap {
            transform: scale(1.05);
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
        <div class="w-full max-w-4xl mx-auto flex flex-col gap-8">

            <header class="flex justify-end items-center border-b border-[#FAEAC9] pb-3 mb-6 md:mb-8">
                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>

            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h1 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Shed Your Silence</h1>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
            </div>

            <p class="text-center font-['Fira_Sans'] text-sm text-[#9b9186] max-w-xl mx-auto">
                You have confessed as <span class="font-['Eczar'] uppercase tracking-wide text-[#E11C25]"><?= htmlspecialchars($user['anon_handle']) ?></span>.
                Trust is earned when others believe you. Gather enough of it and you may take a face and a name of your own.
            </p>

            <?php
            $segments = 20;
            $filledSegments = (int)floor(min(1, $trust / TRUST_THRESHOLD) * $segments);
            ?>
            <div class="flex flex-col gap-2">
                <div class="flex justify-between font-['Fira_Sans'] text-sm uppercase tracking-widest text-[#9b9186]">
                    <span>Trust</span>
                    <span><span class="text-[#FAEAC9]"><?= $trust ?></span> / <?= TRUST_THRESHOLD ?></span>
                </div>
                <div class="relative p-1.5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-lg rough-border pointer-events-none"></div>
                    <div class="relative flex gap-1 h-6">
                        <?php for ($i = 0; $i < $segments; $i++): ?>
                        <div class="flex-1 rounded-sm <?= $i < $filledSegments ? 'bg-[#E11C25]' : 'bg-[#1c1a18]' ?>"></div>
                        <?php endfor; ?>
                    </div>
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
                    You are still a stranger here.
                    <span class="text-[#FAEAC9]"><?= TRUST_THRESHOLD - $trust ?></span> more trust and a face will be yours to take.
                </p>
            </div>

            <?php else: ?>

            <form method="POST" action="claim-profile.php" class="flex flex-col gap-8">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">

                <div class="flex flex-col gap-4">
                    <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest">Choose your face</h2>

                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 sm:gap-4">
                        <?php foreach ($available as $i => $animal): ?>
                        <label class="animal-pick cursor-pointer">
                            <input type="radio" name="avatar_id" value="<?= $animal['avatar_id'] ?>"
                                   data-animal="<?= htmlspecialchars(strtolower($animal['animal_name'])) ?>"
                                   class="sr-only" <?= $i === 0 ? 'checked' : '' ?> required>
                            <div class="animal-card relative p-3">
                                <div class="card-edge absolute inset-0 border-[3px] border-[#3a332c] rounded-xl rough-border pointer-events-none transition-colors"></div>
                                <div class="relative flex flex-col items-center gap-2">
                                    <div class="w-16 h-16 rounded-full overflow-hidden bg-[#1c1a18] p-1">
                                        <img src="<?= BASE_URL ?>assets/images/animals/<?= htmlspecialchars($animal['filename']) ?>"
                                             alt="<?= htmlspecialchars($animal['animal_name']) ?>"
                                             class="w-full h-full object-contain"
                                             onerror="this.style.display='none'">
                                    </div>
                                    <span class="text-base uppercase tracking-wide text-[#9b9186] text-center leading-none w-full truncate">
                                        <?= htmlspecialchars($animal['animal_name']) ?>
                                    </span>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($locked)): ?>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 sm:gap-4 opacity-30 pointer-events-none mt-2">
                        <?php foreach ($locked as $animal): ?>
                        <div class="relative p-3">
                            <div class="absolute inset-0 border-[3px] border-[#3a332c] rounded-xl rough-border pointer-events-none"></div>
                            <div class="relative flex flex-col items-center gap-2">
                                <div class="w-16 h-16 rounded-full overflow-hidden bg-[#1c1a18] p-1 grayscale">
                                    <img src="<?= BASE_URL ?>assets/images/animals/<?= htmlspecialchars($animal['filename']) ?>"
                                         alt="" class="w-full h-full object-contain" onerror="this.style.display='none'">
                                </div>
                                <span class="text-base uppercase tracking-wide text-[#9b9186] text-center leading-none w-full truncate">
                                    <?= (int)$animal['min_trust'] ?> trust
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-4">
                    <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest">Choose your name</h2>

                    <?php
                    $firstAnimal = $available[0]['animal_name'] ?? '';
                    foreach ($available as $animal):
                        $animalKey = strtolower($animal['animal_name']);
                        $isFirst   = $animalKey === strtolower($firstAnimal);
                        $adjSet    = adjectivesFor($animal['animal_name'], $adjectivesByAnimal, $defaultAdjectives);
                    ?>
                    <div class="adjective-set grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 sm:gap-4 <?= $isFirst ? '' : 'hidden' ?>"
                         data-animal="<?= htmlspecialchars($animalKey) ?>">
                        <?php foreach ($adjSet as $i => $adj): ?>
                        <label class="adj-pick cursor-pointer">
                            <input type="radio" name="adjective" value="<?= htmlspecialchars($adj) ?>"
                                   class="sr-only" <?= $isFirst && $i === 0 ? 'checked' : '' ?>
                                   <?= $isFirst ? '' : 'disabled' ?> required>
                            <span class="ribbon-wrap relative flex items-center justify-center w-full">
                                <img src="<?= BASE_URL ?>assets/images/CryptInactiveButton.png" alt=""
                                     class="ribbon-off w-full h-auto drop-shadow-md transition-opacity">
                                <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt=""
                                     class="ribbon-on absolute inset-0 w-full h-auto drop-shadow-md transition-opacity">
                                <span class="ribbon-label absolute font-['Fira_Sans'] text-[#3a332c] text-sm tracking-wide uppercase transition-colors px-2 text-center leading-none truncate max-w-full">
                                    <?= htmlspecialchars($adj) ?>
                                </span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                    <p class="font-['Fira_Sans'] text-sm text-[#72685F]">
                        Your name is bound to the face you choose. A number is added to keep it your own.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="relative flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
                        <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt="" class="w-60 h-auto drop-shadow-md">
                        <span class="absolute text-[#121110] text-xl tracking-widest uppercase transition-colors">
                            Take Your Name
                        </span>
                    </button>
                </div>

            </form>

            <?php endif; ?>

        </div>
    </main>

    <script>
        (function () {
            var animalInputs = document.querySelectorAll('input[name="avatar_id"]');
            var sets = document.querySelectorAll('.adjective-set');
            if (!animalInputs.length || !sets.length) return;

            function showSetFor(animal) {
                sets.forEach(function (set) {
                    var matches = set.dataset.animal === animal;
                    set.classList.toggle('hidden', !matches);

                    var radios = set.querySelectorAll('input[name="adjective"]');
                    radios.forEach(function (radio, i) {
                        radio.disabled = !matches;
                        if (matches && i === 0) radio.checked = true;
                    });
                });
            }

            animalInputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    if (input.checked) showSetFor(input.dataset.animal);
                });
            });
        })();
    </script>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>