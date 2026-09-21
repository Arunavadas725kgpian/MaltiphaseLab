<?php
session_start();
require_once 'db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'PhD Scholar');
    $phone = trim($_POST['phone'] ?? '');
    $research_topic = trim($_POST['bio'] ?? '');
    $scholar_link = trim($_POST['google_scholar_link'] ?? '');

    if (!empty($name) && !empty($email) && !empty($password)) {
        // ইমেইল চেক
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error_msg = "An account with this email address already exists in the laboratory database.";
        } else {
            $password_hash = md5($password);
            $is_approved = 0; // অ্যাডমিন এপ্রুভ না করা পর্যন্ত নিষ্ক্রিয় থাকবে

            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, designation, phone, bio, google_scholar_link, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $designation = $role; // প্রাথমিকভাবে ডেসিগনেশন হিসেবে রোল সেট থাকবে
            $stmt->bind_param("ssssssssi", $name, $email, $password_hash, $role, $designation, $phone, $research_topic, $scholar_link, $is_approved);

            if ($stmt->execute()) {
                $success_msg = "Registration request submitted successfully! Your account status is currently PENDING. You will be able to log in once the Principal Investigator (Admin) verifies and approves your laboratory membership.";
            } else {
                $error_msg = "Failed to submit registration. Please check your data and try again.";
            }
        }
    } else {
        $error_msg = "Please fill in all mandatory fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile Registration | Multiphase Flow Lab - IIT KGP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .font-serif-title { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#090e17] text-slate-100 min-h-screen flex items-center justify-center p-4 py-12">

    <div class="w-full max-w-xl bg-[#111926] border border-slate-800 rounded-2xl shadow-2xl p-8 relative overflow-hidden">
        
        <div class="text-center mb-8">
            <div class="inline-block p-3 rounded-full bg-cyan-950/60 border border-cyan-500/30 text-cyan-400 mb-3 text-xl">
                <i class="fa-solid fa-id-card-clip"></i>
            </div>
            <div class="text-[10px] font-mono-code uppercase tracking-[0.3em] text-cyan-400 mb-1">IIT Kharagpur &bull; Chemical Engineering</div>
            <h1 class="text-2xl font-serif-title text-white">Lab Member Onboarding</h1>
            <p class="text-xs text-slate-400 mt-1">Create your research profile for verification & access</p>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="bg-emerald-950/70 border border-emerald-700/80 text-emerald-300 text-xs p-4 rounded-xl mb-6 flex items-start gap-3 leading-relaxed">
                <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5 text-base"></i>
                <div><?php echo $success_msg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-rose-950/70 border border-rose-700/80 text-rose-300 text-xs p-4 rounded-xl mb-6 flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-400 mt-0.5 text-base"></i>
                <div><?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 uppercase font-mono-code mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="Enter Your Full Name" class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-slate-400 uppercase font-mono-code mb-1">Institutional Email *</label>
                    <input type="email" name="email" required placeholder="name@che.iitkgp.ac.in" class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 uppercase font-mono-code mb-1">Laboratory Role / Position *</label>
                    <select name="role" required class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none">
                        <option value="PhD Scholar">PhD Research Scholar</option>
                        <option value="Post-Doc">Post-Doctoral Fellow</option>
                        <option value="M.Tech">M.Tech Student</option>
                        <option value="B.Tech">B.Tech Project Student</option>
                        <option value="Faculty">Visiting / Co-Faculty</option>
                        <option value="Project Staff">Project Staff</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 uppercase font-mono-code mb-1">Phone / Mobile</label>
                    <input type="text" name="phone" placeholder="+91 XXXXXXXXXX" class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-slate-400 uppercase font-mono-code mb-1">Account Password *</label>
                <input type="password" name="password" required placeholder="Create secure password" class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-400 uppercase font-mono-code mb-1">Research Field & Focus Description</label>
                <textarea name="bio" rows="2" placeholder="e.g. Core-annular flow dynamics, PIV laser visualization in microchannels..." class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-slate-400 uppercase font-mono-code mb-1">Google Scholar / Profile URL (Optional)</label>
                <input type="url" name="google_scholar_link" placeholder="https://scholar.google.com/citations?user=..." class="w-full bg-[#0b1019] border border-slate-700 focus:border-cyan-400 rounded-lg p-2.5 text-white focus:outline-none">
            </div>

            <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg uppercase tracking-wider transition">
                Submit Profile for Admin Approval
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-800 text-center text-xs text-slate-500 flex justify-between items-center">
            <a href="login.php" class="text-cyan-400 hover:underline inline-flex items-center gap-1 font-mono-code">
                &larr; Back to Portal Login
            </a>
            <span class="text-[10px] text-slate-600 font-mono-code">Verification Mandatory</span>
        </div>
    </div>

</body>
</html>