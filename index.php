<?php
require_once 'db.php';

// 1. Publications fetch
$pub_sql = "SELECT * FROM publications ORDER BY year DESC, pub_id DESC";
$pub_result = $conn->query($pub_sql);

// 2. Members fetch & separate into strict academic hierarchy
$members_sql = "SELECT * FROM users WHERE is_approved = 1 AND role NOT IN ('PI', 'Admin', 'Co-Admin') ORDER BY user_id ASC";
$members_res = $conn->query($members_sql);

$postdocs = [];
$phd_scholars = [];
$mtech_students = [];
$visiting = [];
$undergrads = [];
$alumni = [];

if ($members_res && $members_res->num_rows > 0) {
    while($m = $members_res->fetch_assoc()) {
        $role_text = strtolower(($m['role'] ?? '') . ' ' . ($m['course_duration'] ?? ''));
        
        if (strpos($role_text, 'postdoc') !== false) {
            $postdocs[] = $m;
        } elseif (strpos($role_text, 'visit') !== false) {
            $visiting[] = $m;
        } elseif (strpos($role_text, 'undergrad') !== false || strpos($role_text, 'b.tech') !== false || strpos($role_text, 'btech') !== false) {
            $undergrads[] = $m;
        } elseif (strpos($role_text, 'alumni') !== false) {
            $alumni[] = $m;
        } elseif (strpos($role_text, 'phd') !== false || strpos($role_text, 'ph.d') !== false || strpos($role_text, 'doctoral') !== false) {
            $phd_scholars[] = $m;
        } elseif (strpos($role_text, 'mtech') !== false || strpos($role_text, 'm.tech') !== false || strpos($role_text, 'master') !== false) {
            $mtech_students[] = $m;
        } else {
            $phd_scholars[] = $m;
        }
    }
}

// 3. PI Profile fetch
$pi_res = $conn->query("SELECT * FROM users WHERE role IN ('PI', 'Admin') ORDER BY user_id ASC LIMIT 1");
$pi_homepage = ($pi_res && $pi_res->num_rows > 0) ? $pi_res->fetch_assoc() : null;

// 4. Slider posters fetch from Database
$slides_res = $conn->query("SELECT * FROM carousel_slides ORDER BY slide_id DESC");
$slides_data = [];
if ($slides_res && $slides_res->num_rows > 0) {
    while ($row = $slides_res->fetch_assoc()) {
        $slides_data[] = $row;
    }
}

// 5. Current Research Themes
$current_themes_res = $conn->query("SELECT * FROM research_themes WHERE category = 'Current' OR category IS NULL ORDER BY display_order ASC, theme_id ASC");
$current_themes = [];
if ($current_themes_res && $current_themes_res->num_rows > 0) {
    $seq = 1;
    while($row = $current_themes_res->fetch_assoc()) {
        $row['dynamic_theme_code'] = "THEME " . sprintf("%02d", $seq++);
        $current_themes[] = $row;
    }
}

// 6. Old Research Archives
$old_research_res = $conn->query("SELECT * FROM research_themes WHERE category = 'Old' ORDER BY theme_id DESC");

// 7. Gallery Years
$gallery_years_res = $conn->query("SELECT DISTINCT year FROM lab_gallery ORDER BY year DESC");
$gallery_years = [];
if ($gallery_years_res && $gallery_years_res->num_rows > 0) {
    while($yr_row = $gallery_years_res->fetch_assoc()) {
        $gallery_years[] = intval($yr_row['year']);
    }
}

// 8. Dynamic About Us Content Fetch
$about_res = $conn->query("SELECT * FROM lab_about WHERE id = 1 LIMIT 1");
$about_data = ($about_res && $about_res->num_rows > 0) ? $about_res->fetch_assoc() : [
    'title' => 'The Kinetic Continuum',
    'short_summary' => 'The Multiphase Flow Laboratory at IIT Kharagpur is dedicated to advancing the fundamental understanding and industrial applications of complex fluid systems through experimental precision and numerical depth.'
];

// 9. Logo check
$logo_file = 'lab_logo.png';
if (file_exists('lab_logo.jpg')) $logo_file = 'lab_logo.jpg';
elseif (file_exists('lab_logo.jpeg')) $logo_file = 'lab_logo.jpeg';
elseif (file_exists('logo.jpeg')) $logo_file = 'logo.jpeg';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiphase Flow Lab | IIT Kharagpur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,400;1,600&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .font-serif-title { font-family: 'Playfair Display', serif; }
        .font-italic-accent { font-family: 'Cormorant Garamond', serif; font-style: italic; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        .hero-bg {
            background: linear-gradient(to right, rgba(7, 14, 25, 0.94) 25%, rgba(7, 18, 32, 0.65) 65%, rgba(7, 14, 25, 0.9) 100%),
                        url('https://images.unsplash.com/photo-1518152006812-edab29b069ac?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center right;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 3px; }
        .carousel-item {
            min-width: 100%;
            transition: opacity 0.7s ease-in-out;
        }
    </style>
</head>
<body class="bg-[#0b1017] text-slate-100 font-sans antialiased selection:bg-cyan-500 selection:text-white pb-28">

    <!-- HERO SECTION -->
    <header class="relative hero-bg min-h-screen flex flex-col justify-between px-6 md:px-20 pt-8 pb-14 border-b border-slate-800/80">
        <div class="flex items-center justify-between text-xs tracking-[0.25em] text-slate-400 uppercase font-medium">
            
            <div class="flex items-center space-x-4 sm:space-x-6">
                <div class="h-20 w-auto min-w-[5rem] max-w-[14rem] sm:h-24 md:h-28 p-2 rounded-2xl bg-white/95 border-2 border-cyan-400/80 shadow-2xl shadow-cyan-950/80 flex items-center justify-center transition transform hover:scale-105">
                    <img src="<?php echo $logo_file; ?>?v=<?php echo time(); ?>" alt="Multiphase Flow Lab Logo" class="max-h-full max-w-full w-auto h-auto object-contain rounded-xl">
                </div>

                <div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                        <span class="tracking-[0.2em] font-mono-code text-slate-100 font-bold text-sm sm:text-base">IIT KHARAGPUR</span>
                    </div>
                    <div class="text-[11px] sm:text-xs font-mono-code text-cyan-400 tracking-wider font-semibold mt-1 uppercase">Department of Chemical Engineering</div>
                    <div class="text-[10px] font-mono-code text-slate-400 tracking-widest uppercase mt-0.5">Multiphase Flow Laboratory</div>
                </div>
            </div>

            <!-- PORTAL LOGIN -->
            <div class="relative inline-block text-left">
                <button type="button" onclick="toggleDropdown('topLoginMenu')" class="hover:text-cyan-300 transition text-[11px] border border-slate-700 hover:border-cyan-400 px-5 py-2.5 rounded-full bg-slate-900/80 backdrop-blur font-mono-code flex items-center gap-2 shadow-lg focus:outline-none">
                    <span>PORTAL LOGIN</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-cyan-400"></i>
                </button>

                <div id="topLoginMenu" class="hidden absolute right-0 mt-2.5 w-60 rounded-xl bg-[#111926] border border-slate-700/80 shadow-2xl z-50 overflow-hidden text-xs">
                    <div class="px-4 py-2.5 bg-slate-900/70 border-b border-slate-800 text-[10px] font-mono-code text-slate-400 uppercase tracking-widest">Choose Access Gateway</div>
                    <div class="p-1.5 space-y-1 font-sans">
                        <a href="login.php?tab=member" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-200 hover:bg-cyan-950/70 transition">
                            <div class="w-7 h-7 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center text-cyan-400">
                                <i class="fa-solid fa-user-graduate text-xs"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-xs text-white leading-tight">Lab Member Login</div>
                                <div class="text-[10px] text-slate-400 font-mono-code">Scholar, BTech, MTech</div>
                            </div>
                        </a>
                        <a href="login.php?tab=admin" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-200 hover:bg-amber-950/40 transition">
                            <div class="w-7 h-7 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center text-amber-400">
                                <i class="fa-solid fa-user-shield text-xs"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-xs text-white leading-tight">Admin / PI Login</div>
                                <div class="text-[10px] text-slate-400 font-mono-code">Prof. Gargi Das & Admin</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="my-auto max-w-4xl pt-16">
            <h1 class="text-6xl sm:text-7xl md:text-8xl lg:text-9xl font-normal leading-[0.92] tracking-tight text-white mb-6">
                <span class="font-serif-title block">Multiphase</span>
                <span class="font-italic-accent text-cyan-300 font-normal block pl-2 sm:pl-6 -mt-3">Flow Lab</span>
            </h1>
            <p class="text-slate-300 text-sm md:text-base max-w-xl font-light leading-relaxed mb-8 pl-2">
                Where the chaotic beauty of fluid dynamics meets the absolute precision of scientific inquiry. Four research frontiers. One laboratory.
            </p>
            <div class="flex flex-wrap gap-2.5 text-[11px] tracking-wider font-semibold text-slate-300 uppercase pl-2 font-mono-code select-none">
                <span class="bg-slate-800/80 border border-slate-700 px-4 py-1.5 rounded-sm">DISCOVER</span>
                <span class="bg-slate-800/80 border border-slate-700 px-4 py-1.5 rounded-sm">INNOVATE</span>
                <span class="bg-slate-800/80 border border-slate-700 px-4 py-1.5 rounded-sm">DEDICATION</span>
                <span class="bg-slate-800/80 border border-slate-700 px-4 py-1.5 rounded-sm">CURIOSITY</span>
            </div>
        </div>
        <div></div>
    </header>

    <!-- ==================== FULLY FUNCTIONAL AUTO-SLIDING CAROUSEL (7s INTERVAL) ==================== -->
    <section class="bg-[#080d15] py-8 px-4 md:px-20 border-b border-slate-800 relative select-none">
        <div class="max-w-7xl mx-auto">
            <div id="carouselContainer" class="relative overflow-hidden rounded-2xl border border-slate-800 shadow-2xl bg-black">
                
                <div id="carouselTrack" class="flex transition-transform duration-700 ease-in-out">
                    <?php if (!empty($slides_data)): ?>
                        <?php foreach ($slides_data as $idx => $sl): ?>
                            <div class="min-w-full relative h-72 sm:h-96 md:h-[480px] flex items-center overflow-hidden flex-shrink-0" data-index="<?php echo $idx; ?>">
                                <img src="<?php echo htmlspecialchars($sl['image_path']); ?>" 
                                     class="absolute inset-0 w-full h-full object-cover object-center pointer-events-none" 
                                     alt="<?php echo htmlspecialchars($sl['title']); ?>">
                                <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/45 to-transparent"></div>
                                
                                <div class="relative z-10 p-6 sm:p-10 md:p-16 max-w-3xl space-y-3">
                                    <span class="inline-block bg-cyan-500 text-slate-950 font-bold border border-cyan-400 text-[11px] font-mono-code uppercase px-3 py-1 rounded shadow-md tracking-wider">
                                        <?php echo htmlspecialchars($sl['badge_text'] ?? 'RESEARCH THEME'); ?>
                                    </span>
                                    <h3 class="text-3xl sm:text-4xl md:text-5xl font-bold font-serif-title text-white leading-tight drop-shadow-lg">
                                        <?php echo htmlspecialchars($sl['title']); ?>
                                    </h3>
                                    <?php if (!empty($sl['subtitle'])): ?>
                                        <p class="text-xs sm:text-sm text-slate-200 font-normal line-clamp-2 drop-shadow max-w-xl">
                                            <?php echo htmlspecialchars($sl['subtitle']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="pt-2">
                                        <a href="<?php echo htmlspecialchars($sl['link_url'] ?? 'research.php'); ?>" class="inline-block bg-cyan-400 hover:bg-cyan-300 text-slate-950 text-xs font-bold px-6 py-2.5 rounded-lg font-mono-code uppercase tracking-wider transition shadow-lg">
                                            <?php echo htmlspecialchars(!empty($sl['link_label']) ? $sl['link_label'] : 'Explore'); ?> &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback Slide -->
                        <div class="min-w-full relative h-72 sm:h-96 md:h-[480px] flex items-center overflow-hidden flex-shrink-0">
                            <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=1600&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/90 via-black/45 to-transparent"></div>
                            <div class="relative z-10 p-6 md:p-16 max-w-2xl space-y-3">
                                <span class="bg-cyan-500 text-slate-950 font-bold text-[11px] font-mono-code uppercase px-3 py-1 rounded">Lab Highlight</span>
                                <h3 class="text-3xl sm:text-4xl font-bold font-serif-title text-white">Multiphase Flow Laboratory Showcase</h3>
                                <div class="pt-2"><a href="research.php" class="bg-cyan-400 text-slate-950 text-xs font-bold px-6 py-2.5 rounded-lg font-mono-code uppercase">Explore &rarr;</a></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PREV / NEXT ARROWS -->
                <button type="button" id="prevSlideBtn" class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-cyan-500 text-white hover:text-slate-950 w-11 h-11 rounded-full border border-slate-700 flex items-center justify-center transition shadow-2xl z-20 backdrop-blur-sm cursor-pointer">
                    <i class="fa-solid fa-chevron-left text-sm"></i>
                </button>
                <button type="button" id="nextSlideBtn" class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-cyan-500 text-white hover:text-slate-950 w-11 h-11 rounded-full border border-slate-700 flex items-center justify-center transition shadow-2xl z-20 backdrop-blur-sm cursor-pointer">
                    <i class="fa-solid fa-chevron-right text-sm"></i>
                </button>

                <!-- DYNAMIC DOT INDICATORS -->
                <div id="carouselDots" class="absolute bottom-5 left-1/2 -translate-x-1/2 flex space-x-2.5 z-20 bg-black/60 px-4 py-2 rounded-full backdrop-blur-sm"></div>
            </div>
        </div>
    </section>

    <!-- RESEARCH SECTION -->
    <section id="research" class="bg-white text-slate-900 py-20 px-6 md:px-20">
        <div class="max-w-7xl mx-auto space-y-16">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <div class="text-[11px] tracking-[0.3em] uppercase text-slate-400 font-semibold mb-2 font-mono-code">Active Frontiers</div>
                    <h2 class="text-4xl md:text-5xl font-serif-title font-medium text-slate-900">Current Research Themes</h2>
                </div>
                <a href="research.php" class="bg-[#0b1017] hover:bg-cyan-600 text-white font-mono-code text-xs px-5 py-2.5 rounded-xl uppercase tracking-wider transition flex items-center gap-2 shadow-lg self-start md:self-auto">
                    <span>Open Full Research Directory</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if (!empty($current_themes)): ?>
                    <?php foreach($current_themes as $th): ?>
                        <div id="theme-card-<?php echo $th['theme_id']; ?>" class="border border-slate-200 rounded-xl p-5 flex flex-col justify-between hover:shadow-xl transition group bg-white scroll-mt-24">
                            <div>
                                <div class="relative overflow-hidden rounded-lg h-44 mb-4 bg-slate-100">
                                    <span class="absolute top-2.5 left-2.5 bg-black/80 text-cyan-300 text-[10px] font-mono-code tracking-widest uppercase px-2 py-0.5 rounded z-10">
                                        <?php echo htmlspecialchars($th['dynamic_theme_code']); ?>
                                    </span>
                                    <img src="<?php echo htmlspecialchars($th['image_path']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="<?php echo htmlspecialchars($th['title']); ?>">
                                </div>
                                <h3 class="font-bold text-base text-slate-900 leading-snug mb-2">
                                    <?php echo htmlspecialchars($th['title']); ?>
                                </h3>
                                <p class="text-xs text-slate-500 leading-relaxed mb-4">
                                    <?php echo htmlspecialchars($th['description']); ?>
                                </p>
                                <ul class="text-[11px] text-slate-600 space-y-1 mb-6">
                                    <?php if (!empty($th['bullet_1'])): ?>
                                        <li><span class="text-cyan-600 mr-1">&bull;</span> <?php echo htmlspecialchars($th['bullet_1']); ?></li>
                                    <?php endif; ?>
                                    <?php if (!empty($th['bullet_2'])): ?>
                                        <li><span class="text-cyan-600 mr-1">&bull;</span> <?php echo htmlspecialchars($th['bullet_2']); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <a href="research.php?id=<?php echo $th['theme_id']; ?>" class="text-[11px] font-semibold tracking-wider text-cyan-700 hover:text-cyan-600 uppercase flex items-center gap-1 font-mono-code transition">
                                <span>Explore Research Page</span> &rarr;
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- COMPLETED ARCHIVES -->
            <?php if ($old_research_res && $old_research_res->num_rows > 0): ?>
                <div class="pt-10 border-t border-slate-200">
                    <div class="mb-8">
                        <div class="text-[11px] tracking-[0.3em] uppercase text-slate-400 font-semibold mb-1 font-mono-code">Historical Repository</div>
                        <h3 class="text-2xl md:text-3xl font-serif-title font-medium text-slate-800">Past Research & Completed Projects</h3>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-mono-code uppercase text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4">Identifier</th>
                                    <th class="py-3 px-4">Topic / Project Title</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Papers</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <?php while($old = $old_research_res->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3.5 px-4 font-mono-code font-bold text-slate-900">ARCHIVE</td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-900"><?php echo htmlspecialchars($old['title']); ?></td>
                                        <td class="py-3.5 px-4">
                                            <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-mono-code px-2 py-0.5 rounded">
                                                <?php echo htmlspecialchars($old['status'] ?? 'Completed'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <a href="research.php" class="text-cyan-700 hover:underline font-mono-code text-[11px]">View Details &rarr;</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- PUBLICATIONS -->
    <section id="publications" class="bg-[#0c141f] text-slate-100 py-20 px-6 md:px-20 border-t border-slate-800 scroll-mt-10">
        <div class="max-w-7xl mx-auto">
            <div class="mb-10">
                <div class="text-[11px] tracking-[0.3em] uppercase text-cyan-400 font-mono-code mb-2">Data Architecture</div>
                <h2 class="text-4xl md:text-5xl font-serif-title font-medium">Publication Repository</h2>
            </div>
            <div class="overflow-x-auto border border-slate-800 rounded-lg">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-[#090d14] text-slate-400 font-mono-code uppercase tracking-wider text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Year</th>
                            <th class="py-3.5 px-4">Title & Authors</th>
                            <th class="py-3.5 px-4">Journal</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">DOI / Link</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-slate-300">
                        <?php if ($pub_result && $pub_result->num_rows > 0): ?>
                            <?php while($pub = $pub_result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-4 px-4 font-mono-code text-cyan-400 font-semibold"><?php echo htmlspecialchars($pub['year']); ?></td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium text-white text-sm"><?php echo htmlspecialchars($pub['title']); ?></div>
                                        <div class="text-[11px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($pub['authors']); ?></div>
                                    </td>
                                    <td class="py-4 px-4 text-slate-300 italic"><?php echo htmlspecialchars($pub['journal_name']); ?></td>
                                    <td class="py-4 px-4">
                                        <span class="bg-cyan-950/70 text-cyan-300 border border-cyan-800/80 px-2 py-0.5 rounded text-[10px] font-mono-code"><?php echo htmlspecialchars($pub['pub_type'] ?? 'Journal'); ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if (!empty($pub['doi'])): ?>
                                            <a href="https://doi.org/<?php echo htmlspecialchars($pub['doi']); ?>" target="_blank" class="text-cyan-400 hover:text-cyan-300 transition text-xs font-mono-code">DOI &rarr;</a>
                                        <?php else: ?>
                                            <span class="text-slate-600 font-mono-code">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- PEOPLE SECTION -->
    <section id="people" class="bg-[#fcfdfd] text-slate-900 py-20 px-6 md:px-20 border-t border-slate-200 scroll-mt-10">
        <div class="max-w-6xl mx-auto space-y-16">
            <div>
                <h2 class="text-4xl font-serif-title font-bold text-slate-950 tracking-tight mb-2">People</h2>
                <div class="h-1 w-16 bg-[#8a1538] rounded-full mb-6"></div>
            </div>

            <!-- 1. FACULTY -->
            <div id="faculty" class="scroll-mt-28 space-y-4">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">Principal Investigator</h3>
                <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm max-w-xl">
                    <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($pi_homepage['profile_image'] ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=600&auto=format&fit=crop'); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="space-y-1 text-xs">
                        <button type="button" onclick="openPeopleModal('faculty_lead')" class="font-bold text-sm text-slate-950 hover:text-[#8a1538] hover:underline text-left transition font-sans">
                            <?php echo htmlspecialchars($pi_homepage['name'] ?? 'Prof. Gargi Das'); ?>
                        </button>
                        <div class="text-slate-600 font-mono-code pt-1">Dept. of Chemical Eng., IIT Kharagpur</div>
                        <div class="text-[#8a1538] font-mono-code"><?php echo htmlspecialchars($pi_homepage['email'] ?? 'gargi@che.iitkgp.ac.in'); ?></div>
                    </div>
                </div>
            </div>

            <!-- 2. ADMINISTRATIVE ASSISTANT -->
            <div id="admin_staff" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">Administrative Assistant & Technical Staff</h3>
                <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm max-w-xl">
                    <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden flex items-center justify-center text-slate-400 text-3xl font-bold">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div class="space-y-1 text-xs">
                        <h4 class="font-bold text-sm text-slate-950 font-sans">Lab Office Operations</h4>
                        <div class="text-slate-600 font-mono-code pt-1">Multiphase Lab, CHE-201</div>
                        <div class="text-[#8a1538] font-mono-code">multiphase-office@che.iitkgp.ac.in</div>
                        <div class="text-slate-600 font-mono-code">+91 (3222) 282240</div>
                    </div>
                </div>
            </div>

            <!-- 3. POSTDOCTORAL ASSOCIATES -->
            <div id="postdoc" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">Postdoctoral Associates</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($postdocs)): ?>
                        <?php foreach($postdocs as $pd): 
                            $json = htmlspecialchars(json_encode($pd), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm">
                                <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden flex items-center justify-center">
                                    <?php if (!empty($pd['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($pd['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-3xl font-bold text-slate-400"><?php echo strtoupper(substr($pd['name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <button type="button" onclick='openPeopleModal(<?php echo $json; ?>)' class="font-bold text-sm text-slate-950 hover:text-[#8a1538] hover:underline text-left">
                                        <?php echo htmlspecialchars($pd['name']); ?>
                                    </button>
                                    <div class="text-slate-600 font-mono-code pt-1">Room 3-249</div>
                                    <div class="text-[#8a1538] font-mono-code"><?php echo htmlspecialchars($pd['email']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="border border-dashed border-slate-300 p-4 text-xs text-slate-400 font-mono-code col-span-2">
                            Currently no active postdoctoral associates listed.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4.1. PH.D. SCHOLARS -->
            <div id="phd" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">Ph.D. Scholars (Doctoral Candidates)</h3>
                    <span class="text-xs font-mono-code text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200">Doctoral Research</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($phd_scholars)): ?>
                        <?php foreach($phd_scholars as $phd): 
                            $json = htmlspecialchars(json_encode($phd), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm">
                                <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden flex items-center justify-center">
                                    <?php if (!empty($phd['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($phd['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-3xl font-bold text-slate-400"><?php echo strtoupper(substr($phd['name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <button type="button" onclick='openPeopleModal(<?php echo $json; ?>)' class="font-bold text-sm text-slate-950 hover:text-[#8a1538] hover:underline text-left">
                                        <?php echo htmlspecialchars($phd['name']); ?>
                                    </button>
                                    <div class="text-slate-500 font-mono-code text-[11px]"><?php echo htmlspecialchars($phd['course_duration'] ?? 'Ph.D. Scholar'); ?></div>
                                    <div class="text-slate-600 font-mono-code pt-1">Room 3-249</div>
                                    <div class="text-[#8a1538] font-mono-code"><?php echo htmlspecialchars($phd['email']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="border border-dashed border-slate-300 p-4 text-xs text-slate-400 font-mono-code col-span-2">
                            No Ph.D. scholars currently registered.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4.2. M.TECH STUDENTS -->
            <div id="mtech" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">Master's Students (M.Tech Scholars)</h3>
                    <span class="text-xs font-mono-code text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200">Master's Program</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($mtech_students)): ?>
                        <?php foreach($mtech_students as $mt): 
                            $json = htmlspecialchars(json_encode($mt), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm">
                                <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden flex items-center justify-center">
                                    <?php if (!empty($mt['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($mt['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-3xl font-bold text-slate-400"><?php echo strtoupper(substr($mt['name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <button type="button" onclick='openPeopleModal(<?php echo $json; ?>)' class="font-bold text-sm text-slate-950 hover:text-[#8a1538] hover:underline text-left">
                                        <?php echo htmlspecialchars($mt['name']); ?>
                                    </button>
                                    <div class="text-slate-500 font-mono-code text-[11px]"><?php echo htmlspecialchars($mt['course_duration'] ?? 'M.Tech Scholar'); ?></div>
                                    <div class="text-slate-600 font-mono-code pt-1">Room 3-249</div>
                                    <div class="text-[#8a1538] font-mono-code"><?php echo htmlspecialchars($mt['email']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="border border-dashed border-slate-300 p-4 text-xs text-slate-400 font-mono-code col-span-2">
                            No M.Tech students currently registered.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5. VISITING RESEARCHERS -->
            <div id="visiting" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">Visiting Researchers</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($visiting)): ?>
                        <?php foreach($visiting as $vi): 
                            $json = htmlspecialchars(json_encode($vi), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm">
                                <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden flex items-center justify-center">
                                    <?php if (!empty($vi['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($vi['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-3xl font-bold text-slate-400"><?php echo strtoupper(substr($vi['name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <button type="button" onclick='openPeopleModal(<?php echo $json; ?>)' class="font-bold text-sm text-slate-950 hover:text-[#8a1538] hover:underline text-left">
                                        <?php echo htmlspecialchars($vi['name']); ?>
                                    </button>
                                    <div class="text-slate-600 font-mono-code pt-1">Room 3-249</div>
                                    <div class="text-[#8a1538] font-mono-code"><?php echo htmlspecialchars($vi['email']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="border border-dashed border-slate-300 p-4 text-xs text-slate-400 font-mono-code col-span-2">
                            No visiting researchers at this time.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 6. UNDERGRADUATE STUDENTS -->
            <div id="undergrad" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">Undergraduate Students (B.Tech)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (!empty($undergrads)): ?>
                        <?php foreach($undergrads as $ug): 
                            $json = htmlspecialchars(json_encode($ug), ENT_QUOTES, 'UTF-8');
                        ?>
                            <div class="border border-slate-300/80 bg-white p-5 flex items-start gap-5 shadow-sm">
                                <div class="w-28 h-36 bg-slate-100 flex-shrink-0 border border-slate-200 overflow-hidden flex items-center justify-center text-slate-400 text-3xl font-bold">
                                    <?php if (!empty($ug['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($ug['profile_image']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span><?php echo strtoupper(substr($ug['name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1 text-xs">
                                    <button type="button" onclick='openPeopleModal(<?php echo $json; ?>)' class="font-bold text-sm text-slate-950 hover:text-[#8a1538] hover:underline text-left">
                                        <?php echo htmlspecialchars($ug['name']); ?>
                                    </button>
                                    <div class="text-slate-600 font-mono-code pt-1">CAPE Lab, CHE</div>
                                    <div class="text-[#8a1538] font-mono-code"><?php echo htmlspecialchars($ug['email']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="border border-dashed border-slate-300 p-4 text-xs text-slate-400 font-mono-code col-span-2">
                            Undergraduate student researchers are listed per academic term.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 7. ALUMNI -->
            <div id="alumni" class="scroll-mt-28 space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">Alumni</h3>
                <div class="border border-slate-200 bg-white rounded-lg p-5 text-xs text-slate-600">
                    <p>Alumni members maintain positions across academic institutions, national research centers, and process engineering industries worldwide.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. ABOUT SECTION (CONCISE & DEDICATED BUTTON) -->
    <section id="about" class="bg-[#090e16] text-slate-300 py-20 px-6 md:px-20 border-t border-slate-800 scroll-mt-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <div>
                <span class="text-[10px] font-mono-code uppercase tracking-widest text-cyan-400">Institutional Charter</span>
                <h3 class="text-3xl md:text-4xl font-serif-title font-medium text-white mt-1">
                    <?php echo htmlspecialchars($about_data['title'] ?? 'The Kinetic Continuum'); ?>
                </h3>
            </div>
            
            <p class="text-xs md:text-sm text-slate-400 leading-relaxed max-w-3xl">
                <?php echo htmlspecialchars($about_data['short_summary']); ?>
            </p>

            <div class="pt-2">
                <a href="about.php" class="inline-flex items-center gap-2 text-xs font-mono-code uppercase tracking-wider text-cyan-400 hover:text-cyan-300 border border-cyan-500/40 hover:border-cyan-400 bg-cyan-950/40 px-5 py-2.5 rounded-xl transition shadow-lg">
                    <span>Read Full Overview & Mission</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-[#06090e] text-slate-400 py-12 px-6 md:px-20 border-t border-slate-800 text-xs">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 font-mono-code">
            <div class="text-slate-500">
                &copy; <?php echo date('Y'); ?> Multiphase Flow Laboratory &bull; IIT Kharagpur. All rights reserved.
            </div>

            <div class="flex items-center gap-2 bg-[#0c131f] border border-cyan-500/30 px-3.5 py-1.5 rounded-full shadow-lg">
                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                <span class="text-slate-400">Portal Architecture & Developed by:</span>
                <span class="text-cyan-300 font-bold tracking-wide">Arunava Das</span>
            </div>
        </div>
    </footer>

    <!-- FLOATING DOCK -->
    <div class="fixed bottom-6 inset-x-0 flex justify-center z-50 pointer-events-none">
        <div class="relative">
            
            <!-- PEOPLE DROPDOWN -->
            <div id="peopleMenu" class="hidden pointer-events-auto absolute bottom-14 left-24 w-64 rounded-xl bg-[#111926] border border-slate-700/90 shadow-2xl p-2 mb-2 z-50 space-y-1 text-xs">
                <div class="px-3 py-1.5 bg-slate-900 text-[10px] font-mono-code text-slate-400 uppercase tracking-widest border-b border-slate-800">Academic Directory</div>
                <a href="#faculty" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-chalkboard-user text-cyan-400 text-xs w-4"></i>
                    <span>Faculty (PI Lead)</span>
                </a>
                <a href="#admin_staff" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-id-badge text-cyan-400 text-xs w-4"></i>
                    <span>Administrative Assistant</span>
                </a>
                <a href="#postdoc" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-microscope text-cyan-400 text-xs w-4"></i>
                    <span>Postdoctoral Associates</span>
                </a>
                <a href="#phd" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-user-graduate text-cyan-400 text-xs w-4"></i>
                    <span>Ph.D. Scholars</span>
                </a>
                <a href="#mtech" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-graduation-cap text-cyan-400 text-xs w-4"></i>
                    <span>M.Tech Students</span>
                </a>
                <a href="#visiting" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-users text-cyan-400 text-xs w-4"></i>
                    <span>Visiting Researchers</span>
                </a>
                <a href="#undergrad" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-book-open text-cyan-400 text-xs w-4"></i>
                    <span>Undergraduate Students</span>
                </a>
                <a href="#alumni" onclick="toggleDropdown('peopleMenu')" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-200 hover:bg-cyan-950/70 hover:text-cyan-300 transition">
                    <i class="fa-solid fa-award text-cyan-400 text-xs w-4"></i>
                    <span>Alumni</span>
                </a>
            </div>

            <!-- LOGIN DROPDOWN -->
            <div id="bottomLoginMenu" class="hidden pointer-events-auto absolute bottom-14 right-0 w-60 rounded-xl bg-[#111926] border border-slate-700/80 shadow-2xl p-1.5 mb-2 z-50 space-y-1 text-xs">
                <div class="px-3 py-1.5 bg-slate-900/80 border-b border-slate-800 text-[10px] font-mono-code text-slate-400 uppercase tracking-widest">Select Access</div>
                <a href="login.php?tab=member" class="block px-3 py-2 text-slate-200 hover:bg-cyan-950/70 rounded">Lab Member Login</a>
                <a href="login.php?tab=admin" class="block px-3 py-2 text-slate-200 hover:bg-amber-950/40 rounded">Admin / PI Login</a>
            </div>

            <!-- MAIN DOCK -->
            <nav class="pointer-events-auto bg-[#111926]/95 backdrop-blur-md border border-slate-700/80 shadow-2xl rounded-full px-6 py-2.5 flex items-center space-x-5 text-[10px] tracking-widest uppercase font-mono-code text-slate-300">
                <a href="research.php" class="text-cyan-400 hover:text-white transition">RESEARCH</a>
                <a href="#publications" class="hover:text-cyan-400 transition">PUBLICATIONS</a>
                
                <button type="button" onclick="toggleDropdown('peopleMenu')" class="hover:text-cyan-400 transition flex items-center gap-1 focus:outline-none text-cyan-300 font-semibold uppercase">
                    <span>PEOPLE</span>
                    <i class="fa-solid fa-chevron-up text-[8px]"></i>
                </button>

                <a href="gallery.php" class="text-amber-400 hover:text-amber-300 transition font-semibold flex items-center gap-1">
                    <i class="fa-regular fa-images text-[11px]"></i>
                    <span>GALLERY</span>
                </a>

                <a href="about.php" class="hover:text-cyan-400 transition">ABOUT</a>
                <button type="button" onclick="toggleDropdown('bottomLoginMenu')" class="hover:text-cyan-400 transition pl-2 border-l border-slate-700 uppercase">LOGIN</button>
            </nav>
        </div>
    </div>

    <!-- PEOPLE MODAL -->
    <div id="peopleDetailModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white text-slate-900 rounded-xl max-w-2xl w-full p-8 shadow-2xl relative border border-slate-300 max-h-[90vh] overflow-y-auto">
            <button onclick="closePeopleModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-800 text-xl font-bold w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">&times;</button>
            <div class="flex flex-col sm:flex-row items-start gap-6 border-b border-slate-200 pb-6 mb-6">
                <div id="modalPhotoBox" class="w-32 h-40 bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center text-4xl text-slate-400"></div>
                <div class="space-y-1.5 flex-1 text-xs">
                    <h3 id="modalProfileName" class="text-2xl font-bold text-slate-950 font-sans"></h3>
                    <div id="modalProfileRole" class="text-slate-600 font-medium text-xs font-sans"></div>
                    <div class="text-slate-600 font-mono-code flex items-center gap-2 pt-2">
                        <i class="fa-solid fa-location-dot text-slate-400"></i>
                        <span>Room 3-249</span>
                    </div>
                    <div class="text-slate-600 font-mono-code flex items-center gap-2">
                        <i class="fa-regular fa-envelope text-slate-400"></i>
                        <span id="modalEmailText" class="text-[#8a1538]"></span>
                    </div>
                </div>
            </div>
            <div class="space-y-4 text-xs text-slate-700">
                <div>
                    <h4 class="text-sm font-bold text-slate-950 font-sans mb-1">Education</h4>
                    <ul id="modalEducation" class="list-disc pl-5 leading-relaxed"></ul>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-950 font-sans mb-1">About Me</h4>
                    <p id="modalAboutMe" class="leading-relaxed text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-200"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT: PRECISE SLIDER ENGINE (7 SECONDS TIMER & CONTROLS) -->
    <script>
        function toggleDropdown(elementId) {
            const menu = document.getElementById(elementId);
            if (!menu) return;
            menu.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            ['topLoginMenu', 'bottomLoginMenu', 'peopleMenu'].forEach(id => {
                const el = document.getElementById(id);
                if (el && !e.target.closest('#' + id) && !e.target.closest(`button[onclick*="${id}"]`)) {
                    el.classList.add('hidden');
                }
            });
        });

        // ==================== CAROUSEL SLIDER ENGINE ====================
        let currentSlideIdx = 0;
        const track = document.getElementById('carouselTrack');
        const container = document.getElementById('carouselContainer');
        const dotsContainer = document.getElementById('carouselDots');
        const prevBtn = document.getElementById('prevSlideBtn');
        const nextBtn = document.getElementById('nextSlideBtn');
        
        const slides = track ? track.children : [];
        const totalSlides = slides.length;
        let slideInterval = null;

        function updateSlidePosition() {
            if (!track || totalSlides === 0) return;
            track.style.transform = `translateX(-${currentSlideIdx * 100}%)`;
            
            // Update dots
            if (dotsContainer) {
                const dots = dotsContainer.children;
                for (let i = 0; i < dots.length; i++) {
                    if (i === currentSlideIdx) {
                        dots[i].className = "w-6 h-2 rounded-full bg-cyan-400 transition-all duration-300";
                    } else {
                        dots[i].className = "w-2 h-2 rounded-full bg-slate-600 hover:bg-slate-400 transition-all duration-300 cursor-pointer";
                    }
                }
            }
        }

        function nextSlide() {
            if (totalSlides <= 1) return;
            currentSlideIdx = (currentSlideIdx + 1) % totalSlides;
            updateSlidePosition();
        }

        function prevSlide() {
            if (totalSlides <= 1) return;
            currentSlideIdx = (currentSlideIdx - 1 + totalSlides) % totalSlides;
            updateSlidePosition();
        }

        function startAutoSlider() {
            if (totalSlides > 1 && !slideInterval) {
                slideInterval = setInterval(nextSlide, 7000); // 7 seconds interval
            }
        }

        function stopAutoSlider() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
            }
        }

        // Initialize Carousel
        if (totalSlides > 0) {
            // Build Dots
            if (dotsContainer) {
                dotsContainer.innerHTML = '';
                for (let i = 0; i < totalSlides; i++) {
                    const dot = document.createElement('button');
                    dot.type = "button";
                    dot.className = (i === 0) ? "w-6 h-2 rounded-full bg-cyan-400 transition-all duration-300" : "w-2 h-2 rounded-full bg-slate-600 hover:bg-slate-400 transition-all duration-300 cursor-pointer";
                    dot.onclick = () => {
                        currentSlideIdx = i;
                        updateSlidePosition();
                        stopAutoSlider();
                        startAutoSlider();
                    };
                    dotsContainer.appendChild(dot);
                }
            }

            if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); stopAutoSlider(); startAutoSlider(); });
            if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); stopAutoSlider(); startAutoSlider(); });

            if (container) {
                container.addEventListener('mouseenter', stopAutoSlider);
                container.addEventListener('mouseleave', startAutoSlider);
            }

            startAutoSlider();
        }

        // People Modal Handler
        function openPeopleModal(data) {
            document.getElementById('modalProfileName').textContent = data.name;
            document.getElementById('modalProfileRole').textContent = data.role + " (" + (data.course_duration || 'Graduate Researcher') + ")";
            document.getElementById('modalEmailText').textContent = data.email;
            
            const photoBox = document.getElementById('modalPhotoBox');
            const photoSrc = data.profile_image || data.image;
            if (photoSrc && photoSrc.trim() !== '') {
                photoBox.innerHTML = `<img src="${photoSrc}" alt="${data.name}" class="w-full h-full object-cover">`;
            } else {
                photoBox.innerHTML = `<span class="font-bold">${data.name.charAt(0).toUpperCase()}</span>`;
            }

            document.getElementById('modalEducation').innerHTML = `<li><strong>Degree:</strong> ${data.course_duration || 'Graduate Scholar'} in Chemical Engineering, IIT Kharagpur.</li>`;
            document.getElementById('modalAboutMe').textContent = data.bio || "Graduate scholar at Multiphase Flow Laboratory, IIT Kharagpur.";
            document.getElementById('peopleDetailModal').classList.remove('hidden');
        }

        function closePeopleModal() {
            document.getElementById('peopleDetailModal').classList.add('hidden');
        }
    </script>
</body>
</html>