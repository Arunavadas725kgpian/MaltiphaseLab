<?php
session_start();
require_once 'db.php';

// ১. অথেনটিকেশন চেক
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php?tab=member");
    exit();
}

// ২. রোল চেক: PI বা Admin হলে অ্যাডমিন প্যানেলে রিডাইরেক্ট করবে
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'PI' || $_SESSION['role'] === 'Admin')) {
    header("Location: admin_panel.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$err = '';

// ৩. মেম্বারের সম্পূর্ণ স্ট্যান্ডার্ড প্রোফাইল ও ফটো আপডেট হ্যান্ডলিং
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_personal_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course_duration = trim($_POST['course_duration'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $technical_skills = trim($_POST['technical_skills'] ?? '');
    $recent_projects = trim($_POST['recent_projects'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $scholar_link = trim($_POST['google_scholar_link'] ?? '');

    // ফটো আপলোড
    $image_path = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $fileName = $_FILES['profile_photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'member_' . $user_id . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_path = $dest_path;
            }
        } else {
            $err = "Only JPG, JPEG, PNG, and WEBP formats are allowed.";
        }
    }

    if (empty($err)) {
        if ($image_path) {
            $u_stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, course_duration = ?, bio = ?, technical_skills = ?, recent_projects = ?, experience = ?, google_scholar_link = ?, profile_image = ? WHERE user_id = ?");
            $u_stmt->bind_param("sssssssssi", $name, $phone, $course_duration, $bio, $technical_skills, $recent_projects, $experience, $scholar_link, $image_path, $user_id);
        } else {
            $u_stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, course_duration = ?, bio = ?, technical_skills = ?, recent_projects = ?, experience = ?, google_scholar_link = ? WHERE user_id = ?");
            $u_stmt->bind_param("ssssssssi", $name, $phone, $course_duration, $bio, $technical_skills, $recent_projects, $experience, $scholar_link, $user_id);
        }

        if ($u_stmt->execute()) {
            $_SESSION['name'] = $name;
            $msg = "Your comprehensive research profile and photo have been updated successfully!";
        } else {
            $err = "Failed to update profile details.";
        }
    }
}

// ৪. পেপার যোগ করা
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_paper'])) {
    $title = trim($_POST['title']);
    $authors = trim($_POST['authors']);
    $journal = trim($_POST['journal_name']);
    $year = intval($_POST['year']);
    $doi = trim($_POST['doi']);
    
    $p_stmt = $conn->prepare("INSERT INTO publications (title, authors, journal_name, doi, year, pub_type, added_by) VALUES (?, ?, ?, ?, ?, 'Journal', ?)");
    $p_stmt->bind_param("ssssii", $title, $authors, $journal, $doi, $year, $user_id);
    if ($p_stmt->execute()) {
        $msg = "Research paper successfully published to laboratory repository.";
    }
}

// ৫. স্লট বুকিং
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_slot'])) {
    $equip_id = intval($_POST['equip_id']);
    $b_date = trim($_POST['booking_date']);
    $time_slot = trim($_POST['time_slot']);
    $purpose = trim($_POST['purpose']);

    $b_ins = $conn->prepare("INSERT INTO bookings (user_id, equip_id, booking_date, time_slot, purpose, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $b_ins->bind_param("iisss", $user_id, $equip_id, $b_date, $time_slot, $purpose);
    if ($b_ins->execute()) {
        $msg = "Instrument slot reservation submitted! Awaiting PI approval.";
    }
}

// ৬. পেপার ডিলিট
if (isset($_GET['delete_pub'])) {
    $del_id = intval($_GET['delete_pub']);
    $conn->query("DELETE FROM publications WHERE pub_id = $del_id AND added_by = $user_id");
    $msg = "Publication entry removed.";
}

// মেম্বার তথ্য ফেচ
$u_res = $conn->query("SELECT * FROM users WHERE user_id = $user_id LIMIT 1");
$current_user = $u_res->fetch_assoc();
$initial = strtoupper(substr($current_user['name'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard & Academic Profile | Multiphase Flow Lab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .font-serif-title { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-[#090e17] text-slate-100 min-h-screen pb-16">

    <!-- Header Navigation -->
    <header class="bg-[#101724] border-b border-slate-800 px-6 py-4 flex justify-between items-center sticky top-0 z-40">
        <div class="flex items-center space-x-3">
            <span class="w-3 h-3 rounded-full bg-cyan-400"></span>
            <div>
                <h1 class="text-base font-bold text-white tracking-wide">Member Profile & Workspace</h1>
                <p class="text-[11px] text-slate-400 font-mono-code">Multiphase Flow Lab &bull; IIT Kharagpur</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php" target="_blank" class="text-xs text-slate-300 hover:text-white font-mono-code border border-slate-700 bg-slate-800/80 px-3.5 py-1.5 rounded transition">
                <i class="fa-solid fa-globe mr-1"></i> Public Website
            </a>
            <a href="logout.php" class="bg-rose-950/80 text-rose-300 border border-rose-800 text-xs px-3.5 py-1.5 rounded hover:bg-rose-900 transition font-mono-code flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i> Logout
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6 space-y-8 mt-4">

        <?php if ($msg): ?>
            <div class="bg-emerald-950/70 border border-emerald-700/80 text-emerald-300 text-xs px-4 py-3 rounded-xl flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div class="bg-rose-950/70 border border-rose-700/80 text-rose-300 text-xs px-4 py-3 rounded-xl flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-rose-400"></i>
                <span><?php echo $err; ?></span>
            </div>
        <?php endif; ?>

        <!-- ==================== COMPREHENSIVE PROFILE MANAGER ==================== -->
        <div class="bg-[#101724] border border-cyan-500/30 rounded-2xl p-6 md:p-8 shadow-2xl relative overflow-hidden">
            <div class="flex items-center justify-between mb-6 border-b border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-white flex items-center gap-2 uppercase font-mono-code">
                    <i class="fa-solid fa-id-card-clip text-cyan-400"></i> Comprehensive Academic Portfolio & Public Card Settings
                </h2>
                <span class="text-[10px] font-mono-code text-cyan-400 bg-cyan-950/80 border border-cyan-800 px-2.5 py-0.5 rounded">Interactive Homepage Card</span>
            </div>

            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-8 text-xs">
                <input type="hidden" name="update_personal_profile" value="1">

                <!-- Left: Profile Photo Preview & Upload -->
                <div class="lg:col-span-3 flex flex-col items-center justify-start p-5 bg-[#0a0f19] border border-slate-800 rounded-xl text-center h-fit">
                    <div class="relative w-36 h-36 rounded-full overflow-hidden border-2 border-cyan-400 shadow-xl mb-4 bg-slate-900 flex items-center justify-center text-4xl font-bold text-cyan-400">
                        <?php if (!empty($current_user['profile_image']) && file_exists($current_user['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($current_user['profile_image']); ?>?v=<?php echo time(); ?>" alt="Profile Photo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span><?php echo $initial; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <label class="block text-slate-300 uppercase font-mono-code text-[10px] mb-1 font-bold">Profile Photo</label>
                    <input type="file" name="profile_photo" accept="image/*" class="w-full text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-cyan-500/20 file:text-cyan-300 hover:file:bg-cyan-500/30">
                    <span class="text-[9px] text-slate-500 mt-2 font-mono-code leading-tight">Displayed in Team Directory modal</span>
                </div>

                <!-- Right: Profile Fields -->
                <div class="lg:col-span-9 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Full Legal Name *</label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400">
                        </div>
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Course & Duration *</label>
                            <input type="text" name="course_duration" value="<?php echo htmlspecialchars($current_user['course_duration'] ?? 'M.Tech (2025 - 2027)'); ?>" placeholder="e.g. M.Tech (2025 - 2027) or PhD (2024 - Present)" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400 font-mono-code">
                        </div>
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Role / Position</label>
                            <input type="text" disabled value="<?php echo htmlspecialchars($current_user['role'] ?? ''); ?>" class="w-full bg-[#070a10] border border-slate-800 rounded-lg p-2.5 text-cyan-400 font-mono-code cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Institutional Email (Fixed)</label>
                            <input type="text" disabled value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" class="w-full bg-[#070a10] border border-slate-800 rounded-lg p-2.5 text-slate-500 font-mono-code cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Contact Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" placeholder="+91 XXXXXXXXXX" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400">
                        </div>
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Google Scholar URL</label>
                            <input type="url" name="google_scholar_link" value="<?php echo htmlspecialchars($current_user['google_scholar_link'] ?? ''); ?>" placeholder="https://scholar.google.com/..." class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400 font-mono-code">
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-400 uppercase font-mono-code mb-1">Brief Academic Introduction & Research Focus</label>
                        <textarea name="bio" rows="2" placeholder="Tell about your current academic journey, focus area in multiphase flow..." class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400"><?php echo htmlspecialchars($current_user['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Key Technical Skills & Tools</label>
                            <textarea name="technical_skills" rows="2" placeholder="e.g. Particle Image Velocimetry (PIV), COMSOL Multiphysics, ASPEN HYSYS, Python, MATLAB" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400"><?php echo htmlspecialchars($current_user['technical_skills'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-slate-400 uppercase font-mono-code mb-1">Recent & Current Projects</label>
                            <textarea name="recent_projects" rows="2" placeholder="e.g. Numerical simulation of Taylor bubble coalescence; Core-annular flow with non-Newtonian lubricants" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400"><?php echo htmlspecialchars($current_user['recent_projects'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-400 uppercase font-mono-code mb-1">Prior Experience & Industrial Internships</label>
                        <textarea name="experience" rows="2" placeholder="e.g. Summer Internship at IOCL Haldia Refinery; Process modeling at CAPE Lab" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400"><?php echo htmlspecialchars($current_user['experience'] ?? ''); ?></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold px-6 py-2.5 rounded-lg uppercase tracking-wider transition font-mono-code flex items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-floppy-disk"></i> Save & Publish Academic Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- ==================== INSTRUMENT SLOT BOOKING & PUBLICATIONS ==================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Column: Slot Booking -->
            <div class="lg:col-span-6 space-y-6">
                <div class="bg-[#101724] border border-slate-800 rounded-xl p-6 shadow-xl">
                    <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 uppercase font-mono-code border-b border-slate-800 pb-2">
                        <i class="fa-solid fa-calendar-plus text-cyan-400"></i> Reserve Equipment Slot
                    </h3>
                    <form method="POST" class="space-y-3.5 text-xs">
                        <input type="hidden" name="book_slot" value="1">
                        <div>
                            <label class="block text-slate-400 mb-1">Select Instrument</label>
                            <select name="equip_id" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white focus:outline-none focus:border-cyan-400">
                                <?php 
                                $eq_list = $conn->query("SELECT * FROM equipment WHERE status = 'Operational'");
                                if ($eq_list && $eq_list->num_rows > 0):
                                    while($eq = $eq_list->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $eq['equip_id']; ?>"><?php echo htmlspecialchars($eq['name']); ?> (<?php echo $eq['location']; ?>)</option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 mb-1">Date</label>
                                <input type="date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code focus:outline-none focus:border-cyan-400">
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">Slot</label>
                                <select name="time_slot" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code focus:outline-none focus:border-cyan-400">
                                    <option value="09:00 AM - 12:00 PM">09:00 AM - 12:00 PM</option>
                                    <option value="01:00 PM - 04:00 PM">01:00 PM - 04:00 PM</option>
                                    <option value="04:30 PM - 07:30 PM">04:30 PM - 07:30 PM</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Experimental Objective</label>
                            <textarea name="purpose" rows="2" placeholder="Brief objective of the experiment run..." class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white focus:outline-none focus:border-cyan-400"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg uppercase tracking-wider font-mono-code transition">
                            Book Slot
                        </button>
                    </form>
                </div>

                <div class="bg-[#101724] border border-slate-800 rounded-xl p-6 shadow-xl">
                    <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2 uppercase font-mono-code border-b border-slate-800 pb-2">
                        <i class="fa-solid fa-clock-rotate-left text-cyan-400"></i> My Active Bookings
                    </h3>
                    <div class="overflow-x-auto text-xs">
                        <table class="w-full text-left">
                            <thead class="text-slate-400 font-mono-code uppercase text-[10px] border-b border-slate-800">
                                <tr>
                                    <th class="py-2">Instrument</th>
                                    <th class="py-2">Date</th>
                                    <th class="py-2">Slot</th>
                                    <th class="py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-[11px] text-slate-300">
                                <?php
                                $b_res = $conn->query("SELECT b.*, e.name as eq_name FROM bookings b JOIN equipment e ON b.equip_id = e.equip_id WHERE b.user_id = $user_id ORDER BY b.booking_date DESC LIMIT 4");
                                if ($b_res && $b_res->num_rows > 0):
                                    while($b = $b_res->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td class="py-2.5 font-medium text-white"><?php echo htmlspecialchars($b['eq_name']); ?></td>
                                        <td class="py-2.5 font-mono-code text-cyan-400"><?php echo $b['booking_date']; ?></td>
                                        <td class="py-2.5 font-mono-code text-slate-400"><?php echo $b['time_slot']; ?></td>
                                        <td class="py-2.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono-code <?php echo ($b['status'] === 'Approved') ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800'; ?>">
                                                <?php echo $b['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                    echo '<tr><td colspan="4" class="py-4 text-center text-slate-500 font-mono-code">No active slot reservations.</td></tr>';
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Publications -->
            <div class="lg:col-span-6 space-y-6">
                <div class="bg-[#101724] border border-slate-800 rounded-xl p-6 shadow-xl">
                    <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2 uppercase font-mono-code border-b border-slate-800 pb-2">
                        <i class="fa-solid fa-file-circle-plus text-cyan-400"></i> Add Research Publication
                    </h3>
                    <form method="POST" class="space-y-3 text-xs">
                        <input type="hidden" name="add_paper" value="1">
                        <div>
                            <label class="block text-slate-400 mb-1">Paper Title</label>
                            <input type="text" name="title" required placeholder="Publication title..." class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white focus:outline-none focus:border-cyan-400">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Authors</label>
                            <input type="text" name="authors" required placeholder="e.g. Das, A., & Das, G." class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white focus:outline-none focus:border-cyan-400">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-400 mb-1">Journal</label>
                                <input type="text" name="journal_name" required placeholder="Journal name" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white focus:outline-none focus:border-cyan-400">
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">Year</label>
                                <input type="number" name="year" value="<?php echo date('Y'); ?>" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code focus:outline-none focus:border-cyan-400">
                            </div>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">DOI Reference</label>
                            <input type="text" name="doi" placeholder="10.1016/..." class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code focus:outline-none focus:border-cyan-400">
                        </div>
                        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold py-2.5 rounded-lg uppercase tracking-wider font-mono-code transition">
                            Submit Publication
                        </button>
                    </form>
                </div>

                <div class="bg-[#101724] border border-slate-800 rounded-xl p-6 shadow-xl">
                    <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2 uppercase font-mono-code border-b border-slate-800 pb-2">
                        <i class="fa-solid fa-newspaper text-cyan-400"></i> My Uploaded Publications
                    </h3>
                    <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                        <?php
                        $my_pubs = $conn->query("SELECT * FROM publications WHERE added_by = $user_id ORDER BY year DESC, pub_id DESC");
                        if ($my_pubs && $my_pubs->num_rows > 0):
                            while($p = $my_pubs->fetch_assoc()):
                        ?>
                            <div class="bg-[#0a0f19] border border-slate-800 p-2.5 rounded-lg flex justify-between items-start text-xs">
                                <div>
                                    <span class="text-cyan-400 font-mono-code font-bold text-[10px]"><?php echo $p['year']; ?></span>
                                    <h4 class="text-white font-semibold text-xs"><?php echo htmlspecialchars($p['title']); ?></h4>
                                    <p class="text-slate-400 text-[10px] italic"><?php echo htmlspecialchars($p['journal_name']); ?></p>
                                </div>
                                <a href="dashboard.php?delete_pub=<?php echo $p['pub_id']; ?>" onclick="return confirm('Remove publication?');" class="text-slate-500 hover:text-rose-400 p-1">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>
                        <?php 
                            endwhile;
                        else:
                            echo '<p class="text-slate-500 text-xs font-mono-code py-3">No publications added yet.</p>';
                        endif;
                        ?>
                    </div>
                </div>
            </div>

        </div>

    </div>
</body>
</html>