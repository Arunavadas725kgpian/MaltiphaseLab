<?php
require_once 'db.php';

// নির্দিষ্ট থিম আইডি আসলে তা ফেচ করা, নয়তো প্রথম সক্রিয় থিমটি ডিফল্ট দেখানো
$theme_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$all_themes_res = $conn->query("SELECT * FROM research_themes WHERE category = 'Current' OR category IS NULL ORDER BY display_order ASC, theme_id ASC");
$all_themes = [];
$active_theme = null;

if ($all_themes_res && $all_themes_res->num_rows > 0) {
    $idx = 1;
    while ($row = $all_themes_res->fetch_assoc()) {
        $row['seq_code'] = "THEME " . sprintf("%02d", $idx++);
        $all_themes[] = $row;
        if ($theme_id > 0 && $row['theme_id'] == $theme_id) {
            $active_theme = $row;
        }
    }
    if (!$active_theme && !empty($all_themes)) {
        $active_theme = $all_themes[0];
    }
}

// সম্পর্কিত পাবলিকেশন ফেচ করা
$pub_sql = "SELECT * FROM publications ORDER BY year DESC, pub_id DESC LIMIT 8";
$pub_res = $conn->query($pub_sql);

// লোগো চেক
$logo_file = 'lab_logo.png';
if (file_exists('lab_logo.jpg')) $logo_file = 'lab_logo.jpg';
elseif (file_exists('lab_logo.jpeg')) $logo_file = 'lab_logo.jpeg';
elseif (file_exists('logo.jpeg')) $logo_file = 'logo.jpeg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($active_theme['title'] ?? 'Research Overview'); ?> | Multiphase Flow Lab</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-serif-title { font-family: 'Playfair Display', serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <!-- TOP ACADEMIC HEADER -->
    <header class="bg-[#0b1017] text-white border-b border-slate-800 px-6 md:px-20 py-4 sticky top-0 z-40 shadow-xl">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="index.php" class="h-14 w-auto p-1.5 rounded-xl bg-white/95 border border-cyan-400 flex items-center justify-center flex-shrink-0">
                    <img src="<?php echo $logo_file; ?>" alt="Lab Logo" class="max-h-full max-w-full object-contain">
                </a>
                <div>
                    <a href="index.php" class="text-base font-bold tracking-tight text-white hover:text-cyan-300 transition">
                        Multiphase Flow Laboratory
                    </a>
                    <div class="text-[10px] font-mono-code text-cyan-400 tracking-widest uppercase">
                        Dept. of Chemical Engineering &bull; IIT Kharagpur
                    </div>
                </div>
            </div>

            <!-- NAVIGATION -->
            <nav class="hidden md:flex items-center gap-6 text-xs font-mono-code uppercase tracking-wider text-slate-300">
                <a href="index.php" class="hover:text-cyan-300 transition">Home</a>
                <a href="research.php" class="text-cyan-400 font-bold border-b-2 border-cyan-400 pb-1">Research</a>
                <a href="index.php#publications" class="hover:text-cyan-300 transition">Publications</a>
                <a href="index.php#people" class="hover:text-cyan-400 transition">People</a>
                <a href="login.php" class="bg-cyan-500 text-slate-950 font-bold px-3.5 py-1.5 rounded-full hover:bg-cyan-400 transition">Portal</a>
            </nav>
        </div>
    </header>

    <!-- RESEARCH HERO BANNER -->
    <div class="bg-[#0d1624] text-white py-12 px-6 md:px-20 border-b border-slate-800">
        <div class="max-w-7xl mx-auto">
            <div class="text-[11px] font-mono-code tracking-[0.3em] uppercase text-cyan-400 mb-2">Research Program</div>
            <h1 class="text-3xl md:text-5xl font-serif-title font-bold leading-tight">
                <?php echo htmlspecialchars($active_theme['title'] ?? 'Core Fluid Dynamics Investigations'); ?>
            </h1>
            <div class="flex items-center gap-3 mt-4 text-xs font-mono-code text-slate-300">
                <span class="bg-cyan-950 text-cyan-300 border border-cyan-800 px-2.5 py-0.5 rounded font-bold uppercase">
                    <?php echo htmlspecialchars($active_theme['seq_code'] ?? 'THEME'); ?>
                </span>
                <span>Status: <strong class="text-emerald-400"><?php echo htmlspecialchars($active_theme['status'] ?? 'Active'); ?></strong></span>
                <span>&bull;</span>
                <span class="text-slate-400">Department of Chemical Engineering</span>
            </div>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN RESEARCH LAYOUT -->
    <main class="max-w-7xl mx-auto px-6 md:px-20 py-12 w-full flex-1">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- LEFT NAVIGATION SIDEBAR (LIST OF ALL THEMES) -->
            <div class="lg:col-span-4 space-y-3">
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <h3 class="text-xs font-mono-code uppercase font-bold text-slate-400 tracking-wider mb-3 px-2">
                        Research Frontiers Directory
                    </h3>
                    <div class="space-y-1">
                        <?php foreach ($all_themes as $th): 
                            $is_current = ($active_theme && $active_theme['theme_id'] == $th['theme_id']);
                        ?>
                            <a href="research.php?id=<?php echo $th['theme_id']; ?>" 
                               class="flex items-start gap-3 p-3 rounded-lg text-xs transition <?php echo $is_current ? 'bg-cyan-50 border border-cyan-300 text-cyan-950 font-bold shadow-sm' : 'hover:bg-slate-50 text-slate-700 border border-transparent'; ?>">
                                <span class="font-mono-code text-[10px] px-1.5 py-0.5 rounded <?php echo $is_current ? 'bg-cyan-200 text-cyan-900' : 'bg-slate-100 text-slate-500'; ?>">
                                    <?php echo $th['seq_code']; ?>
                                </span>
                                <span class="leading-snug"><?php echo htmlspecialchars($th['title']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CONTACT ADVISOR CARD -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-2 text-xs">
                    <div class="text-[10px] font-mono-code text-slate-400 uppercase font-semibold">Principal Investigator</div>
                    <div class="font-bold text-sm text-slate-900">Prof. Gargi Das</div>
                    <p class="text-slate-500 leading-relaxed">Multiphase Flow & Transport Phenomena, Department of Chemical Engineering, IIT Kharagpur.</p>
                    <div class="pt-2 font-mono-code text-cyan-800">
                        <a href="mailto:gargi@che.iitkgp.ac.in" class="hover:underline">gargi@che.iitkgp.ac.in &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- RIGHT DETAILED CONTENT AREA -->
            <div class="lg:col-span-8 space-y-8">
                
                <?php if ($active_theme): ?>
                    <!-- FEATURED HIGH-RESOLUTION EXPERIMENTAL IMAGE -->
                    <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-md bg-white">
                        <div class="h-80 sm:h-96 w-full bg-slate-900 overflow-hidden relative">
                            <img src="<?php echo htmlspecialchars($active_theme['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($active_theme['title']); ?>" 
                                 class="w-full h-full object-cover">
                            <div class="absolute bottom-3 left-3 bg-black/80 backdrop-blur px-3 py-1 rounded text-white text-[11px] font-mono-code">
                                Figure: Experimental Diagnostics &amp; Flow Diagnostics
                            </div>
                        </div>

                        <div class="p-8 space-y-6">
                            <div>
                                <h2 class="text-2xl font-serif-title font-bold text-slate-900 mb-3">Overview &amp; Objectives</h2>
                                <p class="text-sm text-slate-600 leading-relaxed">
                                    <?php echo nl2br(htmlspecialchars($active_theme['description'])); ?>
                                </p>
                            </div>

                            <!-- KEY RESEARCH HIGHLIGHTS -->
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6">
                                <h3 class="text-xs font-mono-code uppercase tracking-wider text-slate-500 font-bold mb-3">Key Technical Deliverables</h3>
                                <ul class="space-y-2 text-xs text-slate-700">
                                    <?php if (!empty($active_theme['bullet_1'])): ?>
                                        <li class="flex items-start gap-2.5">
                                            <i class="fa-solid fa-circle-check text-cyan-600 mt-0.5"></i>
                                            <span><?php echo htmlspecialchars($active_theme['bullet_1']); ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($active_theme['bullet_2'])): ?>
                                        <li class="flex items-start gap-2.5">
                                            <i class="fa-solid fa-circle-check text-cyan-600 mt-0.5"></i>
                                            <span><?php echo htmlspecialchars($active_theme['bullet_2']); ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <!-- EXPERIMENTAL METHODOLOGY -->
                            <div class="space-y-3">
                                <h3 class="text-xl font-serif-title font-bold text-slate-900">Experimental Methods &amp; Instrumentation</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Investigations utilize Particle Image Velocimetry (PIV) with high-speed Nd:YAG laser illumination, high-speed shadowgraphy, and numerical simulations solved on COMSOL Multiphysics and OpenFOAM architectures.
                                </p>
                            </div>

                            <!-- CONNECTED REPOSITORY PAPERS -->
                            <div class="border-t border-slate-200 pt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-bold uppercase font-mono-code text-slate-900">Related Publications</h3>
                                    <a href="index.php#publications" class="text-xs font-mono-code text-cyan-700 hover:underline">Full Repository &rarr;</a>
                                </div>
                                <div class="space-y-2 text-xs">
                                    <?php if ($pub_res && $pub_res->num_rows > 0): ?>
                                        <?php while($p = $pub_res->fetch_assoc()): ?>
                                            <div class="p-3 rounded-lg border border-slate-200 bg-white hover:border-cyan-400 transition flex justify-between items-center gap-4">
                                                <div>
                                                    <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($p['title']); ?></div>
                                                    <div class="text-[11px] text-slate-500 italic"><?php echo htmlspecialchars($p['authors']); ?> &bull; <?php echo htmlspecialchars($p['journal_name']); ?> (<?php echo $p['year']; ?>)</div>
                                                </div>
                                                <?php if (!empty($p['doi'])): ?>
                                                    <a href="https://doi.org/<?php echo htmlspecialchars($p['doi']); ?>" target="_blank" class="text-[11px] font-mono-code text-cyan-700 hover:underline whitespace-nowrap">DOI &rarr;</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-[#0b1017] text-slate-400 py-8 px-6 md:px-20 border-t border-slate-800 text-xs font-mono-code">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>&copy; <?php echo date('Y'); ?> Multiphase Flow Laboratory &bull; IIT Kharagpur</div>
            <div><a href="index.php" class="text-cyan-400 hover:underline">&larr; Return to Homepage</a></div>
        </div>
    </footer>

</body>
</html>