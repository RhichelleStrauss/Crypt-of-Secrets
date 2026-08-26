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
        $email = $_POST['email'] ?? '';

        $result = registerUser(
            $pdo,
            $email,
            $_POST['password'] ?? '',
            $_POST['confirm'] ?? ''
        );

        if ($result['ok']) {
            loginUser($pdo, $email, $_POST['password']);
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
    <title>Sign Up - Crypt of Secrets</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Eczar:wght@400..800&family=Fira+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="<?= BASE_URL ?>dist/output.css?v=<?php echo time(); ?>" rel="stylesheet">

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
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyGreyLine.png" alt="" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-8 md:h-12 object-contain">
            </div>

            <div class="flex items-center justify-center gap-4 md:gap-8 mb-8">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-10 md:h-14 object-contain scale-x-[-1]">
                <h1 class="text-[#FAEAC9] text-5xl md:text-6xl tracking-widest uppercase">Sign Up</h1>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-10 md:h-14 object-contain">
            </div>

            <?php if ($error): ?>
            <div class="w-full max-w-lg mb-5 border border-[#7A0A0A] bg-[#7A0A0A]/15 px-4 py-3">
                <p class="font-['Fira_Sans'] text-sm text-[#E11C25]"><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form action="signup.php" method="POST" class="w-full max-w-lg flex flex-col gap-5">

                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">

                <div class="flex flex-col">
                    <label for="email" class="text-lg tracking-wide mb-1">Email:</label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($email) ?>"
                           placeholder="Enter your email"
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner">
                </div>

                <div class="flex flex-col">
                    <label for="password" class="text-lg tracking-wide mb-1">Password:</label>
                    <input type="password" id="password" name="password" required minlength="8"
                           placeholder="At least 8 characters"
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner">
                </div>

                <div class="flex flex-col">
                    <label for="confirm" class="text-lg tracking-wide mb-1">Confirm Password:</label>
                    <input type="password" id="confirm" name="confirm" required minlength="8"
                           placeholder="Repeat your password"
                           class="w-full bg-[#0a0a0a] text-[#FAEAC9] px-4 py-3 outline-none focus:ring-1 focus:ring-[#7A0A0A] shadow-inner">
                </div>

                <button type="submit" class="relative mt-4 mx-auto flex items-center justify-center group bg-transparent border-none cursor-pointer">
                    <img src="<?= BASE_URL ?>assets/images/CryptDefaultButton.png" alt=""
                         class="h-[70px] w-auto object-contain transition-transform duration-300 ease-out group-hover:scale-110">
                    <span class="absolute inset-0 flex items-center justify-center text-[#eaddc5] group-hover:text-white text-2xl tracking-widest uppercase transition-colors drop-shadow-md pointer-events-none">
                        Sign Up
                    </span>
                </button>

            </form>

            <div class="flex items-center justify-center gap-6 mt-10 mb-6">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-8 md:h-10 object-contain scale-x-[-1]">
                <div class="flex flex-col items-center gap-1 text-lg">
                    <span class="text-[#FAEAC9]">Already have an account?</span>
                    <a href="login.php" class="text-[#FAEAC9] font-bold tracking-widest underline decoration-2 underline-offset-4 hover:text-white transition-colors">
                        LOG IN
                    </a>
                </div>
                <img src="<?= BASE_URL ?>assets/images/CryptWavyMiniLineGrey.png" alt="" class="h-8 md:h-10 object-contain">
            </div>

            <div class="flex items-center justify-center w-full gap-2 md:gap-4 mt-6 rotate-180">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-8 md:h-12 object-contain scale-x-[-1]">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyGreyLine.png" alt="" class="w-full max-w-2xl h-auto drop-shadow-md">
                <img src="<?= BASE_URL ?>assets/images/CryptWavyInsideMiniLineGrey.png" alt="" class="h-8 md:h-12 object-contain">
            </div>

        </div>
    </div>

    <script type="module" src="<?= BASE_URL ?>assets/js/ferrofluid.js"></script>
</body>
</html>