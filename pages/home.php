<?php require_once __DIR__ . '/../includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Crypt of Secrets</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }

               .glow-wrap {
            cursor: pointer;
        }
        .glow-wrap .glow-item {
            transition: filter 0.3s ease, transform 0.3s ease;
        }
        .glow-wrap:hover .glow-item {
            
            filter: drop-shadow(0 0 12px rgb(255, 28, 37)); 
            transform: scale(1.08) translateY(-2px);
        }
        .glow-wrap:hover span:not(.glow-item) {
            color: #E11C25;
            
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
    <filter id="liquid-distort" x="-50%" y="-50%" width="200%" height="200%" color-interpolation-filters="sRGB">
         <feTurbulence type="fractalNoise" baseFrequency="0.010" numOctaves="3" stitchTiles="stitch" x="0" y="0" width="2000" height="2000" result="noise" />
        
        <feOffset id="js-offset" in="noise" dx="0" dy="0" result="movedNoise" />
        
        <feTile in="movedNoise" result="tiledNoise" />
        
        
        <feDisplacementMap in="SourceGraphic" in2="tiledNoise" scale="250" xChannelSelector="R" yChannelSelector="G" />
    </filter>

    <filter id="rough-border" color-interpolation-filters="sRGB">
    <feTurbulence type="fractalNoise" baseFrequency="0.4" numOctaves="3" result="noise" />
    <feDisplacementMap in="SourceGraphic" in2="noise" scale="4" xChannelSelector="R" yChannelSelector="G" />
</filter>
</svg>

    <?php include ROOT_PATH . 'components/sidenav.php'; ?>

   
   <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-8 py-6">

    <div class="w-full max-w-4xl mx-auto flex flex-col gap-3">

        <header class="flex justify-between items-center border-b border-[#72685F] pb-3 mb-8">
            <div class="flex items-center gap-4 text-[#FAEAC9] uppercase text-2xl tracking-wide">
                <button class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">FILTER</button>
                <button class="underline underline-offset-4 hover:text-[#E11C25] transition-colors">SORT</button>
            </div>

            <div class="flex items-center gap-4">
                <a href="create-post.php"
                   class="glow-wrap w-10 h-10 flex items-center justify-center text-[#121110] text-xl font-black">
                    <img src="../assets/images/icons/CryptPlusIcon.png" alt="add post" class="glow-item w-full h-full object-cover">
                </a>
                <div class="glow-wrap flex flex-col items-center text-[11px]">
                    <div class="glow-item w-9 h-9 rounded-full border border-red-900 overflow-hidden mb-1">
                        <img src="../assets/images/icons/CryptProfileIcon.png" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    
                </div>
            </div>
        </header>

        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full border border-red-900 overflow-hidden">
                <img src="../assets/images/icons/profileDummy.png" alt="" class="w-full h-full object-cover">
            </div>
            <span class="text-xl tracking-wider">STINKYSTAG69</span>
        </div>

        <h2 class="uppercase text-md tracking-widest">Title of Post</h2>

        <div class="relative w-full h-[400px] flex items-center justify-center">
            <div class="absolute inset-0 bg-[#121110] opacity-80 border-[4px] border-[#7A0A0A] rounded-xl rough-border pointer-events-none"></div>
            <div class="relative z-10 text-[#FAEAC9] text-3xl">
                WORDS/IMAGE(post)
            </div>
        </div>

        <div class="flex gap-6 text-[#E11C25] font-['Fira_Sans'] font-medium text-m mt-2 transition-colors">
            <button class="glow-wrap flex flex-col items-center gap-1 transition-colors">
                <span class="icon-swap w-10 h-10 glow-item">
                    <img class="icon-default" src="../assets/images/icons/CryptTrueIcon.png" alt="">
                </span>
                <span class="transition-colors">True</span>
            </button>
            <button class="glow-wrap flex flex-col items-center gap-1 transition-colors">
                <span class="icon-swap w-10 h-10 glow-item">
                    <img class="icon-default" src="../assets/images/icons/CryptFalseIcon.png" alt="">
                </span>
                <span class="transition-colors">False</span>
            </button>
            <button class="glow-wrap flex flex-col items-center gap-1 transition-colors">
                <span class="icon-swap w-10 h-10 glow-item">
                    <img class="icon-default" src="../assets/images/icons/CryptTarotIcon.png" alt="">
                </span>
                <span class="transition-colors">Award</span>
            </button>
        </div>

    </div>
</main>


    <script type="module" src="/Crypt-of-Secrets/assets/js/ferrofluid.js"></script>

</body>
</html>