<?php
session_start();
require_once 'db.php';

$step = 1; 
$msg = '';
$err = '';

// স্টেপ ১: ইমেইল/ফোন যাচাই করে ওটিপি প্রেরণ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $identifier = trim($_POST['identifier'] ?? '');

    if (!empty($identifier)) {
        $stmt = $conn->prepare("SELECT user_id, name, email, phone FROM users WHERE (email = ? OR phone = ?) AND is_approved = 1 LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $user = $res->fetch_assoc()) {
            $otp = strval(rand(100000, 999999));
            $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            $up_stmt = $conn->prepare("UPDATE users SET reset_otp = ?, otp_expiry = ? WHERE user_id = ?");
            $up_stmt->bind_param("ssi", $otp, $expiry, $user['user_id']);
            $up_stmt->execute();

            $_SESSION['reset_user_id'] = $user['user_id'];
            $_SESSION['reset_identifier'] = $identifier;
            
            // ব্যবহারকারীর ইমেইলে ওটিপি পাঠানো
            $to = $user['email'];
            $subject = "Password Reset OTP - Multiphase Flow Lab";
            $message = "Dear " . htmlspecialchars($user['name']) . ",\r\n\r\n"
                     . "Your One-Time Password (OTP) for resetting your laboratory portal password is: " . $otp . "\r\n\r\n"
                     . "This code is valid for 15 minutes. Do not share this OTP with anyone.\r\n\r\n"
                     . "Regards,\r\n"
                     . "Multiphase Flow Laboratory\r\n"
                     . "Department of Chemical Engineering, IIT Kharagpur";
            
            $headers = "From: noreply@multiphase-lab.iitkgp.ac.in\r\n" .
                       "Reply-To: noreply@multiphase-lab.iitkgp.ac.in\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            @mail($to, $subject, $message, $headers);

            $msg = "A 6-digit OTP has been dispatched to your registered address (" . htmlspecialchars($user['email']) . "). Please check your inbox or spam folder.";
            $step = 2;
        } else {
            $err = "No verified laboratory member found with this Email or Phone Number.";
        }
    } else {
        $err = "Please enter your registered institutional email or phone number.";
    }
}

// স্টেপ ২: ওটিপি যাচাই এবং পাসওয়ার্ড পরিবর্তন
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_and_reset'])) {
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    $entered_otp = trim($_POST['otp'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    if (!empty($user_id) && !empty($entered_otp) && !empty($new_pass)) {
        if ($new_pass !== $confirm_pass) {
            $err = "Passwords do not match. Please re-enter.";
            $step = 2;
        } else {
            $now = date("Y-m-d H:i:s");
            $v_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND reset_otp = ? AND otp_expiry >= ?");
            $v_stmt->bind_param("iss", $user_id, $entered_otp, $now);
            $v_stmt->execute();
            $v_res = $v_stmt->get_result();

            if ($v_res && $v_res->num_rows > 0) {
                $new_hash = md5($new_pass);
                $up = $conn->prepare("UPDATE users SET password_hash = ?, reset_otp = NULL, otp_expiry = NULL WHERE user_id = ?");
                $up->bind_param("si", $new_hash, $user_id);
                $up->execute();

                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_identifier']);
                $msg = "Password reset successfully! You can now log in with your new password.";
                $step = 3;
            } else {
                $err = "Invalid or expired OTP. Please check your messages and try again.";
                $step = 2;
            }
        }
    } else {
        $err = "Please fill in all the required fields.";
        $step = 2;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Password Recovery | Multiphase Flow Lab</title>
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
        
        <div class="text-center mb-6">
            <div class="inline-block p-3 rounded-full bg-cyan-950/60 border border-cyan-500/30 text-cyan-400 mb-3 text-xl">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="text-[10px] font-mono-code uppercase tracking-[0.3em] text-cyan-400 mb-1">IIT Kharagpur</div>
            <h1 class="text-2xl font-serif-title text-white">Reset Account Password</h1>
            <p class="text-xs text-slate-400 mt-0.5">Laboratory Authentication Recovery</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="bg-emerald-950/70 border border-emerald-700/80 text-emerald-300 text-xs px-4 py-3 rounded-xl mb-4 flex items-start gap-2.5">
                <i class="fa-solid fa-circle-check mt-0.5 text-emerald-400"></i>
                <div class="leading-relaxed"><?php echo $msg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($err)): ?>
            <div class="bg-rose-950/60 border border-rose-700/80 text-rose-300 text-xs px-4 py-3 rounded-xl mb-5 flex items-start gap-2.5">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 text-rose-400"></i>
                <div class="leading-relaxed"><?php echo $err; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST" action="forgot_password.php" class="space-y-4 text-xs">
                <input type="hidden" name="send_otp" value="1">
                <div>
                    <label class="block uppercase tracking-wider text-slate-400 mb-1.5 font-mono-code">
                        Registered Email OR Phone Number
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                        <input type="text" name="identifier" required placeholder="user@che.iitkgp.ac.in or phone" 
                               class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none transition font-mono-code">
                    </div>
                </div>

                <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg text-xs uppercase tracking-widest transition duration-200 shadow-md">
                    Send Verification OTP &rarr;
                </button>
            </form>

        <?php elseif ($step === 2): ?>
            <form method="POST" action="forgot_password.php" class="space-y-4 text-xs">
                <input type="hidden" name="verify_and_reset" value="1">

                <div>
                    <label class="block uppercase tracking-wider text-amber-400 mb-1 font-mono-code">Enter 6-Digit OTP</label>
                    <input type="text" name="otp" maxlength="6" required placeholder="••••••" 
                           class="w-full bg-[#0b1019] border border-amber-500/50 focus:border-amber-400 rounded-lg text-center tracking-[0.5em] text-lg font-mono-code py-2 text-white placeholder-slate-600 focus:outline-none transition">
                </div>

                <div>
                    <label class="block uppercase tracking-wider text-slate-400 mb-1 font-mono-code">New Password</label>
                    <input type="password" name="new_password" required placeholder="••••••••" 
                           class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg px-3 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none transition">
                </div>

                <div>
                    <label class="block uppercase tracking-wider text-slate-400 mb-1 font-mono-code">Confirm New Password</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" 
                           class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg px-3 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none transition">
                </div>

                <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg text-xs uppercase tracking-widest transition duration-200 shadow-md">
                    Reset & Update Password
                </button>
            </form>

        <?php elseif ($step === 3): ?>
            <div class="text-center pt-2">
                <a href="login.php?tab=member" class="inline-block w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg text-xs uppercase tracking-widest transition duration-200 shadow-md">
                    Proceed To Portal Login &rarr;
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-6 pt-4 border-t border-slate-800/80 flex justify-between items-center text-xs font-mono-code text-[11px]">
            <a href="login.php?tab=member" class="text-slate-400 hover:text-cyan-400 transition">
                &larr; Back to Login
            </a>
            <a href="index.php" class="text-slate-500 hover:text-slate-400">Home</a>
        </div>
    </div>

</body>
</html>