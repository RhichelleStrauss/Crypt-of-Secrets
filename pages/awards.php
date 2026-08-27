<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

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

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }

        .card-scene {
            width: 210px;
            height: 330px;
            perspective: 900px;
            cursor: pointer;
        }
        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 0.65s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-scene.flipped .card-inner {
            transform: rotateY(180deg);
        }
        .card-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            border-radius: 10px;
            overflow: hidden;
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
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 1.5px solid #7A0A0A;
        }
        .piece-pip.filled {
            background: #7A0A0A;
        }
        .card-scene:not(.card-locked):hover {
            filter: drop-shadow(0 0 10px rgba(200, 16, 46, 0.6));
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
        <div class="w-full max-w-5xl mx-auto flex flex-col gap-10">

            <header class="flex justify-between items-end border-b border-[#72685F] pb-4">
                <div>
                    <h1 class="text-[#FAEAC9] text-3xl tracking-widest uppercase">Awards</h1>
                    <p class="font-['Fira_Sans'] text-sm text-[#9b9186] mt-1">Collect 4 pieces to assemble a card. Flip to reveal its power.</p>
                </div>
                <div class="flex items-center gap-3 font-['Fira_Sans'] text-sm text-[#9b9186]">
                    <span><span class="text-[#FAEAC9] text-lg"><?= $unlockedCount ?></span> / <?= count($cards) ?> unlocked</span>
                </div>
            </header>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-8 gap-y-10 justify-items-center">
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
                                    <span class="font-['Fira_Sans'] text-[#4a423b] text-xs uppercase tracking-widest">Locked</span>
                                </div>
                                <?php elseif ($card['quantity'] > 0): ?>
                                <div class="absolute top-2 right-2 bg-[#7A0A0A] text-[#FAEAC9] font-['Fira_Sans'] text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                                    <?= (int)$card['quantity'] ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-face card-back-face">
                                <?php if (!empty($card['back_filename'])): ?>
                                <img src="<?= BASE_URL ?>assets/tarot/<?= htmlspecialchars($card['back_filename']) ?>"
                                     alt="<?= htmlspecialchars($card['tarot_name']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.parentElement.style.background='#141210'">
                                <?php else: ?>
                                <div class="w-full h-full bg-[#141210] border-2 border-[#7A0A0A] flex flex-col items-center justify-center px-3 py-5 text-center">
                                    <h3 class="text-[#F4E9C9] text-sm leading-tight mb-3"><?= htmlspecialchars($card['tarot_name']) ?></h3>
                                    <p class="font-['Fira_Sans'] text-[#c9b98f] text-[13px] leading-relaxed mb-3"><?= htmlspecialchars($card['effect_text']) ?></p>
                                    <span class="font-['Fira_Sans'] text-[9px] tracking-[2px] text-[#7A0A0A] border border-[#7A0A0A] rounded-full px-3 py-0.5 uppercase">
                                        <?= durationLabel($card['buff_duration'] === null ? null : (int)$card['buff_duration']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                      
                            </div>

                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-1.5">
                        <span class="font-['Eczar'] text-[15px] text-[#e4d5b7] text-center leading-tight max-w-[160px] tracking-wide">
                            <?= htmlspecialchars($card['tarot_name']) ?>
                        </span>

                        <?php if ($card['unlocked'] && (int)$card['quantity'] > 0): ?>
                        <a href="home.php"
                           class="relative flex items-center justify-center group bg-transparent border-none cursor-pointer mt-1 transition-transform duration-300 ease-out hover:scale-110">
                            <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt=""
                                 class="h-9 w-auto object-contain">
                            <span class="absolute inset-0 flex items-center justify-center text-[#eaddc5] group-hover:text-white text-[10px] tracking-widest uppercase transition-colors drop-shadow-md pointer-events-none">
                                Use (<?= (int)$card['quantity'] ?>)
                            </span>
                        </a>
                        <?php endif; ?>

                        <?php if (!$card['unlocked']): ?>
                        <div class="flex gap-1.5 items-center">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="piece-pip <?= $i < (int)$card['pieces'] ? 'filled' : '' ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <span class="font-['Fira_Sans'] text-[10px] text-[#4a423b]">
                            <?= (int)$card['pieces'] ?> / 4 pieces
                        </span>
                        <?php else: ?>
                        <span class="font-['Fira_Sans'] text-[10px] text-[#7A0A0A] uppercase tracking-widest">
                            Unlocked
                        </span>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <div class="border-t border-[#72685F] pt-6 flex flex-col gap-3">
                <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest">Piece collection</h2>
                <p class="font-['Fira_Sans'] text-sm text-[#9b9186]">
                    Earn pieces by passing judgement on confessions. Every vote carries a chance
                    of a fragment. Gather all four of a card to assemble it.
                </p>
                <span class="font-['Fira_Sans'] text-xs text-[#9b9186] mt-1">
                    <?= $loosePieces ?> <?= $loosePieces === 1 ? 'fragment' : 'fragments' ?> held
                </span>
            </div>

        </div>
    </main>

    <script>
    function flipCard(el) {
        el.classList.toggle('flipped');
    }

    </script>
    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>