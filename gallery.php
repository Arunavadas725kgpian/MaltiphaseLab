<?php
require_once 'db.php';

// সব বছরগুলো ফেচ করা
$years_res = $conn->query("SELECT DISTINCT year FROM lab_gallery ORDER BY year DESC");
$available_years = [];
if ($years_res && $years_res->num_rows > 0) {
    while ($r = $years_res->fetch_assoc()) {
        $available_years[] = intval($r['year']);
    }
}

// ডিফল্ট বছর নির্ধারণ
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : (!empty($available_years) ? $available_years[0] : intval(date('Y')));

// নির্বাচিত বছরের ছবিগুলো ফেচ করা
$items_query = $conn->prepare("SELECT * FROM lab_gallery WHERE year = ? ORDER BY gallery_id DESC");
$items_query->bind_param("i", $selected_year);
$items_query->execute();
$gallery_items = $items_query->get_result();

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
    <title>Gallery <?php echo $selected_year; ?> | Multiphase Flow Lab</title>
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

    <!-- TOP HEADER -->
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
                <a href="gallery.php" class="text-cyan-400 font-bold border-b-2 border-cyan-400 pb-1">Gallery</a>
                <a href="login.php" class="bg-cyan-500 text-slate-950 font-bold px-3.5 py-1.5 rounded-full hover:bg-cyan-400 transition">Portal</a>
            </nav>
        </div>
    </header>

    <!-- MIT HML STYLE GALLERY HERO & YEAR FILTER BAR -->
    <div class="max-w-6xl mx-auto px-6 md:px-12 pt-12 pb-6 w-full">
        
        <h1 class="text-3xl md:text-4xl font-serif-title font-bold text-slate-950 mb-6">
            Gallery <?php echo $selected_year; ?>
        </h1>

        <!-- YEAR FILTER TABS -->
        <div class="flex flex-wrap items-center gap-6 text-sm font-semibold border-b border-slate-200 pb-4 mb-10">
            <?php if (!empty($available_years)): ?>
                <?php foreach ($available_years as $yr): ?>
                    <a href="gallery.php?year=<?php echo $yr; ?>" 
                       class="<?php echo ($yr == $selected_year) ? 'text-slate-950 font-bold border-b-2 border-[#8a1538] pb-1' : 'text-slate-600 hover:text-slate-900 transition'; ?>">
                        <?php echo $yr; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <span class="text-slate-900 font-bold"><?php echo date('Y'); ?></span>
            <?php endif; ?>
        </div>

        <!-- GALLERY PHOTO FEED -->
        <div class="space-y-16">
            <?php if ($gallery_items && $gallery_items->num_rows > 0): ?>
                <?php while ($item = $gallery_items->fetch_assoc()): ?>
                    <div class="space-y-3">
                        <h3 class="text-lg md:text-xl font-bold text-slate-900 font-sans leading-snug">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>

                        <div class="rounded-xl overflow-hidden border border-slate-200 shadow-md bg-slate-950 max-w-4xl max-h-[550px] flex items-center justify-center">
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="w-full h-auto max-h-[550px] object-cover object-center">
                        </div>

                        <?php if (!empty($item['description'])): ?>
                            <p class="text-xs text-slate-600 max-w-4xl leading-relaxed pt-1">
                                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="p-12 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                    <i class="fa-regular fa-images text-4xl text-slate-300 mb-3"></i>
                    <p class="text-sm font-mono-code text-slate-500">No gallery photographs published for <?php echo $selected_year; ?> yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- FOOTER -->
    <footer class="bg-[#0b1017] text-slate-400 py-8 px-6 md:px-20 border-t border-slate-800 text-xs font-mono-code mt-20">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>&copy; <?php echo date('Y'); ?> Multiphase Flow Laboratory &bull; IIT Kharagpur</div>
            <div><a href="index.php" class="text-cyan-400 hover:underline">&larr; Return to Public Homepage</a></div>
        </div>
    </footer>

</body>
</html>