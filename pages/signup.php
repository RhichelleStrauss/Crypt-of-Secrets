<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Crypt of Secrets</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&display=swap" rel="stylesheet">

    <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        
       
        input::placeholder { color: #4a4a4a; }
    </style>
</head>
<body class="relative w-full min-h-screen overflow-x-hidden bg-[#121110] text-[#e4d5b7]">
    
    
    <div id="ferrofluid-container" class="fixed inset-0 w-full h-full z-0"></div>

   
    <div class="relative z-10 flex flex-col items-center justify-center min-h-screen py-10 px-4">
        
        
        <div class="w-full max-w-6xl flex flex-col items-center">
            
          
            <div class="flex items-center justify-center w-full gap-2 md:gap-4 mb-10">
              
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyInsideMiniLineGrey.png" alt="Ornament" class="h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyGreyLine.png" alt="Main Line" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyInsideMiniLineGrey.png" alt="Ornament" class="h-8 md:h-12 object-contain">
            </div>

         
            <div class="flex items-center justify-center gap-4 md:gap-8 mb-10">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-10 md:h-14 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-5xl md:text-6xl tracking-widest  uppercase">Sign Up</h1>
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-10 md:h-14 object-contain">
            </div>

          <!-- temp form -->
            <form action="#" method="POST" class="w-full max-w-lg flex flex-col gap-5">
                
                <div class="flex flex-col">
                    <label for="name" class="text-lg tracking-wide mb-1">Name:</label>
                    <input type="text" id="name" name="name" placeholder="Enter your name" 
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner rounded-md">
                </div>

                <div class="flex flex-col">
                    <label for="email" class="text-lg tracking-wide mb-1">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" 
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner rounded-md">
                </div>

                <div class="flex flex-col">
                    <label for="password" class="text-lg tracking-wide mb-1">Password:</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" 
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner rounded-md">
                </div>

                
            <button type="submit" class="relative mt-6 mx-auto flex items-center justify-center group bg-transparent border-none cursor-pointer">
    
    <img src="/Crypt-of-Secrets/assets/images/CryptDefaultButton.png" 
         class="h-[70px] w-auto object-contain transition-transform duration-300 ease-out group-hover:scale-110" 
         alt="">
    
    <span class="absolute inset-0 flex items-center justify-center text-[#eaddc5] group-hover:text-white text-2xl tracking-widest uppercase transition-colors drop-shadow-md pointer-events-none">
        SIGN UP
    </span>
    
</button>
            </form>

           
            <div class="flex items-center justify-center gap-6 mt-12 mb-6">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-8 md:h-10 object-contain scale-x-[-1]">
                <div class="flex flex-col items-center gap-1 text-lg">
                    <span class="text-[#FAEAC9]">Already have an account?</span>
                    <a href="signup.php" class="text-[#FAEAC9] font-bold tracking-widest underline decoration-2 underline-offset-4 hover:text-white transition-colors">
                        LOG IN
                    </a>
                </div>
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-8 md:h-10 object-contain">
            </div>

            
            <div class="flex items-center justify-center w-full gap-2 md:gap-4 mt-6 rotate-180">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyInsideMiniLineGrey.png" alt="Ornament" class="h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyGreyLine.png" alt="Main Line" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyInsideMiniLineGrey.png" alt="Ornament" class="h-8 md:h-12 object-contain">
            </div>

        </div>
    </div>

    
    <script type="module" src="/Crypt-of-Secrets/assets/js/ferrofluid.js"></script>

</body>
</html>