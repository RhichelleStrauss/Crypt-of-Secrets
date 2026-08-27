<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="/crypt-of-secrets/dist/output.css" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        .rough-border { filter: url(#rough-border); }
        .tab-active {
            border-bottom: 2px solid #E11C25;
            color: #FAEAC9;
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

  

    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-8 py-10">
        <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8 items-start">

            <div class="flex flex-col gap-6">

                <div class="flex items-center justify-between border-b border-[#72685F] pb-6">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 rounded-full border-2 border-[#7A0A0A] overflow-hidden shrink-0 bg-[#1c1a18] p-1">
                            <img src="/crypt-of-secrets/assets/images/animals/Temp_CowIcon.webp" alt="Profile" class="w-full h-full object-contain">
                        </div>
                        <div class="flex flex-col gap-1">
                            <h1 class="text-[#FAEAC9] text-2xl tracking-widest uppercase">AshenCow45</h1>
                            <span class="font-['Fira_Sans'] text-sm text-[#9b9186]">Member since Aug 2026</span>
                        </div>
                    </div>

                    <a href="logout.php"
                       class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186] hover:text-[#E11C25] transition-colors border border-[#3a332c] hover:border-[#E11C25] rounded-full px-4 py-2">
                        Log out
                    </a>
                </div>

                <nav class="flex gap-6 border-b border-[#72685F] font-['Fira_Sans'] uppercase text-sm tracking-widest">
                    <a href="#" class="tab-active pb-3 transition-colors">Overview</a>
                    <a href="#" class="text-[#9b9186] hover:text-[#FAEAC9] pb-3 transition-colors">Posts</a>
                    <a href="#" class="text-[#9b9186] hover:text-[#FAEAC9] pb-3 transition-colors">True</a>
                    <a href="#" class="text-[#9b9186] hover:text-[#FAEAC9] pb-3 transition-colors">False</a>
                </nav>

                <div class="flex flex-col gap-3">

                    <div class="relative p-4">
                        <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none bg-[#121110] opacity-90"></div>
                        <div class="relative flex flex-col gap-2">
                            <div class="flex items-center gap-2 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="text-[#FAEAC9]">AshenCow45</span>
                                <span>confessed</span>
                                <span>19 minutes ago</span>
                            </div>
                            <p class="font-['Fira_Sans'] text-sm text-[#e4d5b7]">Stole a stapler from the office three years ago and still use it every day.</p>
                            <div class="flex items-center gap-5 mt-1 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="text-[#E11C25]">89% true</span>
                                <span>142 votes</span>
                                <span>3 awards</span>
                            </div>
                        </div>
                    </div>

                    <div class="relative p-4">
                        <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none bg-[#121110] opacity-90"></div>
                        <div class="relative flex flex-col gap-2">
                            <div class="flex items-center gap-2 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="text-[#FAEAC9]">AshenCow45</span>
                                <span>confessed</span>
                                <span>2 days ago</span>
                            </div>
                            <p class="font-['Fira_Sans'] text-sm text-[#e4d5b7]">Told my neighbor their dog was cute. It was not.</p>
                            <div class="flex items-center gap-5 mt-1 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span>64% true</span>
                                <span>58 votes</span>
                                <span>0 awards</span>
                            </div>
                        </div>
                    </div>

                    <div class="relative p-4">
                        <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none bg-[#121110] opacity-90"></div>
                        <div class="relative flex flex-col gap-2">
                            <div class="flex items-center gap-2 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="text-[#FAEAC9]">AshenCow45</span>
                                <span>confessed</span>
                                <span>2 months ago</span>
                            </div>
                            <p class="font-['Fira_Sans'] text-sm text-[#e4d5b7]">Ate the last slice and blamed my roommate's cat.</p>
                            <div class="flex items-center gap-5 mt-1 font-['Fira_Sans'] text-xs text-[#9b9186]">
                                <span class="text-[#E11C25]">95% true</span>
                                <span>301 votes</span>
                                <span>7 awards</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <aside class="flex flex-col gap-5 sticky top-10">

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-4">

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]">87</span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">Trust index</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]">42</span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">Confessions</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]">312</span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">True votes</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-xl text-[#FAEAC9]">Aug 2026</span>
                                <span class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#9b9186]">Joined</span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="relative p-5">
                    <div class="absolute inset-0 border-[3px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
                    <div class="relative flex flex-col gap-3">
                        <span class="font-['Fira_Sans'] uppercase text-xs tracking-widest text-[#9b9186]">Tarot cards</span>

                        <div class="flex gap-2 flex-wrap">
                            <div class="w-10 h-14 rounded overflow-hidden bg-[#1c1a18] border border-[#7A0A0A]">
                                <img src="/crypt-of-secrets/assets/images/tarot/TempTarot_TheConfessorsMark.png" alt="The Confessor's Mark" class="w-full h-full object-cover">
                            </div>
                            <div class="w-10 h-14 rounded overflow-hidden bg-[#1c1a18] border border-[#7A0A0A]">
                                <img src="/crypt-of-secrets/assets/images/tarot/TempTarot_WhisperedTruth.png" alt="Whispered Truth" class="w-full h-full object-cover">
                            </div>
                            <div class="w-10 h-14 rounded overflow-hidden bg-[#1c1a18] border border-[#7A0A0A]">
                                <img src="/crypt-of-secrets/assets/images/tarot/TempTarot_TheToll.png" alt="The Toll" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <span class="font-['Fira_Sans'] text-xs text-[#9b9186]">3 unlocked</span>

                        <a href="awards.php" class="font-['Fira_Sans'] text-xs uppercase tracking-widest text-[#FAEAC9] hover:text-[#E11C25] transition-colors self-start">
                            View all
                        </a>
                    </div>
                </div>

            </aside>

        </div>
    </main>

    <script type="module" src="/crypt-of-secrets/assets/js/ferrofluid.js"></script>

</body>
</html>