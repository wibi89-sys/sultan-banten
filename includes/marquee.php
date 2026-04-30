<?php
/**
 * Marquee Announcement Component
 */

$marqueeAnnouncements = getMarqueeAnnouncements($user['upt_id'] ?? null);
$instansi = getInstansi();
$globalRunningText = $instansi['running_text'] ?? '';

if (!empty($marqueeAnnouncements) || !empty($globalRunningText)): ?>
<div class="marquee-container <?= !isLoggedIn() ? 'full-width' : '' ?>">
    <?php if (!empty($marqueeAnnouncements)): ?>
    <div class="marquee-label">
        <i class="fas fa-bullhorn me-2"></i> INFO
    </div>
    <?php endif; ?>
    <div class="marquee-content">
        <?php if (!empty($globalRunningText)): ?>
        <div class="marquee-item global-greeting">
            <i class="fas fa-star me-2 text-warning"></i>
            <strong><?= htmlspecialchars($globalRunningText) ?></strong>
            <i class="fas fa-star ms-2 text-warning"></i>
        </div>
        <?php endif; ?>

        <?php foreach ($marqueeAnnouncements as $m): ?>
        <div class="marquee-item">
            <span class="kategori kategori-<?= strtolower($m['kategori']) ?>"><?= $m['kategori'] ?></span>
            <strong><?= htmlspecialchars($m['judul']) ?>:</strong> 
            <span class="ms-2"><?= strip_tags($m['isi']) ?></span>
        </div>
        <?php endforeach; ?>
        
        <?php // Duplicate for smooth continuous loop ?>
        <?php if (!empty($globalRunningText)): ?>
        <div class="marquee-item global-greeting">
            <i class="fas fa-star me-2 text-warning"></i>
            <strong><?= htmlspecialchars($globalRunningText) ?></strong>
            <i class="fas fa-star ms-2 text-warning"></i>
        </div>
        <?php endif; ?>

        <?php foreach ($marqueeAnnouncements as $m): ?>
        <div class="marquee-item">
            <span class="kategori kategori-<?= strtolower($m['kategori']) ?>"><?= $m['kategori'] ?></span>
            <strong><?= htmlspecialchars($m['judul']) ?>:</strong> 
            <span class="ms-2"><?= strip_tags($m['isi']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    /* Adjust main content and footer to not be covered by marquee */
    .main-content {
        padding-bottom: 40px !important;
    }
    footer {
        margin-bottom: 40px !important;
    }
    
    .marquee-container.full-width {
        left: 0 !important;
    }
    
    .marquee-label {
        background: var(--bg-gradient) !important;
    }
    
    .global-greeting {
        color: var(--primary-600) !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
    }
    
    [data-theme="dark"] .global-greeting {
        color: var(--primary-400) !important;
    }
    
    .kategori-pengumuman { background: var(--primary-500); color: white; }
    .kategori-berita { background: var(--success-500); color: white; }
    .kategori-urgent { background: var(--danger-500); color: white; }
    .kategori-info { background: var(--accent-500); color: #000; }
</style>
<?php endif; ?>
