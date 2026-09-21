<?php
session_start();
require_once 'db.php';

// ব্যবহারকারী যদি আগে থেকেই লগইন করা থাকে তবে সরাসরি সংশ্লিষ্ট প্যানেলে রিডাইরেক্ট করা হবে
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['PI', 'Admin', 'Co-Admin'])) {
        header("Location: admin_panel.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error_message = '';
$login_tab = $_GET['tab'] ?? 'member';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $mode = trim($_POST['mode'] ?? 'member');

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT user_id, name, email, password_hash, role, is_approved, designation FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $user = $result->fetch_assoc()) {
            // MD5 পাসওয়ার্ড ভেরিফিকেশন
            if (md5($password) === $user['password_hash']) {
                
                // ১. অ্যাডমিন / PI / Co-Admin মোডে লগইন
                if ($mode === 'admin') {
                    if (in_array($user['role'], ['PI', 'Admin', 'Co-Admin'])) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['designation'] = $user['designation'];
                        header("Location: admin_panel.php");
                        exit();
                    } else {
                        $error_message = "Access Denied! This account does not possess Administrator or Co-Admin privileges.";
                        $login_tab = 'admin';
                    }
                } 
                // ২. ল্যাব মেম্বার মোডে লগইন
                else {
                    if ($user['is_approved'] != 1) {
                        $error_message = "Your profile registration is currently PENDING verification by Laboratory Admin.";
                    } else {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['designation'] = $user['designation'];
                        header("Location: dashboard.php");
                        exit();
                    }
                }
            } else {
                $error_message = "Invalid password. Please check your credentials.";
            }
        } else {
            $error_message = "No registered user found with this email address.";
        }
    } else {
        $error_message = "Please enter both institutional email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Authentication | Multiphase Flow Lab - IIT KGP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .font-serif-title { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#090e17] min-h-screen flex items-center justify-center p-4 text-slate-100">

    <div class="w-full max-w-md bg-[#111926] border border-slate-800 rounded-2xl shadow-2xl p-8 relative overflow-hidden">
        
        <!-- Header / Logo -->
        <div class="text-center mb-6">
            <div class="inline-block p-3 rounded-full bg-cyan-950/60 border border-cyan-500/30 text-cyan-400 mb-3 text-xl">
                <i class="fa-solid fa-microscope"></i>
            </div>
            <div class="text-[10px] font-mono-code uppercase tracking-[0.3em] text-cyan-400 mb-1">IIT Kharagpur</div>
            <h1 class="text-2xl font-serif-title text-white">Multiphase Flow Lab</h1>
            <p class="text-xs text-slate-400 mt-0.5">Laboratory Gateway & Portal Control</p>
        </div>

        <!-- Dual Gateway Tabs -->
        <div class="grid grid-cols-2 gap-2 bg-[#0a0f19] p-1.5 rounded-xl border border-slate-800 mb-6 text-xs font-mono-code">
            <a href="login.php?tab=member" class="text-center py-2 rounded-lg transition <?php echo ($login_tab === 'member') ? 'bg-cyan-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fa-solid fa-user-graduate mr-1"></i> Lab Member
            </a>
            <a href="login.php?tab=admin" class="text-center py-2 rounded-lg transition <?php echo ($login_tab === 'admin') ? 'bg-cyan-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-white'; ?>">
                <i class="fa-solid fa-user-shield mr-1"></i> Admin / PI
            </a>
        </div>

        <!-- Error Notification -->
        <?php if (!empty($error_message)): ?>
            <div class="bg-rose-950/60 border border-rose-700/80 text-rose-300 text-xs px-4 py-3 rounded-xl mb-6 flex items-start gap-2.5 leading-relaxed">
                <i class="fa-solid fa-shield-halved mt-0.5 text-rose-400"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Authentication Form -->
        <form method="POST" action="login.php?tab=<?php echo $login_tab; ?>" class="space-y-4">
            <input type="hidden" name="mode" value="<?php echo $login_tab; ?>">

            <div>
                <label class="block text-xs uppercase tracking-wider text-slate-400 mb-1 font-mono-code">
                    <?php echo ($login_tab === 'admin') ? 'Admin / Co-Admin Email ID' : 'Institutional Email ID'; ?>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                    <input type="email" name="email" required placeholder="user@che.iitkgp.ac.in" autocomplete="off"
                           class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none transition font-mono-code">
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-xs uppercase tracking-wider text-slate-400 font-mono-code">Password</label>
                    <?php if ($login_tab === 'member'): ?>
                        <a href="forgot_password.php" class="text-[11px] text-cyan-400 hover:underline font-mono-code">Forgot Password?</a>
                    <?php endif; ?>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" required placeholder="••••••••" 
                           class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none transition font-mono-code">
                </div>
            </div>

            <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg text-xs uppercase tracking-widest transition duration-200 shadow-md font-mono-code">
                <?php echo ($login_tab === 'admin') ? 'Authenticate Admin Gateway' : 'Enter Member Portal'; ?>
            </button>
        </form>

        <!-- New Member Registration Link -->
        <?php if ($login_tab === 'member'): ?>
            <div class="mt-6 pt-4 border-t border-slate-800/80 text-center">
                <p class="text-xs text-slate-400">
                    New B.Tech / M.Tech / PhD / Scholar? 
                    <a href="member_register.php" class="text-cyan-400 hover:underline font-semibold block mt-1 font-mono-code">
                        Register & Create Lab Profile &rarr;
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <!-- Footer Links -->
        <div class="mt-6 text-center text-xs flex justify-between items-center font-mono-code text-[11px]">
            <a href="index.php" class="text-slate-400 hover:text-cyan-400 transition">
                &larr; Public Website
            </a>
            <span class="text-[10px] text-slate-600">Restricted Lab Network</span>
        </div>
    </div>

</body>
</html>