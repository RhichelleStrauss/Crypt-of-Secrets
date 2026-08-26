
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Leader - Crypt of Secrets</title>

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

    

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-8 py-10">
        <div class="w-full max-w-4xl mx-auto flex flex-col gap-6">

            <div class="flex items-center justify-center gap-4 md:gap-8 mb-2">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-10 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-4xl tracking-widest uppercase">The Leader</h1>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-10 object-contain">
            </div>

            <div class="flex justify-center gap-8 font-['Fira_Sans'] text-sm text-[#9b9186] border-b border-[#72685F] pb-5">
                <span><span class="text-[#E11C25] text-lg"><?= $counts['pending'] ?? 0 ?></span> awaiting</span>
                <span><span class="text-[#FAEAC9] text-lg"><?= $counts['approved'] ?? 0 ?></span> admitted</span>
                <span><span class="text-[#72685F] text-lg"><?= $counts['rejected'] ?? 0 ?></span> denied</span>
            </div>

           
            <div class="border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3">
                <p class="font-['Fira_Sans'] text-sm text-[#FAEAC9]"><?= htmlspecialchars($notice) ?></p>
            </div>
           
            <div class="relative p-10 text-center">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                <p class="relative font-['Fira_Sans'] text-sm text-[#9b9186]">Nothing awaits judgement.</p>
            </div>
          
            <div class="relative p-6">
                <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>

                <div class="relative flex flex-col gap-4">

                    <div class="flex items-center justify-between font-['Fira_Sans'] text-xs text-[#9b9186]">
                        <div class="flex items-center gap-3">
                            <span class="text-[#FAEAC9]"><?= htmlspecialchars($post['anon_handle']) ?></span>
                            <span>trust <?= (int)$post['trust_index'] ?></span>
                        </div>
                        <span><?= timeAgo($post['created_at']) ?></span>
                    </div>

                    <h2 class="text-[#FAEAC9] uppercase text-lg tracking-widest"><?= htmlspecialchars($post['title']) ?></h2>

                    <p class="font-['Fira_Sans'] text-sm text-[#e4d5b7] leading-relaxed whitespace-pre-line"><?= htmlspecialchars($post['content']) ?></p>

                    <div class="flex gap-3 justify-end pt-2">

                        <form method="POST" action="leader.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <input type="hidden" name="decision" value="rejected">
                            <button type="submit"
                                class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186] border border-[#3a332c] hover:border-[#9b9186] hover:text-[#e4d5b7] rounded-full px-5 py-2 transition-colors">
                                Deny
                            </button>
                        </form>

                        <form method="POST" action="leader.php">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                            <input type="hidden" name="decision" value="approved">
                            <button type="submit"
                                class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#FAEAC9] border border-[#7A0A0A] bg-[#7A0A0A]/30 hover:bg-[#7A0A0A]/60 rounded-full px-5 py-2 transition-colors">
                                Admit
                            </button>
                        </form>

                    </div>
                </div>
            </div>
          

        </div>
    </main>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>

</body>
</html>