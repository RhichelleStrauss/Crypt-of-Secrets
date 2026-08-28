<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

function viewUrl(string $sort, string $filter): string {
    return 'awards.php?sort=' . urlencode($sort) . '&filter=' . urlencode($filter);
}

$user = currentUser($pdo);

$stmt = $pdo->prepare(
    'SELECT
        t.tarot_id, t.tarot_name, t.icon_filename, t.back_filename,
        t.effect_text, t.buff_duration, t.rarity,
        COALESCE(ac.quantity, 0) AS quantity,
        (ac.collection_id IS NOT NULL) AS unlocked,
        (SELECT COUNT(DISTINCT piece_number)
           FROM user_tarot_pieces up
          WHERE up.user_id = :uid AND up.tarot_id = t.tarot_id) AS pieces
     FROM tarot_card_buffs t
     LEFT JOIN award_collection ac
            ON ac.tarot_id = t.tarot_id AND ac.user_id = :uid2
     ORDER BY t.tarot_id'
);
$stmt->execute(['uid' => $user['user_id'], 'uid2' => $user['user_id']]);
$cards = $stmt->fetchAll();

$unlockedCount = 0;
foreach ($cards as $c) {
    if ($c['unlocked']) $unlockedCount++;
}
$totalCards = count($cards);

$sortOptions = [
    'default'  => 'Default',
    'name'     => 'Name (A–Z)',
    'rarity'   => 'Rarity',
    'duration' => 'Buff duration',
    'pieces'   => 'Pieces collected',
];

$filterOptions = [
    'all'      => 'All cards',
    'unlocked' => 'Owned',
    'locked'   => 'Not owned',
];

$sort   = array_key_exists($_GET['sort']   ?? '', $sortOptions)   ? $_GET['sort']   : 'default';
$filter = array_key_exists($_GET['filter'] ?? '', $filterOptions) ? $_GET['filter'] : 'all';

if ($filter === 'unlocked') {
    $cards = array_values(array_filter($cards, fn($c) => (bool)$c['unlocked']));
} elseif ($filter === 'locked') {
    $cards = array_values(array_filter($cards, fn($c) => !$c['unlocked']));
}

usort($cards, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'name':
            return strcasecmp($a['tarot_name'], $b['tarot_name']);
        case 'rarity':
            return $b['rarity'] <=> $a['rarity'];
        case 'duration':
            return ($a['buff_duration'] ?? 0) <=> ($b['buff_duration'] ?? 0);
        case 'pieces':
            return $b['pieces'] <=> $a['pieces'];
        default:
            return 0; // usort is stable since PHP 8.0 - keeps the curated tarot_id order
    }
});

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM user_tarot_pieces WHERE user_id = :uid'
);
$stmt->execute(['uid' => $user['user_id']]);
$loosePieces = (int)$stmt->fetchColumn();

function durationLabel(?int $minutes): string {
    if ($minutes === null) return 'Instant';
    if ($minutes < 60)     return $minutes . ' minutes';
    $hours = $minutes / 60;
    return rtrim(rtrim(number_format($hours, 1), '0'), '.') . ' hours';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awards - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <?php
    $preloadedBacks = [];
    foreach ($cards as $preloadCard) {
        if ($preloadCard['unlocked'] && !empty($preloadCard['back_filename'])
            && !in_array($preloadCard['back_filename'], $preloadedBacks, true)) {
            $preloadedBacks[] = $preloadCard['back_filename'];
        }
    }
    ?>
    <?php foreach ($preloadedBacks as $backFile): ?>
    <link rel="preload" as="image" href="<?= BASE_URL ?>assets/tarot/small/<?= htmlspecialchars($backFile) ?>">
    <?php endforeach; ?>

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }

        .menu > summary {
            list-style: none;
            cursor: pointer;
        }
        .menu > summary::-webkit-details-marker { display: none; }
        .menu[open] > summary { color: #E11C25; }
        @media (max-width: 767px) {
            .feed-header { justify-content: flex-end; }
            .menu-panel {
                left: auto;
                right: 0;
            }
        }

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

        .card-scene {
            width: 180px;
            height: 283px;
            perspective: 900px;
            -webkit-perspective: 900px;
            cursor: pointer;
        }
        @media (min-width: 640px) {
            .card-scene { width: 210px; height: 330px; }
        }
        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            -webkit-transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }
        .card-scene.flipped .card-inner {
            transform: rotateY(180deg);
        }
        .card-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
            border-radius: 10px;
            overflow: hidden;
            transform: translateZ(0);
        }
        .card-back-face {
            transform: rotateY(180deg);
        }
        .card-locked .card-inner {
            filter: brightness(0.35) saturate(0.2);
            cursor: default;
        }
        .card-locked:hover .card-inner {
            filter: brightness(0.4) saturate(0.25);
        }
        .piece-pip {
            width: 16px;
            height: 16px;
            object-fit: contain;
        }
        .card-scene:not(.card-locked):hover {
            box-shadow: 0 0 12px 2px rgba(225, 28, 37, 0.7), 0 0 40px 12px rgba(122, 10, 10, 0.9);
            border-radius: 10px;
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
        <div class="w-full max-w-5xl mx-auto flex flex-col gap-10">

            <header class="feed-header flex flex-wrap gap-3 justify-between items-center border-b border-[#FAEAC9] pb-3">

                <div class="flex items-center gap-4 sm:gap-5 text-[#FAEAC9] uppercase text-lg sm:text-xl md:text-2xl tracking-wide">

                    <details class="menu relative" id="filterMenu">
                        <summary class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">FILTER</summary>
                        <div class="menu-panel absolute left-0 top-full mt-2 z-40 w-60">
                            <div class="absolute inset-0 bg-[#4d4d4d]/60 backdrop-blur-sm border-[3px] border-[#7A0A0A] rounded-lg rough-border pointer-events-none"></div>
                            <div class="relative z-10 py-2">
                                <?php foreach ($filterOptions as $key => $label): ?>
                                <a href="<?= viewUrl($sort, $key) ?>"
                                   class="dropdown-link block px-4 py-2 font-['Fira_Sans'] text-sm normal-case tracking-normal transition-colors <?= $filter === $key ? 'text-[#E11C25] font-bold' : 'text-[#e4d5b7] hover:text-[#E11C25]' ?>">
                                    <?= htmlspecialchars($label) ?>
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
                                <?php foreach ($sortOptions as $key => $label): ?>
                                <a href="<?= viewUrl($key, $filter) ?>"
                                   class="dropdown-link block px-4 py-2 font-['Fira_Sans'] text-sm normal-case tracking-normal transition-colors <?= $sort === $key ? 'text-[#E11C25] font-bold' : 'text-[#e4d5b7] hover:text-[#E11C25]' ?>">
                                    <?= htmlspecialchars($label) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>

                </div>

                <?php include ROOT_PATH . 'components/icon-row.php'; ?>
            </header>

            <div class="flex flex-col items-center gap-3">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h1 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Awards</h1>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
                <p class="font-['Fira_Sans'] text-base text-[#72685F] text-center">Collect 4 pieces to assemble a card. Flip to reveal its power.</p>
                <span class="font-['Fira_Sans'] text-base text-[#72685F]">
                    <span class="text-[#FAEAC9] text-xl"><?= $unlockedCount ?></span> / <?= $totalCards ?> unlocked
                </span>
            </div>

            <?php if ($sort !== 'default' || $filter !== 'all'): ?>
            <div class="flex items-center gap-3 font-['Fira_Sans'] text-base text-[#72685F] -mt-6">
                <span><?= htmlspecialchars($filterOptions[$filter]) ?>, sorted by <?= strtolower($sortOptions[$sort]) ?></span>
                <a href="awards.php" class="text-[#E11C25] hover:text-[#FAEAC9] transition-colors">clear</a>
            </div>
            <?php endif; ?>

            <?php if (empty($cards)): ?>
            <div class="relative p-10 text-center">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-base text-[#72685F]">Nothing matches that filter.</p>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 sm:gap-x-8 gap-y-8 sm:gap-y-10 justify-items-center">
                <?php foreach ($cards as $card): ?>
                <div class="flex flex-col items-center gap-3">

                    <div class="card-scene <?= !$card['unlocked'] ? 'card-locked' : '' ?>"
                         id="card-<?= $card['tarot_id'] ?>"
                         <?= $card['unlocked'] ? 'onclick="flipCard(this)"' : '' ?>>
                        <div class="card-inner">

                            <div class="card-face">
                                <img src="<?= BASE_URL ?>assets/tarot/<?= htmlspecialchars($card['icon_filename']) ?>"
                                     alt="<?= htmlspecialchars($card['tarot_name']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.parentElement.style.background='#1c1a18'">

                                <?php if (!$card['unlocked']): ?>
                                <div class="absolute inset-0 flex items-end justify-center pb-4">
                                    <span class="font-['Fira_Sans'] text-[#3a332c] text-sm uppercase tracking-widest">Locked</span>
                                </div>
                                <?php elseif ($card['quantity'] > 0): ?>
                                <div class="absolute top-2 right-2 bg-[#7A0A0A] text-[#FAEAC9] font-['Fira_Sans'] text-sm rounded-full w-6 h-6 flex items-center justify-center font-bold">
                                    <?= (int)$card['quantity'] ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-face card-back-face">
                                <?php if (!empty($card['back_filename'])): ?>
                                <img src="<?= BASE_URL ?>assets/tarot/small/<?= htmlspecialchars($card['back_filename']) ?>"
                                     alt="<?= htmlspecialchars($card['tarot_name']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.parentElement.style.background='#1c1a18'">
                                <?php else: ?>
                                <div class="w-full h-full bg-[#1c1a18] border-2 border-[#7A0A0A] flex flex-col items-center justify-center px-3 py-5 text-center">
                                    <h3 class="text-[#FAEAC9] text-base leading-tight mb-3"><?= htmlspecialchars($card['tarot_name']) ?></h3>
                                    <p class="font-['Fira_Sans'] text-[#c9b98f] text-sm leading-relaxed mb-3"><?= htmlspecialchars($card['effect_text']) ?></p>
                                    <span class="font-['Fira_Sans'] text-xs tracking-[2px] text-[#7A0A0A] border border-[#7A0A0A] rounded-full px-3 py-0.5 uppercase">
                                        <?= durationLabel($card['buff_duration'] === null ? null : (int)$card['buff_duration']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                      
                            </div>

                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-1.5">
                        <span class="font-['Eczar'] text-lg text-[#e4d5b7] text-center leading-tight max-w-[180px] tracking-wide">
                            <?= htmlspecialchars($card['tarot_name']) ?>
                        </span>

                        <?php if ($card['unlocked'] && (int)$card['quantity'] > 0): ?>
                        <a href="analytics.php?tab=buffs"
                           class="relative flex items-center justify-center group bg-transparent border-none cursor-pointer mt-1 transition-transform duration-300 ease-out hover:scale-110">
                            <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt=""
                                 class="h-9 w-auto object-contain">
                            <span class="absolute inset-0 flex items-center justify-center text-[#121110] group-hover:text-[#FAEAC9] text-sm tracking-widest uppercase transition-colors drop-shadow-md pointer-events-none">
                                Use (<?= (int)$card['quantity'] ?>)
                            </span>
                        </a>
                        <?php endif; ?>

                        <?php if (!$card['unlocked']): ?>
                        <div class="flex gap-1.5 items-center">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <img src="<?= BASE_URL ?>assets/images/icons/<?= $i < (int)$card['pieces'] ? 'FilledDiamondIcon.png' : 'EmptyDiamondIcon.png' ?>"
                                 alt="" class="piece-pip">
                            <?php endfor; ?>
                        </div>
                        <span class="font-['Fira_Sans'] text-sm text-[#72685F]">
                            <?= (int)$card['pieces'] ?> / 4 pieces
                        </span>
                        <?php else: ?>
                        <span class="font-['Fira_Sans'] text-sm font-bold text-[#7A0A0A] uppercase tracking-widest">
                            Unlocked
                        </span>
                        <?php if ((int)$card['pieces'] > 0): ?>
                        <div class="flex gap-1.5 items-center mt-1">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <img src="<?= BASE_URL ?>assets/images/icons/<?= $i < (int)$card['pieces'] ? 'FilledDiamondIcon.png' : 'EmptyDiamondIcon.png' ?>"
                                 alt="" class="piece-pip">
                            <?php endfor; ?>
                        </div>
                        <span class="font-['Fira_Sans'] text-sm text-[#72685F]">
                            <?= (int)$card['pieces'] ?> / 4 toward next copy
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col items-center gap-3 border-t border-[#72685F] pt-8">
                <div class="flex items-center justify-center gap-4 md:gap-8">
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain">
                    <h2 class="text-[#FAEAC9] text-2xl sm:text-3xl md:text-4xl tracking-widest uppercase text-center">Piece Collection</h2>
                    <img src="<?= BASE_URL ?>assets/images/CryptDoubeLineBeige.png" alt="" class="h-4 sm:h-6 md:h-8 object-contain scale-x-[-1]">
                </div>
                <img src="<?= BASE_URL ?>assets/images/LongEyeLine.png" alt="" class="w-full max-w-[220px] h-auto drop-shadow-md">
                <p class="font-['Fira_Sans'] text-base text-[#72685F] text-center max-w-xl">
                    Earn pieces by passing judgement on confessions. Every vote carries a chance
                    of a fragment. Gather all four of a card to assemble it.
                </p>
                <span class="font-['Fira_Sans'] text-base text-[#72685F]">
                    <?= $loosePieces ?> <?= $loosePieces === 1 ? 'fragment' : 'fragments' ?> held
                </span>
            </div>

        </div>
    </main>

    <script>
    function flipCard(el) {
        el.classList.toggle('flipped');
    }

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
    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>