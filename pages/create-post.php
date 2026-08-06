<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&display=swap" rel="stylesheet">
    <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body {
            font-family: 'Eczar', serif;
        }

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

        aside:hover~#mainContent,
        aside:focus-within~#mainContent {
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

        
        <header class="flex justify-end items-center border-b border-gray-600 pb-3 mb-8">

            <div class="flex items-center gap-5">
                <a href="create-post.php"
                    class="w-16 h-16 flex items-center justify-center text-[#121110] text-xl font-black hover:bg-white transition-colors">
                    <img src="../assets/images/icons/tempAddIcon.png" alt="Create post" class="w-full h-full object-cover">
                </a>
                <div class="flex flex-col items-center text-[16px]">
                    <div class="w-12 h-12 rounded-full border border-red-900 overflow-hidden mb-1">
                        <img src="../assets/images/icons/profileDummy.png" alt="Profile" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>

        </header>

       
        <div class="w-full max-w-4xl flex flex-col gap-6 mx-auto flex-1">

            <h1 class="text-[#eaddc5] font-bold uppercase text-3xl tracking-widest">Create Post</h1>

            <div class="flex flex-col gap-2">
                <label for="postTitle" class="text-[#eaddc5] font-bold uppercase text-sm tracking-widest">Title</label>
                <input id="postTitle" type="text" name="title"
                    class="w-full bg-[#e2e2e2] text-[#121110] px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#7A0A0A]">
            </div>

            <div class="flex flex-col gap-2 flex-1">
                <label for="postBody" class="text-[#eaddc5] font-bold uppercase text-sm tracking-widest">Post</label>
                <textarea id="postBody" name="body" placeholder="Text Area"
                    class="w-full h-64 resize-none bg-[#e2e2e2] text-[#121110] placeholder:text-[#6b6b6b] uppercase text-sm p-3 focus:outline-none focus:ring-2 focus:ring-[#7A0A0A]"></textarea>
            </div>

         
            <div class="flex justify-right items-start mt-2">



                <div class="flex gap-4">
                    <button type="submit" class="relative mt-8 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">

                        <img src="/Crypt-of-Secrets/assets/images/CryptButtonRedder.png" alt="Log In" class="w-60 h-auto drop-shadow-md">

                        <span class="absolute text-red-700 font-bold text-xl tracking-widest uppercase -translate-y-6 group-hover:text-red-600 transition-colors">
                            Save Draft
                        </span>
                    </button>

                     <button type="submit" class="relative mt-8 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">

                        <img src="/Crypt-of-Secrets/assets/images/CryptButtonRedder.png" alt="Log In" class="w-60 h-auto drop-shadow-md">

                        <span class="absolute text-red-700 font-bold text-xl tracking-widest uppercase -translate-y-6 group-hover:text-red-600 transition-colors">
                            Post
                        </span>
                    </button>
                </div>

            </div>

        </div>

    </main>

    <script type="module" src="/Crypt-of-Secrets/assets/js/ferrofluid.js"></script>

</body>

</html>