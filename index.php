<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypt of Secrets</title>
   
    <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Eczar', serif;
        }
    </style>

</head>

<body class="relative w-full h-screen overflow-hidden bg-[#121110]">

<a href="pages/login.php" class="absolute inset-0 z-40 block w-full h-full cursor-pointer" aria-label="Log in"></a>
    
    
    <div class="fixed inset-0 w-full h-full z-0 bg-[url('/Crypt-of-Secrets/assets/images/CryptSplashPatternBg.png')] bg-cover bg-center bg-no-repeat"></div>

  
    <div class="fixed inset-0 w-full h-full pointer-events-none z-20">
        <img src="/Crypt-of-Secrets/assets/images/CryptCurtainBgLeft.png" 
             class="absolute top-0 left-0 h-screen w-auto object-cover" 
             alt="Left Curtain">
             
        <img src="/Crypt-of-Secrets/assets/images/CryptCurtainBgRight.png" 
             class="absolute top-0 right-0 h-screen w-auto object-cover" 
             alt="Right Curtain">
    </div>

    
    <div class="relative z-30 w-full h-full flex flex-col items-center justify-center pointer-events-none">
        <h2 class="text-[#72685F] text-3xl font-bold tracking-widest mb-2">WELCOME TO THE</h2>
        <h1 class="text-[#E11C25] text-6xl font-black tracking-widest drop-shadow-lg">CRYPT OF SECRETS</h1>

        
    </div>

</body>
</html>