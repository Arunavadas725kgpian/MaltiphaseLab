<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['PI', 'Admin', 'Co-Admin'])) {
    header("Location: login.php?tab=admin");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];
$msg = '';
$err = '';

$active_tab = $_GET['tab'] ?? 'slider';

// ==================== ০. ABOUT LAB CONTENT UPDATE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_about_content'])) {
    $active_tab = 'about_editor';
    $about_title = trim($_POST['about_title'] ?? 'The Kinetic Continuum');
    $short_summary = trim($_POST['short_summary'] ?? '');
    $full_description = trim($_POST['full_description'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    $facilities = trim($_POST['facilities'] ?? '');

    $stmt = $conn->prepare("UPDATE lab_about SET title = ?, short_summary = ?, full_description = ?, mission = ?, facilities = ? WHERE id = 1");
    $stmt->bind_param("sssss", $about_title, $short_summary, $full_description, $mission, $facilities);
    if ($stmt->execute()) {
        $msg = "About Lab content updated successfully!";
    } else {
        $err = "Failed to update About content.";
    }
}

// ==================== ১. GALLERY MANAGER ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gallery_item'])) {
    $active_tab = 'gallery';
    $year = intval($_POST['year'] ?? date('Y'));
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($title) && isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['gallery_image']['tmp_name'];
        $fileName = $_FILES['gallery_image']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $dir = 'uploads/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $newFileName = 'gallery_' . $year . '_' . time() . '.' . $ext;
            $dest = $dir . $newFileName;

            if (move_uploaded_file($fileTmp, $dest)) {
                $ins = $conn->prepare("INSERT INTO lab_gallery (year, title, description, image_path) VALUES (?, ?, ?, ?)");
                $ins->bind_param("isss", $year, $title, $description, $dest);
                $ins->execute();
                $msg = "Gallery photo published successfully!";
            } else {
                $err = "Failed to save the image.";
            }
        } else {
            $err = "Only JPG, JPEG, PNG, and WEBP formats are allowed.";
        }
    } else {
        $err = "Title and photo file are required.";
    }
}

if (isset($_GET['delete_gallery'])) {
    $active_tab = 'gallery';
    $gid = intval($_GET['delete_gallery']);
    $conn->query("DELETE FROM lab_gallery WHERE gallery_id = $gid");
    $msg = "Gallery photograph deleted.";
}

// ==================== ২. LAB LOGO DIRECT UPLOAD ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_lab_logo'])) {
    $active_tab = 'lab_logo';
    if (isset($_FILES['lab_logo_file']) && $_FILES['lab_logo_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['lab_logo_file']['tmp_name'];
        $fileName = $_FILES['lab_logo_file']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $destPath = 'lab_logo.' . $fileExt;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                copy($destPath, 'lab_logo.png');
                copy($destPath, 'lab_logo.jpg');
                copy($destPath, 'lab_logo.jpeg');
                copy($destPath, 'logo.jpeg');
                $msg = "Multiphase Lab Logo successfully uploaded and applied to Homepage!";
            } else {
                $err = "Server failed to save the uploaded image file.";
            }
        } else {
            $err = "Only JPG, JPEG, PNG, and WEBP formats are allowed.";
        }
    } else {
        $err = "Please select an image file to upload.";
    }
}

// ==================== ৩. QUICK MOVE TO OLD / CURRENT ====================
if (isset($_GET['toggle_archive'])) {
    $active_tab = 'research';
    $toggle_id = intval($_GET['toggle_archive']);
    $to_status = ($_GET['to'] === 'Old') ? 'Old' : 'Current';
    $new_status_text = ($to_status === 'Old') ? 'Completed' : 'Active';

    $stmt = $conn->prepare("UPDATE research_themes SET category = ?, status = ? WHERE theme_id = ?");
    $stmt->bind_param("ssi", $to_status, $new_status_text, $toggle_id);
    if ($stmt->execute()) {
        $msg = "Research entry updated! Active theme numbering auto-recalculated.";
    }
}

// ==================== ৪. RESEARCH THEMES MANAGEMENT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_research_theme'])) {
    $active_tab = 'research';
    $theme_code = trim($_POST['theme_code'] ?? 'THEME');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bullet_1 = trim($_POST['bullet_1'] ?? '');
    $bullet_2 = trim($_POST['bullet_2'] ?? '');
    $explore_link = trim($_POST['explore_link'] ?? '#publications');
    $status = trim($_POST['status'] ?? 'Active');
    $category = trim($_POST['category'] ?? 'Current');

    if ($status === 'Completed') {
        $category = 'Old';
    }

    if (!empty($title) && !empty($description) && isset($_FILES['theme_image']) && $_FILES['theme_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['theme_image']['tmp_name'];
        $fileName = $_FILES['theme_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newFileName = 'theme_new_' . time() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $stmt = $conn->prepare("INSERT INTO research_themes (theme_code, title, description, bullet_1, bullet_2, explore_link, image_path, status, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $theme_code, $title, $description, $bullet_1, $bullet_2, $explore_link, $destPath, $status, $category);
                $stmt->execute();
                $msg = "New research entry added under " . htmlspecialchars($category) . " category!";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_research_theme'])) {
    $active_tab = 'research';
    $theme_id = intval($_POST['theme_id'] ?? 0);
    $theme_code = trim($_POST['theme_code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bullet_1 = trim($_POST['bullet_1'] ?? '');
    $bullet_2 = trim($_POST['bullet_2'] ?? '');
    $explore_link = trim($_POST['explore_link'] ?? '#publications');
    $status = trim($_POST['status'] ?? 'Active');
    $category = trim($_POST['category'] ?? 'Current');

    if ($status === 'Completed') {
        $category = 'Old';
    }

    $image_path = null;
    if (isset($_FILES['theme_image']) && $_FILES['theme_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['theme_image']['tmp_name'];
        $fileName = $_FILES['theme_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newFileName = 'theme_' . $theme_id . '_' . time() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = $destPath;
            }
        }
    }

    if ($theme_id > 0 && !empty($title)) {
        if ($image_path) {
            $stmt = $conn->prepare("UPDATE research_themes SET theme_code = ?, title = ?, description = ?, bullet_1 = ?, bullet_2 = ?, explore_link = ?, image_path = ?, status = ?, category = ? WHERE theme_id = ?");
            $stmt->bind_param("sssssssssi", $theme_code, $title, $description, $bullet_1, $bullet_2, $explore_link, $image_path, $status, $category, $theme_id);
        } else {
            $stmt = $conn->prepare("UPDATE research_themes SET theme_code = ?, title = ?, description = ?, bullet_1 = ?, bullet_2 = ?, explore_link = ?, status = ?, category = ? WHERE theme_id = ?");
            $stmt->bind_param("ssssssssi", $theme_code, $title, $description, $bullet_1, $bullet_2, $explore_link, $status, $category, $theme_id);
        }
        $stmt->execute();
        $msg = "Research entry updated successfully!";
    }
}

if (isset($_GET['delete_theme'])) {
    $active_tab = 'research';
    $del_tid = intval($_GET['delete_theme']);
    $conn->query("DELETE FROM research_themes WHERE theme_id = $del_tid");
    $msg = "Research entry removed.";
}

// ==================== ৫. CO-ADMIN MANAGEMENT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coadmin'])) {
    $active_tab = 'coadmin';
    if ($current_user_role === 'PI' || $current_user_role === 'Admin') {
        $co_name = trim($_POST['co_name'] ?? '');
        $co_email = trim($_POST['co_email'] ?? '');
        $co_password = trim($_POST['co_password'] ?? '');

        if (!empty($co_name) && !empty($co_email) && !empty($co_password)) {
            $pass_hash = md5($co_password);
            $desig = 'Laboratory Co-Administrator';
            $role = 'Co-Admin';
            $approved = 1;

            $ins_co = $conn->prepare("INSERT INTO users (name, email, password_hash, role, designation, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
            $ins_co->bind_param("sssssi", $co_name, $co_email, $pass_hash, $role, $desig, $approved);
            if ($ins_co->execute()) {
                $msg = "Co-Admin authorized successfully!";
            }
        }
    }
}

if (isset($_GET['delete_coadmin'])) {
    $active_tab = 'coadmin';
    if ($current_user_role === 'PI' || $current_user_role === 'Admin') {
        $del_co_id = intval($_GET['delete_coadmin']);
        $conn->query("DELETE FROM users WHERE user_id = $del_co_id AND role = 'Co-Admin'");
        $msg = "Co-Admin access revoked.";
    }
}

// ==================== ৬. PUBLICATION MANAGEMENT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_publication'])) {
    $active_tab = 'publications';
    $year = intval($_POST['year'] ?? date('Y'));
    $title = trim($_POST['title'] ?? '');
    $authors = trim($_POST['authors'] ?? '');
    $journal_name = trim($_POST['journal_name'] ?? '');
    $pub_type = trim($_POST['pub_type'] ?? 'Journal');
    $doi = trim($_POST['doi'] ?? '');

    if (!empty($title) && !empty($authors)) {
        $p_stmt = $conn->prepare("INSERT INTO publications (year, title, authors, journal_name, pub_type, doi, added_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $p_stmt->bind_param("isssssi", $year, $title, $authors, $journal_name, $pub_type, $doi, $current_user_id);
        $p_stmt->execute();
        $msg = "Publication added successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_publication'])) {
    $active_tab = 'publications';
    $pub_id = intval($_POST['pub_id'] ?? 0);
    $year = intval($_POST['year'] ?? date('Y'));
    $title = trim($_POST['title'] ?? '');
    $authors = trim($_POST['authors'] ?? '');
    $journal_name = trim($_POST['journal_name'] ?? '');
    $pub_type = trim($_POST['pub_type'] ?? 'Journal');
    $doi = trim($_POST['doi'] ?? '');

    if ($pub_id > 0 && !empty($title)) {
        $up_p = $conn->prepare("UPDATE publications SET year = ?, title = ?, authors = ?, journal_name = ?, pub_type = ?, doi = ? WHERE pub_id = ?");
        $up_p->bind_param("isssssi", $year, $title, $authors, $journal_name, $pub_type, $doi, $pub_id);
        $up_p->execute();
        $msg = "Publication updated!";
    }
}

if (isset($_GET['delete_pub'])) {
    $active_tab = 'publications';
    $del_pid = intval($_GET['delete_pub']);
    $conn->query("DELETE FROM publications WHERE pub_id = $del_pid");
    $msg = "Publication removed.";
}

// ==================== ৭. HOMEPAGE CAROUSEL SLIDES (ADD, UPDATE & DELETE) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slide'])) {
    $active_tab = 'slider';
    $badge = trim($_POST['badge_text'] ?? 'Lab Highlight');
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '#');
    $link_label = trim($_POST['link_label'] ?? 'Learn More');

    if (!empty($title) && isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['slide_image']['tmp_name'];
        $fileName = $_FILES['slide_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $newFileName = 'slide_' . time() . '.' . $fileExt;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $stmt = $conn->prepare("INSERT INTO carousel_slides (badge_text, title, subtitle, image_path, link_url, link_label) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $badge, $title, $subtitle, $destPath, $link_url, $link_label);
            $stmt->execute();
            $msg = "Slider poster added successfully!";
        }
    } else {
        $err = "Title and slide image are required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_slide'])) {
    $active_tab = 'slider';
    $slide_id = intval($_POST['slide_id'] ?? 0);
    $badge = trim($_POST['badge_text'] ?? 'Lab Highlight');
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '#');
    $link_label = trim($_POST['link_label'] ?? 'Learn More');

    $image_path = null;
    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['slide_image']['tmp_name'];
        $fileName = $_FILES['slide_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newFileName = 'slide_' . $slide_id . '_' . time() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = $destPath;
            }
        }
    }

    if ($slide_id > 0 && !empty($title)) {
        if ($image_path) {
            $stmt = $conn->prepare("UPDATE carousel_slides SET badge_text = ?, title = ?, subtitle = ?, link_url = ?, link_label = ?, image_path = ? WHERE slide_id = ?");
            $stmt->bind_param("ssssssi", $badge, $title, $subtitle, $link_url, $link_label, $image_path, $slide_id);
        } else {
            $stmt = $conn->prepare("UPDATE carousel_slides SET badge_text = ?, title = ?, subtitle = ?, link_url = ?, link_label = ? WHERE slide_id = ?");
            $stmt->bind_param("sssssi", $badge, $title, $subtitle, $link_url, $link_label, $slide_id);
        }
        $stmt->execute();
        $msg = "Slider poster updated successfully!";
    }
}

if (isset($_GET['delete_slide'])) {
    $active_tab = 'slider';
    $del_id = intval($_GET['delete_slide']);
    $conn->query("DELETE FROM carousel_slides WHERE slide_id = $del_id");
    $msg = "Poster slide removed.";
}

// ==================== ৮. PI PROFILE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pi_profile'])) {
    $active_tab = 'pi_profile';
    $pi_name = trim($_POST['pi_name'] ?? '');
    $pi_designation = trim($_POST['pi_designation'] ?? '');
    $pi_email = trim($_POST['pi_email'] ?? '');
    $pi_phone = trim($_POST['pi_phone'] ?? '');
    $pi_bio = trim($_POST['pi_bio'] ?? '');
    $pi_scholar = trim($_POST['pi_scholar'] ?? '');

    $image_path = null;
    if (isset($_FILES['pi_image']) && $_FILES['pi_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pi_image']['tmp_name'];
        $fileName = $_FILES['pi_image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $newFileName = 'pi_' . time() . '.' . $fileExt;
        $destPath = $uploadDir . $newFileName;
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $image_path = $destPath;
        }
    }

    if ($image_path) {
        $up_stmt = $conn->prepare("UPDATE users SET name = ?, designation = ?, email = ?, phone = ?, bio = ?, google_scholar_link = ?, profile_image = ? WHERE role IN ('PI', 'Admin') ORDER BY user_id ASC LIMIT 1");
        $up_stmt->bind_param("sssssss", $pi_name, $pi_designation, $pi_email, $pi_phone, $pi_bio, $pi_scholar, $image_path);
    } else {
        $up_stmt = $conn->prepare("UPDATE users SET name = ?, designation = ?, email = ?, phone = ?, bio = ?, google_scholar_link = ? WHERE role IN ('PI', 'Admin') ORDER BY user_id ASC LIMIT 1");
        $up_stmt->bind_param("ssssss", $pi_name, $pi_designation, $pi_email, $pi_phone, $pi_bio, $pi_scholar);
    }
    $up_stmt->execute();
    $msg = "PI Profile updated successfully!";
}

// ==================== ৯. MEMBER APPROVALS ====================
if (isset($_GET['approve_id'])) {
    $active_tab = 'members';
    $uid = intval($_GET['approve_id']);
    $conn->query("UPDATE users SET is_approved = 1 WHERE user_id = $uid");
    $msg = "Member verified!";
}
if (isset($_GET['reject_id'])) {
    $active_tab = 'members';
    $uid = intval($_GET['reject_id']);
    $conn->query("DELETE FROM users WHERE user_id = $uid");
    $msg = "Member rejected.";
}

// Data fetch
$about_query = $conn->query("SELECT * FROM lab_about WHERE id = 1 LIMIT 1");
$about_admin = ($about_query && $about_query->num_rows > 0) ? $about_query->fetch_assoc() : null;

$gallery_all = $conn->query("SELECT * FROM lab_gallery ORDER BY year DESC, gallery_id DESC");
$pi = $conn->query("SELECT * FROM users WHERE role IN ('PI', 'Admin') ORDER BY user_id ASC LIMIT 1")->fetch_assoc();
$current_themes = $conn->query("SELECT * FROM research_themes WHERE category = 'Current' OR category IS NULL ORDER BY display_order ASC, theme_id ASC");
$old_themes = $conn->query("SELECT * FROM research_themes WHERE category = 'Old' ORDER BY theme_id DESC");
$publications = $conn->query("SELECT * FROM publications ORDER BY year DESC, pub_id DESC");
$slides = $conn->query("SELECT * FROM carousel_slides ORDER BY slide_id DESC");
$pending_query = $conn->query("SELECT * FROM users WHERE is_approved = 0 ORDER BY user_id DESC");
$coadmins_query = $conn->query("SELECT * FROM users WHERE role = 'Co-Admin' ORDER BY user_id DESC");

$total_pubs = $publications ? $publications->num_rows : 0;
$total_slides = $slides ? $slides->num_rows : 0;
$total_pending = $pending_query ? $pending_query->num_rows : 0;
$total_coadmins = $coadmins_query ? $coadmins_query->num_rows : 0;
$total_current = $current_themes ? $current_themes->num_rows : 0;
$total_old = $old_themes ? $old_themes->num_rows : 0;

$preview_logo = 'lab_logo.png';
if (file_exists('lab_logo.jpg')) $preview_logo = 'lab_logo.jpg';
elseif (file_exists('lab_logo.jpeg')) $preview_logo = 'lab_logo.jpeg';
elseif (file_exists('logo.jpeg')) $preview_logo = 'logo.jpeg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Center | Multiphase Flow Lab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .sidebar-btn.active {
            background: linear-gradient(90deg, rgba(6, 182, 212, 0.2) 0%, rgba(6, 182, 212, 0.05) 100%);
            color: #22d3ee;
            border-left-color: #06b6d4;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-[#090e17] text-slate-100 min-h-screen pb-16">

    <header class="bg-[#101724] border-b border-slate-800 px-6 py-4 flex justify-between items-center sticky top-0 z-40">
        <div class="flex items-center space-x-3">
            <span class="w-3 h-3 rounded-full bg-cyan-400 animate-pulse"></span>
            <div>
                <h1 class="text-base font-bold text-white tracking-wide">Admin Control Center</h1>
                <p class="text-[11px] text-slate-400 font-mono-code">
                    Multiphase Flow Lab &bull; IIT Kharagpur 
                    <span class="text-cyan-400 font-bold ml-1">[Role: <?php echo htmlspecialchars($current_user_role); ?>]</span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php" target="_blank" class="text-xs text-slate-300 hover:text-white font-mono-code border border-slate-700 bg-slate-800/80 px-3.5 py-1.5 rounded transition">
                <i class="fa-solid fa-globe mr-1"></i> Public Website
            </a>
            <a href="logout.php" class="bg-rose-950/80 text-rose-300 border border-rose-800 text-xs px-4 py-1.5 rounded hover:bg-rose-900 transition font-mono-code">
                <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout
            </a>
        </div>
    </header>

    <div class="max-w-7xl mx-auto p-6 mt-2">

        <?php if ($msg): ?>
            <div class="bg-emerald-950/70 border border-emerald-700 text-emerald-300 text-xs px-4 py-3 rounded-xl flex items-center gap-2 shadow-lg mb-6">
                <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i> <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div class="bg-rose-950/70 border border-rose-700 text-rose-300 text-xs px-4 py-3 rounded-xl flex items-center gap-2 shadow-lg mb-6">
                <i class="fa-solid fa-circle-xmark text-rose-400 text-sm"></i> <span><?php echo $err; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- SIDEBAR -->
            <div class="lg:col-span-3 bg-[#101724] border border-slate-800 rounded-2xl p-3 shadow-xl space-y-1.5 font-mono-code text-xs sticky top-20">
                <div class="px-3 py-2 text-[10px] text-slate-500 uppercase tracking-widest border-b border-slate-800/80 mb-2">
                    Navigation Gateway
                </div>

                <!-- 1. ABOUT LAB EDITOR -->
                <button onclick="switchTab('about_editor')" id="btn-about_editor" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-book-bookmark text-cyan-400 text-sm"></i>
                        <span>About Lab Editor</span>
                    </span>
                    <span class="text-[9px] bg-cyan-950 text-cyan-300 border border-cyan-800 px-1.5 py-0.5 rounded font-bold uppercase">Overview</span>
                </button>

                <!-- 2. GALLERY MANAGER -->
                <button onclick="switchTab('gallery')" id="btn-gallery" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-regular fa-images text-amber-400 text-sm"></i>
                        <span>Gallery Manager</span>
                    </span>
                    <span class="text-[9px] bg-amber-950 text-amber-300 border border-amber-800 px-1.5 py-0.5 rounded uppercase">Photos</span>
                </button>

                <!-- 3. LAB LOGO -->
                <button onclick="switchTab('lab_logo')" id="btn-lab_logo" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-circle-notch text-cyan-400 text-sm"></i>
                        <span>Lab Logo Upload</span>
                    </span>
                    <span class="text-[9px] bg-cyan-950 text-cyan-300 border border-cyan-800 px-1.5 py-0.5 rounded font-bold uppercase">Header</span>
                </button>

                <!-- 4. PI PROFILE -->
                <button onclick="switchTab('pi_profile')" id="btn-pi_profile" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-user-gear text-slate-400 text-xs"></i>
                        <span>PI Profile Details</span>
                    </span>
                    <span class="text-[9px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded uppercase">Lead</span>
                </button>

                <!-- 5. CO-ADMIN -->
                <button onclick="switchTab('coadmin')" id="btn-coadmin" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-user-shield text-xs"></i>
                        <span>Co-Admin Access</span>
                    </span>
                    <?php if ($total_coadmins > 0): ?>
                        <span class="bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full text-[10px]"><?php echo $total_coadmins; ?></span>
                    <?php endif; ?>
                </button>

                <!-- 6. RESEARCH -->
                <button onclick="switchTab('research')" id="btn-research" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-atom text-xs"></i>
                        <span>Research (Current & Old)</span>
                    </span>
                    <span class="bg-cyan-950 text-cyan-300 border border-cyan-800/60 px-2 py-0.5 rounded-full text-[10px]"><?php echo ($total_current + $total_old); ?></span>
                </button>

                <!-- 7. PUBLICATIONS -->
                <button onclick="switchTab('publications')" id="btn-publications" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-book-open text-xs"></i>
                        <span>Publications</span>
                    </span>
                    <span class="bg-cyan-950 text-cyan-300 border border-cyan-800/60 px-2 py-0.5 rounded-full text-[10px]"><?php echo $total_pubs; ?></span>
                </button>

                <!-- 8. HOMEPAGE SLIDER (ACTIVE WITH COUNT) -->
                <button onclick="switchTab('slider')" id="btn-slider" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-images text-cyan-400 text-xs"></i>
                        <span class="font-bold text-white">Homepage Slider</span>
                    </span>
                    <span class="bg-cyan-950 text-cyan-300 border border-cyan-800/60 px-2 py-0.5 rounded-full text-[10px] font-bold"><?php echo $total_slides; ?></span>
                </button>

                <!-- 9. MEMBERS -->
                <button onclick="switchTab('members')" id="btn-members" class="sidebar-btn w-full text-left px-3.5 py-3 rounded-xl border-l-2 border-transparent text-slate-400 hover:text-white transition flex items-center justify-between group">
                    <span class="flex items-center gap-2.5">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        <span>Members</span>
                    </span>
                    <?php if ($total_pending > 0): ?>
                        <span class="bg-amber-950 text-amber-300 border border-amber-800 px-1.5 py-0.5 rounded text-[10px] font-bold animate-pulse"><?php echo $total_pending; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- RIGHT CONTENT AREA -->
            <div class="lg:col-span-9 space-y-6">

                <!-- ==================== VIEW 1: HOMEPAGE SLIDER (WITH FULL HISTORY & EDIT) ==================== -->
                <div id="view-slider" class="tab-view hidden space-y-6">
                    <div class="bg-[#101724] border border-cyan-500/40 rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-white uppercase font-mono-code flex items-center gap-2">
                                    <i class="fa-solid fa-images text-cyan-400"></i> Homepage Carousel Posters
                                </h2>
                                <p class="text-xs text-slate-400 mt-0.5">Upload new posters or edit & remove existing slides. Timer changes slides automatically on homepage!</p>
                            </div>
                            <span class="text-xs font-mono-code text-cyan-300 bg-cyan-950 border border-cyan-800 px-2.5 py-1 rounded">
                                Active: <?php echo $total_slides; ?>
                            </span>
                        </div>

                        <!-- UPLOAD NEW SLIDE FORM -->
                        <form method="POST" enctype="multipart/form-data" class="bg-[#0a0f19] p-5 rounded-xl border border-slate-800 space-y-4 text-xs">
                            <input type="hidden" name="add_slide" value="1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-mono-code mb-1 font-bold">Badge Text</label>
                                    <input type="text" name="badge_text" placeholder="e.g. WORKSHOP / RESEARCH" required class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white font-mono-code">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-slate-300 font-mono-code mb-1 font-bold">Poster Title *</label>
                                    <input type="text" name="title" required placeholder="Main Highlight Title" class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-mono-code mb-1">Subtitle / Summary (Optional)</label>
                                    <input type="text" name="subtitle" placeholder="Short description..." class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-mono-code mb-1">Explore URL / Button Link</label>
                                    <input type="text" name="link_url" value="research.php" placeholder="e.g. research.php" class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white font-mono-code">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-mono-code mb-1 font-bold">Upload Slide Image (1600x900 recommended) *</label>
                                <input type="file" name="slide_image" accept="image/*" required class="w-full text-xs text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-cyan-500 file:text-slate-950 hover:file:bg-cyan-400 cursor-pointer bg-slate-900 p-2 rounded-lg border border-slate-700">
                            </div>

                            <div>
                                <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold px-6 py-2.5 rounded-lg uppercase tracking-wider font-mono-code transition shadow-lg flex items-center gap-2">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload Poster
                                </button>
                            </div>
                        </form>

                        <!-- PUBLISHED SLIDES HISTORY LIST -->
                        <div class="space-y-3 pt-2">
                            <h3 class="text-xs uppercase font-mono-code font-bold text-slate-300 flex items-center justify-between">
                                <span>Active Slider Posters (Total: <?php echo $total_slides; ?>)</span>
                                <span class="text-slate-500 text-[10px]">Displayed on homepage carousel</span>
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php if ($slides && $slides->num_rows > 0): ?>
                                    <?php while ($sl = $slides->fetch_assoc()): 
                                        $sl_json = htmlspecialchars(json_encode($sl), ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <div class="bg-[#0a0f19] border border-slate-800 rounded-xl overflow-hidden flex flex-col justify-between hover:border-slate-700 transition">
                                            <div class="h-36 w-full relative bg-slate-950">
                                                <img src="<?php echo htmlspecialchars($sl['image_path']); ?>" alt="<?php echo htmlspecialchars($sl['title']); ?>" class="w-full h-full object-cover">
                                                <span class="absolute top-2 left-2 bg-black/80 text-cyan-300 font-mono-code text-[10px] px-2 py-0.5 rounded font-bold uppercase">
                                                    <?php echo htmlspecialchars($sl['badge_text']); ?>
                                                </span>
                                            </div>

                                            <div class="p-3.5 space-y-2 text-xs flex-1 flex flex-col justify-between">
                                                <div>
                                                    <h4 class="font-bold text-white text-sm leading-snug line-clamp-1"><?php echo htmlspecialchars($sl['title']); ?></h4>
                                                    <?php if (!empty($sl['subtitle'])): ?>
                                                        <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5"><?php echo htmlspecialchars($sl['subtitle']); ?></p>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between">
                                                    <span class="text-[10px] font-mono-code text-cyan-400 truncate max-w-[150px]">
                                                        <?php echo htmlspecialchars($sl['link_url']); ?>
                                                    </span>
                                                    <div class="space-x-1 whitespace-nowrap">
                                                        <button type="button" onclick='openEditSlideModal(<?php echo $sl_json; ?>)' class="bg-cyan-950 text-cyan-300 border border-cyan-700 hover:bg-cyan-900 px-2.5 py-1 rounded text-xs font-mono-code">
                                                            <i class="fa-regular fa-pen-to-square mr-0.5"></i> Edit
                                                        </button>
                                                        <a href="admin_panel.php?delete_slide=<?php echo $sl['slide_id']; ?>&tab=slider" onclick="return confirm('Delete this slider poster?');" class="bg-rose-950 text-rose-300 border border-rose-800 hover:bg-rose-900 px-2.5 py-1 rounded text-xs font-mono-code">
                                                            <i class="fa-regular fa-trash-can"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center border-2 border-dashed border-slate-800 rounded-xl col-span-2 text-slate-500 font-mono-code text-xs">
                                        No slider posters uploaded yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ==================== VIEW 2: ABOUT LAB CONTENT EDITOR ==================== -->
                <div id="view-about_editor" class="tab-view hidden space-y-6">
                    <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-white uppercase font-mono-code flex items-center gap-2">
                                    <i class="fa-solid fa-book-bookmark text-cyan-400"></i> About Laboratory Charter & Content
                                </h2>
                                <p class="text-xs text-slate-400 mt-0.5">Control both the Homepage Brief Overview and Dedicated Full Page Details</p>
                            </div>
                            <div class="flex items-center gap-3 font-mono-code text-xs">
                                <a href="index.php#about" target="_blank" class="text-slate-400 hover:text-white underline">Homepage View</a>
                                <a href="about.php" target="_blank" class="text-cyan-400 hover:underline">Dedicated Page View &rarr;</a>
                            </div>
                        </div>

                        <form method="POST" class="space-y-4 text-xs">
                            <input type="hidden" name="update_about_content" value="1">
                            <div>
                                <label class="block text-slate-300 font-mono-code mb-1 font-bold">Main Section Title</label>
                                <input type="text" name="about_title" value="<?php echo htmlspecialchars($about_admin['title'] ?? 'The Kinetic Continuum'); ?>" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2.5 text-white font-serif-title text-base">
                            </div>
                            <div class="bg-[#0a0f19] p-4 rounded-xl border border-cyan-500/30 space-y-2">
                                <label class="block text-cyan-300 font-mono-code font-bold">1. Homepage Brief Summary (2-3 lines)</label>
                                <textarea name="short_summary" rows="3" required class="w-full bg-[#101724] border border-slate-700 rounded-lg p-3 text-slate-200 leading-relaxed"><?php echo htmlspecialchars($about_admin['short_summary'] ?? ''); ?></textarea>
                            </div>
                            <div class="bg-[#0a0f19] p-4 rounded-xl border border-slate-800 space-y-2">
                                <label class="block text-slate-200 font-mono-code font-bold">2. Dedicated Page Detailed Description (Visible on about.php)</label>
                                <textarea name="full_description" rows="6" required class="w-full bg-[#101724] border border-slate-700 rounded-lg p-3 text-slate-200 leading-relaxed"><?php echo htmlspecialchars($about_admin['full_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-[#0a0f19] p-4 rounded-xl border border-slate-800 space-y-2">
                                    <label class="block text-slate-300 font-mono-code font-bold">3. Scientific Mission</label>
                                    <textarea name="mission" rows="4" class="w-full bg-[#101724] border border-slate-700 rounded-lg p-3 text-slate-200 leading-relaxed"><?php echo htmlspecialchars($about_admin['mission'] ?? ''); ?></textarea>
                                </div>
                                <div class="bg-[#0a0f19] p-4 rounded-xl border border-slate-800 space-y-2">
                                    <label class="block text-slate-300 font-mono-code font-bold">4. Instrumentation & Facilities</label>
                                    <textarea name="facilities" rows="4" class="w-full bg-[#101724] border border-slate-700 rounded-lg p-3 text-slate-200 leading-relaxed"><?php echo htmlspecialchars($about_admin['facilities'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl uppercase tracking-wider font-mono-code transition shadow-lg">Save About Content</button>
                        </form>
                    </div>
                </div>

                <!-- ==================== VIEW 3: GALLERY MANAGER ==================== -->
                <div id="view-gallery" class="tab-view hidden space-y-6">
                    <div class="bg-[#101724] border border-amber-500/40 rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                            <div>
                                <h2 class="text-base font-bold text-white uppercase font-mono-code flex items-center gap-2">
                                    <i class="fa-regular fa-images text-amber-400"></i> Annual Lab Gallery Publisher
                                </h2>
                                <p class="text-xs text-slate-400 mt-0.5">Upload photos for conferences, awards, degree celebrations, and lab trips</p>
                            </div>
                            <a href="gallery.php" target="_blank" class="text-xs font-mono-code text-cyan-400 hover:underline">View Public Gallery &rarr;</a>
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="bg-[#0a0f19] p-5 rounded-xl border border-slate-800 space-y-4 text-xs">
                            <input type="hidden" name="add_gallery_item" value="1">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-slate-300 font-mono-code mb-1 font-bold">Event Year *</label>
                                    <input type="number" name="year" value="<?php echo date('Y'); ?>" required class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white font-mono-code">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-slate-300 font-mono-code mb-1 font-bold">Event Title *</label>
                                    <input type="text" name="title" required placeholder="Title" class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-mono-code mb-1 font-bold">Select Photograph *</label>
                                <input type="file" name="gallery_image" accept="image/*" required class="w-full text-xs text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400 cursor-pointer bg-slate-900 p-2 rounded-lg border border-slate-700">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-mono-code mb-1">Description</label>
                                <textarea name="description" rows="2" placeholder="Details..." class="w-full bg-[#101724] border border-slate-700 rounded-lg p-2.5 text-white"></textarea>
                            </div>
                            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-2.5 rounded-lg uppercase tracking-wider font-mono-code transition">Publish to Gallery</button>
                        </form>
                        <div class="space-y-3">
                            <h3 class="text-xs uppercase font-mono-code font-bold text-slate-400">Published Photos (<?php echo ($gallery_all ? $gallery_all->num_rows : 0); ?>)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[450px] overflow-y-auto pr-1">
                                <?php if ($gallery_all && $gallery_all->num_rows > 0): ?>
                                    <?php while ($g = $gallery_all->fetch_assoc()): ?>
                                        <div class="bg-[#0a0f19] border border-slate-800 p-3 rounded-xl flex items-start justify-between gap-3 text-xs">
                                            <div class="flex items-start gap-3">
                                                <img src="<?php echo htmlspecialchars($g['image_path']); ?>" class="w-20 h-16 rounded-lg object-cover border border-slate-700 flex-shrink-0">
                                                <div>
                                                    <span class="text-[10px] font-mono-code bg-amber-950 text-amber-300 border border-amber-800 px-1.5 py-0.5 rounded font-bold"><?php echo $g['year']; ?></span>
                                                    <h4 class="font-bold text-white mt-1 leading-snug"><?php echo htmlspecialchars($g['title']); ?></h4>
                                                </div>
                                            </div>
                                            <a href="admin_panel.php?delete_gallery=<?php echo $g['gallery_id']; ?>&tab=gallery" onclick="return confirm('Remove photo?');" class="text-rose-400 hover:text-rose-300 text-xs p-1"><i class="fa-regular fa-trash-can"></i></a>
                                        </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== VIEW 4: LAB LOGO UPLOAD ==================== -->
                <div id="view-lab_logo" class="tab-view hidden">
                    <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl p-6 md:p-8 shadow-2xl space-y-6">
                        <h2 class="text-sm font-bold text-white uppercase font-mono-code">Header Logo Upload</h2>
                        <form method="POST" enctype="multipart/form-data" class="bg-[#0a0f19] p-6 rounded-2xl border border-slate-800 flex flex-col md:flex-row items-center gap-8">
                            <input type="hidden" name="upload_lab_logo" value="1">
                            <div class="w-28 h-28 p-2 rounded-2xl bg-white border-2 border-cyan-400 overflow-hidden flex items-center justify-center flex-shrink-0">
                                <img src="<?php echo $preview_logo; ?>?v=<?php echo time(); ?>" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="flex-1 space-y-2">
                                <input type="file" name="lab_logo_file" accept="image/*" required class="w-full text-xs text-slate-300">
                                <p class="text-[11px] text-slate-400 font-mono-code">Supports JPG, PNG, WEBP. Fits auto without crop.</p>
                            </div>
                            <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-6 py-2.5 rounded-xl uppercase font-mono-code">Update Logo</button>
                        </form>
                    </div>
                </div>

                <!-- ==================== VIEW 5: PI PROFILE ==================== -->
                <div id="view-pi_profile" class="tab-view hidden space-y-6">
                    <div class="bg-[#101724] border border-slate-800 rounded-2xl p-6 shadow-2xl">
                        <h2 class="text-sm font-bold text-white mb-4 uppercase font-mono-code border-b border-slate-800 pb-3">PI Profile Credentials</h2>
                        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-6 text-xs">
                            <input type="hidden" name="update_pi_profile" value="1">
                            <div class="lg:col-span-4 flex flex-col items-center justify-center p-4 bg-[#0a0f19] border border-slate-800 rounded-xl">
                                <div class="w-32 h-32 rounded-2xl overflow-hidden border-2 border-cyan-400 mb-3 bg-slate-900">
                                    <img src="<?php echo htmlspecialchars($pi['profile_image'] ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=600&auto=format&fit=crop'); ?>" class="w-full h-full object-cover">
                                </div>
                                <input type="file" name="pi_image" accept="image/*" class="w-full text-slate-400 text-xs">
                            </div>
                            <div class="lg:col-span-8 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <input type="text" name="pi_name" required value="<?php echo htmlspecialchars($pi['name'] ?? ''); ?>" placeholder="Full Name" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                                    <input type="text" name="pi_designation" required value="<?php echo htmlspecialchars($pi['designation'] ?? ''); ?>" placeholder="Designation" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                                </div>
                                <input type="email" name="pi_email" value="<?php echo htmlspecialchars($pi['email'] ?? ''); ?>" placeholder="Email" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                                <input type="text" name="pi_phone" value="<?php echo htmlspecialchars($pi['phone'] ?? ''); ?>" placeholder="Phone" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                                <input type="text" name="pi_scholar" value="<?php echo htmlspecialchars($pi['google_scholar_link'] ?? ''); ?>" placeholder="Google Scholar URL" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                                <textarea name="pi_bio" rows="2" placeholder="Bio" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white"><?php echo htmlspecialchars($pi['bio'] ?? ''); ?></textarea>
                                <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-5 py-2 rounded-lg font-mono-code uppercase">Save PI Profile</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ==================== VIEW 6: CO-ADMIN ==================== -->
                <div id="view-coadmin" class="tab-view hidden">
                    <div class="bg-[#101724] border border-cyan-500/30 rounded-2xl p-6 shadow-2xl">
                        <div class="flex justify-between items-center mb-4 border-b border-slate-800 pb-3">
                            <h2 class="text-sm font-bold text-white uppercase font-mono-code">Co-Administrator Access</h2>
                            <button type="button" onclick="openAddCoAdminModal()" class="bg-cyan-500 text-slate-950 font-bold px-3 py-1.5 rounded-lg text-xs font-mono-code uppercase">+ Create Co-Admin</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-800 rounded-xl bg-[#0a0f19]">
                            <table class="w-full text-left text-xs">
                                <tbody class="divide-y divide-slate-800 text-slate-300">
                                    <?php if ($coadmins_query && $coadmins_query->num_rows > 0): ?>
                                        <?php while($co = $coadmins_query->fetch_assoc()): ?>
                                            <tr>
                                                <td class="py-3 px-4 font-semibold text-white"><?php echo htmlspecialchars($co['name']); ?></td>
                                                <td class="py-3 px-4 text-cyan-400 font-mono-code"><?php echo htmlspecialchars($co['email']); ?></td>
                                                <td class="py-3 px-4 text-right">
                                                    <a href="admin_panel.php?delete_coadmin=<?php echo $co['user_id']; ?>&tab=coadmin" onclick="return confirm('Revoke access?');" class="bg-rose-950 text-rose-300 border border-rose-800 px-2 py-0.5 rounded text-xs">Revoke</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ==================== VIEW 7: RESEARCH THEMES ==================== -->
                <div id="view-research" class="tab-view hidden space-y-6">
                    <div class="bg-[#101724] border border-cyan-500/30 rounded-2xl p-6 shadow-2xl">
                        <div class="flex items-center justify-between mb-5 border-b border-slate-800 pb-4">
                            <h2 class="text-base font-bold text-white uppercase font-mono-code">Research Frontiers</h2>
                            <button type="button" onclick="openAddThemeModal()" class="bg-cyan-500 text-slate-950 font-bold px-4 py-1.5 rounded-lg text-xs font-mono-code uppercase">+ Add Research Entry</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if ($current_themes && $current_themes->num_rows > 0): ?>
                                <?php while($th = $current_themes->fetch_assoc()): 
                                    $theme_json = htmlspecialchars(json_encode($th), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <div class="bg-[#0a0f19] border border-slate-800 rounded-xl p-4 flex flex-col justify-between space-y-3">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[10px] font-mono-code uppercase px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800 font-bold"><?php echo htmlspecialchars($th['theme_code']); ?></span>
                                                <div class="space-x-1">
                                                    <a href="admin_panel.php?toggle_archive=<?php echo $th['theme_id']; ?>&to=Old&tab=research" class="bg-amber-950 text-amber-300 border border-amber-800 px-2 py-1 rounded text-[11px] font-mono-code">Make Old</a>
                                                    <button type="button" onclick='openEditThemeModal(<?php echo $theme_json; ?>)' class="bg-cyan-950 text-cyan-300 border border-cyan-700 px-2.5 py-1 rounded text-xs font-mono-code">Edit</button>
                                                    <a href="admin_panel.php?delete_theme=<?php echo $th['theme_id']; ?>&tab=research" onclick="return confirm('Delete theme?');" class="bg-rose-950 text-rose-300 border border-rose-800 px-2 py-1 rounded text-xs font-mono-code"><i class="fa-regular fa-trash-can"></i></a>
                                                </div>
                                            </div>
                                            <div class="flex gap-3 items-start pt-1">
                                                <img src="<?php echo htmlspecialchars($th['image_path']); ?>" class="w-16 h-14 rounded object-cover border border-slate-700 flex-shrink-0">
                                                <h3 class="text-xs font-bold text-white leading-snug"><?php echo htmlspecialchars($th['title']); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ==================== VIEW 8: PUBLICATIONS ==================== -->
                <div id="view-publications" class="tab-view hidden">
                    <div class="bg-[#101724] border border-cyan-500/30 rounded-2xl p-6 shadow-2xl">
                        <div class="flex justify-between items-center mb-4 border-b border-slate-800 pb-3">
                            <h2 class="text-sm font-bold text-white uppercase font-mono-code">Publications</h2>
                            <button type="button" onclick="openAddPubModal()" class="bg-cyan-500 text-slate-950 font-bold px-3 py-1.5 rounded-lg text-xs font-mono-code uppercase">+ Add Publication</button>
                        </div>
                        <div class="overflow-x-auto border border-slate-800 rounded-xl bg-[#0a0f19]">
                            <table class="w-full text-left text-xs">
                                <tbody class="divide-y divide-slate-800 text-slate-300">
                                    <?php if ($publications && $publications->num_rows > 0): ?>
                                        <?php while($p = $publications->fetch_assoc()): 
                                            $pub_json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <tr>
                                                <td class="py-3 px-4 font-mono-code text-cyan-400 font-bold"><?php echo htmlspecialchars($p['year']); ?></td>
                                                <td class="py-3 px-4 text-white font-semibold"><?php echo htmlspecialchars($p['title']); ?></td>
                                                <td class="py-3 px-4 text-right">
                                                    <button type="button" onclick='openEditPubModal(<?php echo $pub_json; ?>)' class="bg-cyan-950 text-cyan-300 border border-cyan-700 px-2 py-0.5 rounded text-xs">Edit</button>
                                                    <a href="admin_panel.php?delete_pub=<?php echo $p['pub_id']; ?>&tab=publications" onclick="return confirm('Delete?');" class="bg-rose-950 text-rose-300 border border-rose-800 px-2 py-0.5 rounded text-xs"><i class="fa-regular fa-trash-can"></i></a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ==================== VIEW 9: MEMBERS ==================== -->
                <div id="view-members" class="tab-view hidden">
                    <div class="bg-[#101724] border border-amber-500/30 rounded-2xl p-6 shadow-xl">
                        <h2 class="text-sm font-bold text-white mb-4 uppercase font-mono-code border-b border-slate-800 pb-3">Pending Member Registrations</h2>
                        <?php if ($pending_query && $pending_query->num_rows > 0): ?>
                            <table class="w-full text-left text-xs">
                                <tbody class="divide-y divide-slate-800 text-[11px]">
                                    <?php while($m = $pending_query->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-2.5 font-bold text-white"><?php echo htmlspecialchars($m['name']); ?></td>
                                            <td class="py-2.5 text-cyan-400 font-mono-code"><?php echo $m['role']; ?></td>
                                            <td class="py-2.5 text-slate-400"><?php echo htmlspecialchars($m['email']); ?></td>
                                            <td class="py-2.5 text-right space-x-2">
                                                <a href="admin_panel.php?approve_id=<?php echo $m['user_id']; ?>&tab=members" class="bg-emerald-500 text-slate-950 px-2.5 py-1 rounded font-bold">Approve</a>
                                                <a href="admin_panel.php?reject_id=<?php echo $m['user_id']; ?>&tab=members" class="bg-rose-950 text-rose-300 px-2.5 py-1 rounded">Reject</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-slate-500 text-xs font-mono-code py-4">No pending requests.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- MODAL: EDIT SLIDE (POPUP WITH FORM PRE-FILLED) -->
    <div id="editSlideModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl max-w-xl w-full p-6 text-slate-100 shadow-2xl relative">
            <button type="button" onclick="closeEditSlideModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">&times;</button>
            <h3 class="text-base font-bold font-mono-code uppercase text-white mb-4 border-b border-slate-800 pb-3">Edit Homepage Carousel Poster</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
                <input type="hidden" name="update_slide" value="1">
                <input type="hidden" id="edit_slide_id" name="slide_id" value="">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Badge</label>
                        <input type="text" id="edit_slide_badge" name="badge_text" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-slate-400 mb-1">Title *</label>
                        <input type="text" id="edit_slide_title" name="title" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-400 mb-1">Subtitle</label>
                        <input type="text" id="edit_slide_subtitle" name="subtitle" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                    </div>
                    <div>
                        <label class="block text-slate-400 mb-1">Explore URL Link</label>
                        <input type="text" id="edit_slide_link" name="link_url" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-400 mb-1">Replace Poster Image (Optional)</label>
                    <input type="file" name="slide_image" accept="image/*" class="w-full text-slate-400">
                </div>

                <div class="pt-2 flex justify-end gap-3 font-mono-code">
                    <button type="button" onclick="closeEditSlideModal()" class="bg-slate-800 text-slate-300 px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-5 py-2 rounded-lg uppercase">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD THEME -->
    <div id="addThemeModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl max-w-xl w-full p-6 text-slate-100 shadow-2xl relative">
            <button type="button" onclick="closeAddThemeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">&times;</button>
            <h3 class="text-base font-bold font-mono-code uppercase text-white mb-4 border-b border-slate-800 pb-3">Add Research Entry</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
                <input type="hidden" name="add_research_theme" value="1">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <select name="category" class="w-full bg-[#0a0f19] border border-cyan-500/60 rounded-lg p-2 text-cyan-300 font-mono-code">
                        <option value="Current">Current Research</option>
                        <option value="Old">Old Archive</option>
                    </select>
                    <input type="text" name="theme_code" required placeholder="Theme 01" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                    <select name="status" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <input type="text" name="title" required placeholder="Title" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" name="explore_link" value="#publications" placeholder="Paper Link" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                <textarea name="description" rows="2" required placeholder="Description" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white"></textarea>
                <input type="file" name="theme_image" accept="image/*" required class="w-full text-slate-400">
                <div class="pt-2 flex justify-end gap-3 font-mono-code">
                    <button type="button" onclick="closeAddThemeModal()" class="bg-slate-800 text-slate-300 px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-5 py-2 rounded-lg uppercase">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT THEME -->
    <div id="editThemeModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl max-w-xl w-full p-6 text-slate-100 shadow-2xl relative">
            <button type="button" onclick="closeEditThemeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">&times;</button>
            <h3 class="text-base font-bold font-mono-code uppercase text-white mb-4 border-b border-slate-800 pb-3">Edit Research Entry</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-3 text-xs">
                <input type="hidden" name="update_research_theme" value="1">
                <input type="hidden" id="edit_theme_id" name="theme_id" value="">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <select id="edit_category" name="category" class="w-full bg-[#0a0f19] border border-cyan-500/60 rounded-lg p-2 text-cyan-300 font-mono-code font-bold">
                        <option value="Current">Current Research</option>
                        <option value="Old">Old Archive</option>
                    </select>
                    <input type="text" id="edit_theme_code" name="theme_code" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                    <select id="edit_status" name="status" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <input type="text" id="edit_theme_title" name="title" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" id="edit_explore_link" name="explore_link" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                <textarea id="edit_theme_description" name="description" rows="2" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white"></textarea>
                <input type="file" name="theme_image" accept="image/*" class="w-full text-slate-400">
                <div class="pt-2 flex justify-end gap-3 font-mono-code">
                    <button type="button" onclick="closeEditThemeModal()" class="bg-slate-800 text-slate-300 px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-5 py-2 rounded-lg uppercase">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD PUB -->
    <div id="addPubModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl max-w-xl w-full p-6 text-slate-100 shadow-2xl relative">
            <button type="button" onclick="closeAddPubModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">&times;</button>
            <h3 class="text-base font-bold font-mono-code uppercase text-white mb-4 border-b border-slate-800 pb-3">Add Publication</h3>
            <form method="POST" class="space-y-3 text-xs">
                <input type="hidden" name="add_publication" value="1">
                <input type="number" name="year" value="<?php echo date('Y'); ?>" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" name="title" required placeholder="Title" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" name="authors" required placeholder="Authors" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" name="journal_name" required placeholder="Journal" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" name="doi" placeholder="DOI" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                <div class="pt-2 flex justify-end gap-2 font-mono-code">
                    <button type="button" onclick="closeAddPubModal()" class="bg-slate-800 text-slate-300 px-3 py-1.5 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-4 py-1.5 rounded-lg uppercase">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: EDIT PUB -->
    <div id="editPubModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl max-w-xl w-full p-6 text-slate-100 shadow-2xl relative">
            <button type="button" onclick="closeEditPubModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">&times;</button>
            <h3 class="text-base font-bold font-mono-code uppercase text-white mb-4 border-b border-slate-800 pb-3">Edit Publication</h3>
            <form method="POST" class="space-y-3 text-xs">
                <input type="hidden" name="update_publication" value="1">
                <input type="hidden" id="edit_pub_id" name="pub_id" value="">
                <input type="number" id="edit_pub_year" name="year" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" id="edit_pub_title" name="title" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" id="edit_pub_authors" name="authors" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" id="edit_pub_journal" name="journal_name" required class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="text" id="edit_pub_doi" name="doi" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white font-mono-code">
                <div class="pt-2 flex justify-end gap-2 font-mono-code">
                    <button type="button" onclick="closeEditPubModal()" class="bg-slate-800 text-slate-300 px-3 py-1.5 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-4 py-1.5 rounded-lg uppercase">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD CO-ADMIN -->
    <div id="addCoAdminModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-[#101724] border border-cyan-500/50 rounded-2xl max-w-md w-full p-6 text-slate-100 shadow-2xl relative">
            <button type="button" onclick="closeAddCoAdminModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">&times;</button>
            <h3 class="text-base font-bold font-mono-code uppercase text-white mb-4 border-b border-slate-800 pb-3">Authorize Co-Admin</h3>
            <form method="POST" class="space-y-3 text-xs">
                <input type="hidden" name="create_coadmin" value="1">
                <input type="text" name="co_name" required placeholder="Full Name" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="email" name="co_email" required placeholder="Email" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <input type="password" name="co_password" required placeholder="Password" class="w-full bg-[#0a0f19] border border-slate-700 rounded-lg p-2 text-white">
                <div class="pt-2 flex justify-end gap-2 font-mono-code">
                    <button type="button" onclick="closeAddCoAdminModal()" class="bg-slate-800 text-slate-300 px-3 py-1.5 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-cyan-500 text-slate-950 font-bold px-4 py-1.5 rounded-lg uppercase">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        function switchTab(tabKey) {
            document.querySelectorAll('.tab-view').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.sidebar-btn').forEach(el => el.classList.remove('active'));

            const targetView = document.getElementById('view-' + tabKey);
            const targetBtn = document.getElementById('btn-' + tabKey);

            if (targetView) targetView.classList.remove('hidden');
            if (targetBtn) targetBtn.classList.add('active');

            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const currentTab = "<?php echo $active_tab; ?>";
            switchTab(currentTab);
        });

        // Slide edit modal handlers
        function openEditSlideModal(data) {
            document.getElementById('edit_slide_id').value = data.slide_id;
            document.getElementById('edit_slide_badge').value = data.badge_text;
            document.getElementById('edit_slide_title').value = data.title;
            document.getElementById('edit_slide_subtitle').value = data.subtitle || '';
            document.getElementById('edit_slide_link').value = data.link_url || 'research.php';
            document.getElementById('editSlideModal').classList.remove('hidden');
        }
        function closeEditSlideModal() { document.getElementById('editSlideModal').classList.add('hidden'); }

        function openAddThemeModal() { document.getElementById('addThemeModal').classList.remove('hidden'); }
        function closeAddThemeModal() { document.getElementById('addThemeModal').classList.add('hidden'); }

        function openEditThemeModal(data) {
            document.getElementById('edit_theme_id').value = data.theme_id;
            document.getElementById('edit_theme_code').value = data.theme_code;
            document.getElementById('edit_theme_title').value = data.title;
            document.getElementById('edit_explore_link').value = data.explore_link || '#publications';
            document.getElementById('edit_theme_description').value = data.description;
            document.getElementById('edit_status').value = data.status || 'Active';
            document.getElementById('edit_category').value = data.category || 'Current';
            document.getElementById('editThemeModal').classList.remove('hidden');
        }
        function closeEditThemeModal() { document.getElementById('editThemeModal').classList.add('hidden'); }

        function openAddCoAdminModal() { document.getElementById('addCoAdminModal').classList.remove('hidden'); }
        function closeAddCoAdminModal() { document.getElementById('addCoAdminModal').classList.add('hidden'); }
        function openAddPubModal() { document.getElementById('addPubModal').classList.remove('hidden'); }
        function closeAddPubModal() { document.getElementById('addPubModal').classList.add('hidden'); }

        function openEditPubModal(data) {
            document.getElementById('edit_pub_id').value = data.pub_id;
            document.getElementById('edit_pub_year').value = data.year;
            document.getElementById('edit_pub_title').value = data.title;
            document.getElementById('edit_pub_authors').value = data.authors;
            document.getElementById('edit_pub_journal').value = data.journal_name;
            document.getElementById('edit_pub_doi').value = data.doi || '';
            document.getElementById('editPubModal').classList.remove('hidden');
        }
        function closeEditPubModal() { document.getElementById('editPubModal').classList.add('hidden'); }
    </script>
</body>
</html>