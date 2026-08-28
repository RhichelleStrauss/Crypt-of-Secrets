<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!checkCsrf($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email  = $_POST['email'] ?? '';
        $result = loginUser($pdo, $email, $_POST['password'] ?? '');

        if ($result['ok']) {
            header('Location: home.php');
            exit;
        }

        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:wght@400;500;600&display=swap" rel="stylesheet">

   <link href="/Crypt-of-Secrets/dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

   
    <style>
        body { font-family: 'Eczar', serif; }
        input::placeholder { color: #4d4d4d; }
        .rough-border { filter: url(#rough-border); }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 100px #121110 inset !important;
            box-shadow: 0 0 0 100px #121110 inset !important;
            border-radius: 0.75rem;
            -webkit-text-fill-color: #FAEAC9 !important;
            caret-color: #FAEAC9;
            transition: background-color 9999s ease-in-out 0s;
        }
    </style>
</head>
<body class="relative w-full min-h-screen overflow-x-hidden bg-[#121110] text-[#e4d5b7]">

    <div id="ferrofluid-container" class="fixed inset-0 w-full h-full z-0"></div>

    <svg class="absolute w-0 h-0 overflow-hidden" xmlns="http://www.w3.org/2000/svg">
        <filter id="rough-border" color-interpolation-filters="sRGB">
            <feTurbulence type="fractalNoise" baseFrequency="0.4" numOctaves="3" result="noise" />
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="4" xChannelSelector="R" yChannelSelector="G" />
        </filter>
    </svg>

    <div class="relative z-10 flex flex-col items-center justify-center min-h-screen py-6 sm:py-10 px-4">
        <div class="w-full max-w-6xl flex flex-col items-center">

            <div class="flex items-center justify-center w-full gap-2 md:gap-4 mb-6 sm:mb-10">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-5 sm:h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyGreyLine.png" alt="" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-5 sm:h-8 md:h-12 object-contain">
            </div>

            <div class="flex items-center justify-center gap-2 sm:gap-4 md:gap-8 mb-6 sm:mb-8">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-7 sm:h-10 md:h-14 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-3xl sm:text-5xl md:text-6xl tracking-widest uppercase text-center">Log In</h1>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-7 sm:h-10 md:h-14 object-contain">
            </div>

            <?php if ($error): ?>
            <div class="w-full max-w-lg mb-5 border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="w-full max-w-lg flex flex-col gap-5">

                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">

                <div class="flex flex-col">
                    <label for="email" class="text-base sm:text-lg tracking-wide mb-1">Email:</label>
                    <div class="relative">
                        <input type="email" id="email" name="email" required
                               value="<?= htmlspecialchars($email) ?>"
                               placeholder="Enter your email"
                               class="peer relative z-10 w-full rounded-xl bg-[#121110]/20 text-[#FAEAC9] px-4 py-3 outline-none">
                        <div class="absolute inset-0 z-20 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label for="password" class="text-base sm:text-lg tracking-wide mb-1">Password:</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••"
                               class="peer relative z-10 w-full rounded-xl bg-[#121110]/20 text-[#FAEAC9] px-4 py-3 outline-none">
                        <div class="absolute inset-0 z-20 rounded-xl border-[3px] border-[#7A0A0A] rough-border peer-focus:border-[#FAEAC9] pointer-events-none transition-colors"></div>
                    </div>
                </div>

                <button type="submit" class="relative mt-4 mx-auto flex items-center justify-center group bg-transparent border-none cursor-pointer">
                    <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt=""
                         class="h-[52px] sm:h-[70px] w-auto object-contain transition-transform duration-300 ease-out group-hover:scale-110">
                    <span class="absolute inset-0 flex items-center justify-center text-[#121110] group-hover:text-[#121110] text-xl sm:text-3xl tracking-widest uppercase transition-colors drop-shadow-md pointer-events-none">
                        Log In
                    </span>
                </button>

            </form>

            <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-6 mt-8 sm:mt-10 mb-6">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="hidden sm:block h-8 md:h-10 object-contain scale-x-[-1]">
                <div class="flex flex-col items-center gap-1 text-base sm:text-lg">
                    <span class="text-[#FAEAC9]">Don't have an account?</span>
                    <a href="signup.php" class="text-[#FAEAC9] font-bold tracking-widest underline decoration-2 underline-offset-4 hover:text-[#FAEAC9] transition-colors">
                        SIGN UP
                    </a>
                </div>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="hidden sm:block h-8 md:h-10 object-contain">
            </div>

            <div class="flex items-center justify-center w-full gap-2 md:gap-4 mt-6 rotate-180">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-5 sm:h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyGreyLine.png" alt="" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-5 sm:h-8 md:h-12 object-contain">
            </div>

        </div>
    </div>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>
</body>
</html>