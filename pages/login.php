<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Crypt of Secrets</title>
    
    <!-- Google Fonts: Eczar -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&display=swap" rel="stylesheet">

    <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

    <style>
        body { font-family: 'Eczar', serif; }
        
       
        input::placeholder { color: #4a4a4a; }
    </style>
</head>
<body class="relative w-full min-h-screen overflow-x-hidden  text-[#e4d5b7]">
    
    
   <div class="fixed inset-0 w-full h-full z-1 bg-[url('/Crypt-of-Secrets/assets/images/loginBg.jpg')] bg-cover bg-center bg-no-repeat"></div>

   
    <div class="relative z-10 flex flex-col items-center justify-center min-h-screen py-10 px-4">
        
        
        <div class="w-full max-w-6xl flex flex-col items-center">
            
          
            <div class="flex items-center justify-center w-full gap-2 md:gap-4 mb-10">
              
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyInsideMiniLineGrey.png" alt="Ornament" class="h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyGreyLine.png" alt="Main Line" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyInsideMiniLineGrey.png" alt="Ornament" class="h-8 md:h-12 object-contain">
            </div>

         
            <div class="flex items-center justify-center gap-4 md:gap-8 mb-10">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-10 md:h-14 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-5xl md:text-6xl tracking-widest  uppercase">Log In</h1>
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-10 md:h-14 object-contain">
            </div>

          <!-- temp form -->
            <form action="#" method="POST" class="w-full max-w-lg flex flex-col gap-5">
                
                <div class="flex flex-col">
                    <label for="name" class="text-lg tracking-wide mb-1">Name:</label>
                    <input type="text" id="name" name="name" placeholder="Enter your name" 
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner">
                </div>

                <div class="flex flex-col">
                    <label for="email" class="text-lg tracking-wide mb-1">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" 
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner">
                </div>

                <div class="flex flex-col">
                    <label for="password" class="text-lg tracking-wide mb-1">Password:</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" 
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner">
                </div>

                
                <button type="submit" class="relative mt-8 mx-auto flex items-center justify-center group transition-transform duration-200 hover:scale-105 active:scale-95">
    
   
    <img src="/Crypt-of-Secrets/assets/images/CryptButtonRedder.png" alt="Log In" class="w-64 h-auto drop-shadow-md">
    
   
    <span class="absolute text-red-700 font-bold text-3xl tracking-widest uppercase -translate-y-6 group-hover:text-red-600 transition-colors">
        LOG IN
    </span>

</button>

            </form>

           
            <div class="flex items-center justify-center gap-6 mt-12 mb-6">
                <img src="/Crypt-of-Secrets/assets/images/CryptWavyMiniLineGrey.png" alt="Ornament" class="h-8 md:h-10 object-contain scale-x-[-1]">
                <div class="flex flex-col items-center gap-1 text-lg">
                    <span class="text-[#FAEAC9]">Don't have an account?</span>
                    <a href="signup.php" class="text-[#FAEAC9] font-bold tracking-widest underline decoration-2 underline-offset-4 hover:text-white transition-colors">
                        SIGN UP
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

   
   

</body>
</html>