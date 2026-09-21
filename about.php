<?php
require_once 'db.php';

$about_res = $conn->query("SELECT * FROM lab_about WHERE id = 1 LIMIT 1");
$about = ($about_res && $about_res->num_rows > 0) ? $about_res->fetch_assoc() : [
    'title' => 'The Kinetic Continuum',
    'short_summary' => 'The Multiphase Flow Laboratory at IIT Kharagpur is dedicated to advancing complex fluid systems.',
    'full_description' => 'Established under the Department of Chemical Engineering at IIT Kharagpur...',
    'mission' => 'To engineer transformative insights into complex fluid dynamics.',
    'facilities' => 'Equipped with High-Speed Nd:YAG Laser Particle Image Velocimetry (PIV)...'
];

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
    <title>About Us | Multiphase Flow Laboratory | IIT Kharagpur</title>
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
<body class="bg-white text-slate-800 antialiased min-h-screen flex flex-col justify-between">

    <!-- HEADER -->
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

            <nav class="hidden md:flex items-center gap-6 text-xs font-mono-code uppercase tracking-wider text-slate-300">
                <a href="index.php" class="hover:text-cyan-300 transition">Home</a>
                <a href="research.php" class="hover:text-cyan-300 transition">Research</a>
                <a href="index.php#publications" class="hover:text-cyan-300 transition">Publications</a>
                <a href="index.php#people" class="hover:text-cyan-300 transition">People</a>
                <a href="gallery.php" class="hover:text-cyan-300 transition">Gallery</a>
                <a href="about.php" class="text-cyan-400 font-bold border-b-2 border-cyan-400 pb-1">About</a>
                <a href="login.php" class="bg-cyan-500 text-slate-950 font-bold px-3.5 py-1.5 rounded-full hover:bg-cyan-400 transition">Portal</a>
            </nav>
        </div>
    </header>

    <!-- BANNER -->
    <div class="bg-[#0d1624] text-white py-14 px-6 md:px-20 border-b border-slate-800">
        <div class="max-w-5xl mx-auto space-y-2">
            <div class="text-[11px] font-mono-code tracking-[0.3em] uppercase text-cyan-400">Institutional Overview</div>
            <h1 class="text-4xl md:text-5xl font-serif-title font-bold leading-tight">
                About the Laboratory
            </h1>
            <p class="text-slate-400 text-xs md:text-sm font-mono-code pt-1">
                Department of Chemical Engineering &bull; Indian Institute of Technology Kharagpur
            </p>
        </div>
    </div>

    <!-- MAIN BODY -->
    <main class="max-w-5xl mx-auto px-6 md:px-12 py-14 space-y-12 flex-1 w-full">
        
        <!-- SECTION 1: DETAILED HISTORY & OVERVIEW -->
        <section class="space-y-4">
            <div class="flex items-center gap-3">
                <span class="w-2 h-6 bg-[#8a1538] rounded-full"></span>
                <h2 class="text-2xl font-serif-title font-bold text-slate-950">
                    <?php echo htmlspecialchars($about['title']); ?>
                </h2>
            </div>
            <div class="text-sm text-slate-700 leading-relaxed space-y-4 whitespace-pre-line pl-5 border-l border-slate-200">
                <?php echo htmlspecialchars($about['full_description']); ?>
            </div>
        </section>

        <!-- SECTION 2: LAB MISSION & SCIENTIFIC CHARTER -->
        <?php if (!empty($about['mission'])): ?>
            <section class="bg-slate-50 border border-slate-200 rounded-2xl p-8 space-y-3">
                <div class="text-xs font-mono-code uppercase font-bold text-[#8a1538] tracking-widest">
                    Scientific Mission & Charter
                </div>
                <p class="text-base font-medium text-slate-900 leading-relaxed italic">
                    &ldquo;<?php echo htmlspecialchars($about['mission']); ?>&rdquo;
                </p>
            </section>
        <?php endif; ?>

        <!-- SECTION 3: FACILITIES & EXPERIMENTAL CAPABILITIES -->
        <?php if (!empty($about['facilities'])): ?>
            <section class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="w-2 h-6 bg-cyan-600 rounded-full"></span>
                    <h3 class="text-xl font-serif-title font-bold text-slate-950">
                        Instrumentation & Computational Facilities
                    </h3>
                </div>
                <div class="text-sm text-slate-700 leading-relaxed bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <?php echo nl2br(htmlspecialchars($about['facilities'])); ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- SECTION 4: LOCATION & CONTACT -->
        <section class="border-t border-slate-200 pt-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-xs font-mono-code text-slate-500">
            <div>
                <strong class="text-slate-800">Multiphase Flow Laboratory</strong><br>
                Room CHE-201 / 3-249, Dept. of Chemical Engineering<br>
                IIT Kharagpur, West Bengal 721302, India
            </div>
            <div>
                <a href="index.php#people" class="text-cyan-700 font-bold hover:underline">&rarr; Meet the Principal Investigator & Scholars</a>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-[#0b1017] text-slate-400 py-8 px-6 md:px-20 border-t border-slate-800 text-xs font-mono-code">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>&copy; <?php echo date('Y'); ?> Multiphase Flow Laboratory &bull; IIT Kharagpur</div>
            <div><a href="index.php" class="text-cyan-400 hover:underline">&larr; Return to Public Homepage</a></div>
        </div>
    </footer>

</body>
</html>