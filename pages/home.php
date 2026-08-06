<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Crypt of Secrets</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&display=swap" rel="stylesheet">
    <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }

        .gooey-edge {
            filter: url(#liquid-distort);
        }

       
        .ink-bleed {
            position: absolute;
            top: -220px;
            bottom: -220px;
            left: -220px;
            right: 0;
        }

        
        #mainContent {
            margin-left: 90px;
            transition: margin-left 500ms ease-out;
        }
        aside:hover ~ #mainContent,
        aside:focus-within ~ #mainContent {
            margin-left: 400px;
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
    </style>
</head>

<body class="relative w-full min-h-screen bg-[#72685F] text-[#e4d5b7] overflow-x-hidden">
     
    <div id="ferrofluid-container" class="fixed inset-0 w-full h-full z-0"></div>

    <svg class="hidden" xmlns="http://www.w3.org/2000/svg">
        <filter id="liquid-distort" x="-50%" y="-50%" width="200%" height="200%">
            <feTurbulence type="fractalNoise" baseFrequency="0.010" numOctaves="5" result="noise" />
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="250" xChannelSelector="R" yChannelSelector="G" />
        </filter>
    </svg>

    <aside class="fixed top-0 left-0 h-screen w-[380px] -translate-x-[300px] hover:translate-x-0 focus-within:translate-x-0 transition-transform duration-500 ease-out z-50">
        
        <div class="ink-bleed bg-[#121110] gooey-edge"></div>

        <div class="relative z-10 w-[280px] h-full flex flex-col pt-12 pl-10">
            
            <div class=" text-[#121110] font-black px-4 py-2 text-center tracking-widest ">
                <img src="../assets/images/icons/CryptLogo.png" alt="Profile" class="w-full h-full object-cover">
            </div>
            
            <nav class="flex flex-col gap-8">
                <a href="home.php" class="text-[#eaddc5] hover:text-white font-bold text-3xl tracking-widest uppercase transition-colors">
                    HOME
                </a>
                <a href="create-post.php" class="text-[#eaddc5] hover:text-white font-bold text-3xl tracking-widest uppercase transition-colors">
                    CREATE POST
                </a>
                <a href="profile.php" class="text-[#eaddc5] hover:text-white font-bold text-3xl tracking-widest uppercase transition-colors">
                    PROFILE
                </a>
                <a href="analytics.php" class="text-[#eaddc5] hover:text-white font-bold text-3xl tracking-widest uppercase transition-colors">
                    ANALYTICS
                </a>
                <a href="awards.php" class="text-[#eaddc5] hover:text-white font-bold text-3xl tracking-widest uppercase transition-colors">
                    AWARDS
                </a>
            </nav>
        </div>
    </aside>

   
    <main id="mainContent" class="relative z-10 min-h-screen flex flex-col px-8 py-6">
        
       
        <header class="flex justify-between items-center border-b border-gray-600 pb-3 mb-8">
            
           
            <div class="flex items-center gap-5 text-[#7A0A0A] font-bold uppercase text-3xl tracking-wide">
                <button class="underline underline-offset-4 hover:text-[#0B1A57] transition-colors">FILTER</button>
                <button class="underline underline-offset-4 hover:text-[#0B1A57] transition-colors">SORT</button>
            </div>
            
           
            <div class="flex items-center gap-5">
                
                <a href="create-post.php"
                   class="w-16 h-16 flex items-center justify-center text-[#121110] text-xl font-black hover:bg-white transition-colors">
                    <img src="../assets/images/icons/tempAddIcon.png" alt="Profile" class="w-full h-full object-cover">
                </a>
              
                <div class="flex flex-col items-center text-[16px]">
                    <div class="w-12 h-12 rounded-full border border-red-900 overflow-hidden mb-1">
                        <img src="../assets/images/icons/profileDummy.png" alt="Profile" class="w-full h-full object-cover">
                    </div>
                    
                </div>
            </div>
            
        </header>

        <div class="w-full max-w-4xl flex flex-col gap-3 mx-auto">
            
       
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full border border-red-900 overflow-hidden">
                    <img src="../assets/images/icons/profileDummy.png" alt="" class="w-full h-full object-cover">
                </div>
                <span class="font-bold text-sm tracking-wider">STINKYSTAG69</span>
            </div>

         
            <h2 class="font-bold uppercase text-sm tracking-widest">Title of Post</h2>

           
            <div class="w-full h-[400px] bg-[#e2e2e2] text-[#121110] text-3xl font-bold flex items-center justify-center border-2 border-transparent">
                WORDS/IMAGE
            </div>

            
            <div class="flex gap-6 text-red-600 font-bold text-xs mt-2">
                <button class="flex flex-col items-center gap-1 transition-colors">
                    <span class="icon-swap w-6 h-6">
                        <img class="icon-default" src="../assets/images/icons/truthicon.png" alt="">
                       </span>
                    <span>True</span>
                </button>
                <button class="flex flex-col items-center gap-1 transition-colors">
                    <span class="icon-swap w-6 h-6">
                        <img class="icon-default" src="../assets/images/icons/falseIcon.png" alt="">
                          </span>
                    <span>False</span>
                </button>
                <button class="flex flex-col items-center gap-1 transition-colors">
                    <span class="icon-swap w-6 h-6">
                        <img class="icon-default" src="../assets/images/icons/awardIcon.png" alt="">
                        </span>
                    <span>Award</span>
                </button>
            </div>

        </div>

    </main>

    <script type="module" src="/Crypt-of-Secrets/assets/js/ferrofluid.js"></script>

</body>
</html>