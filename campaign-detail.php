<?php
// ============================================================
// ChamaFunds – campaign-detail.php  (v4 — major redesign)
//<?php
// ============================================================
// ChamaFunds – campaign-detail.php  (v4 — major redesign)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$id        = (int)($_GET['id']   ?? 0);
$slug      = $conn->real_escape_string($_GET['slug'] ?? '');
$condition = $id ? "c.campaign_id = $id" : "c.slug = '$slug'";

$result = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name, u.email AS campaigner_email,
            u.avatar_url AS campaigner_avatar, u.phone AS campaigner_phone,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c JOIN users u ON c.campaigner_id = u.user_id
     WHERE $condition LIMIT 1"
);
if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    include __DIR__ . '/includes/header.php';
    echo '<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding-top:80px;">
    <div style="text-align:center;padding:40px;">
      <i class="fas fa-search" style="font-size:3rem;color:#d1d5db;margin-bottom:16px;display:block;"></i>
      <h2 style="color:#1A2A6C;font-weight:800;margin-bottom:8px;">Campaign Not Found</h2>
      <p style="color:#9ca3af;margin-bottom:24px;">This campaign may have been removed or the link is incorrect.</p>
      <a href="<?= BASE ?>/campaign-drives.php" class="btn btn-primary">Browse Campaigns</a>
    </div></div>';
    include __DIR__ . '/includes/footer.php'; exit;
}
$c   = $result->fetch_assoc();
$cid = $c['campaign_id'];
$conn->query("UPDATE campaigns SET view_count = view_count + 1 WHERE campaign_id = $cid");

// ── Category hero images — African people, African context ──
$categoryHeros = [
    // African nurse with patient in clinic
    'Medical'    => 'https://images.unsplash.com/photo-1666214280557-f1b5022eb634?w=1600&q=80',
    // African children studying in classroom
    'Education'  => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1600&q=80',
    // African community gathered together
    'Community'  => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1600&q=80',
    // African mother and children
    'Family'     => 'https://images.unsplash.com/photo-1602928321679-560bb453f190?w=1600&q=80',
    // African entrepreneurs in meeting
    'Business'   => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1600&q=80',
    // Humanitarian aid / relief Africa
    'Emergency'  => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?w=1600&q=80',
    // African wedding celebration
    'Marriage'   => 'https://images.unsplash.com/photo-1607462109225-6b64ae2dd3cb?w=1600&q=80',
    // Candles / memorial — dignified
    'Funeral'    => 'https://images.unsplash.com/photo-1501436513145-30f24e19fcc8?w=1600&q=80',
    // African farmer in field
    'Agriculture'=> 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=1600&q=80',
    // African church / praise worship
    'Religion'   => 'https://images.unsplash.com/photo-1438232992991-995b671e4b8a?w=1600&q=80',
    // African athletes running
    'Sports'     => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=1600&q=80',
    // African hands joined together
    'Other'      => 'https://images.unsplash.com/photo-1504439468489-c8920d796a29?w=1600&q=80',
];
$heroImg = $categoryHeros[$c['category']] ?? $categoryHeros['Other'];

// ── Uploaded campaign images ─────────────────────────────────
$imgsResult = $conn->query(
    "SELECT image_id, image_url, is_cover, sort_order FROM campaign_images
     WHERE campaign_id=$cid ORDER BY is_cover DESC, sort_order ASC LIMIT 10"
);
$campaignImages = [];
while ($img = $imgsResult->fetch_assoc()) $campaignImages[] = $img;
if (empty($campaignImages) && !empty($c['image_url'])) {
    $campaignImages[] = ['image_id'=>0,'image_url'=>$c['image_url'],'is_cover'=>1,'sort_order'=>0];
}

// ── Resolve every image URL to a fully-qualified absolute URL ─
// Stored values can be:  /uploads/campaigns/file.jpg  (relative)
//                     or https://chamafunds.com/...    (already absolute)
$protocol_cd = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protocol_cd = trim($_SERVER['HTTP_X_FORWARDED_PROTO']);
}
function resolveImgUrl($url, $base) {
    if (empty($url)) return '';
    if (strpos($url, 'http') === 0) return $url; // already absolute
    return $base . '/' . ltrim($url, '/');
}
foreach ($campaignImages as &$img) {
    $img['image_url'] = resolveImgUrl($img['image_url'], BASE);
}
unset($img);
// Also resolve the main campaign image_url
if (!empty($c['image_url']) && strpos($c['image_url'], 'http') !== 0) {
    $c['image_url'] = BASE . '/' . ltrim($c['image_url'], '/');
}

// ── Donations ────────────────────────────────────────────────
$dons = $conn->query(
    "SELECT donor_name, is_anonymous, amount, mobile_money_network, payment_date
     FROM donations WHERE campaign_id=$cid AND status='completed'
     ORDER BY payment_date DESC LIMIT 20"
);
$totalDonorsAll = (int)$conn->query(
    "SELECT COUNT(*) FROM donations WHERE campaign_id=$cid AND status='completed'"
)->fetch_row()[0];

// ── Last 5 donations preview (shown below story) ─────────────
$recentDons = $conn->query(
    "SELECT donor_name, is_anonymous, amount, payment_date
     FROM donations WHERE campaign_id=$cid AND status='completed'
     ORDER BY payment_date DESC LIMIT 5"
);
$recentDonsArr = [];
while ($rd = $recentDons->fetch_assoc()) $recentDonsArr[] = $rd;

// ── Similar campaigns (same category, exclude current) ───────
$catEscSim = $conn->real_escape_string($c['category']);
$simResult = $conn->query(
    "SELECT c.campaign_id, c.title, c.category, c.currency, c.raised_amount,
            c.goal_amount, c.image_url, c.contributor_count,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     WHERE c.category = '$catEscSim'
       AND c.campaign_id != $cid
       AND c.status = 'active'
     ORDER BY c.raised_amount DESC
     LIMIT 4"
);
$similarCampaigns = [];
while ($sc = $simResult->fetch_assoc()) {
    $sc['image_url'] = resolveImgUrl($sc['image_url'], BASE);
    $similarCampaigns[] = $sc;
}
// If not enough same-category, fill with other active campaigns
if (count($similarCampaigns) < 4) {
    $existing = array_merge([$cid], array_column($similarCampaigns, 'campaign_id'));
    $excl = implode(',', $existing);
    $need = 4 - count($similarCampaigns);
    $fillResult = $conn->query(
        "SELECT c.campaign_id, c.title, c.category, c.currency, c.raised_amount,
                c.goal_amount, c.image_url, c.contributor_count,
                ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
                DATEDIFF(c.end_date, NOW()) AS days_left
         FROM campaigns c
         WHERE c.campaign_id NOT IN ($excl)
           AND c.status = 'active'
         ORDER BY c.created_at DESC
         LIMIT $need"
    );
    while ($sc = $fillResult->fetch_assoc()) {
        $sc['image_url'] = resolveImgUrl($sc['image_url'], BASE);
        $similarCampaigns[] = $sc;
    }
}

$pct       = min(100, (float)$c['pct']);
$daysLeft  = (int)$c['days_left'];
$daysStr   = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Campaign ended');
$daysUrgent= $daysLeft >= 0 && $daysLeft <= 5;
$remaining = max(0, $c['goal_amount'] - $c['raised_amount']);
$isOwner   = isset($_SESSION['user_id']) &&
             ($_SESSION['user_id'] == $c['campaigner_id'] || ($_SESSION['role'] ?? '') === 'admin');

$protocol     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
// Also check proxy headers (common on cPanel/shared hosting)
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protocol = trim($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : $protocol;
}
$canonicalUrl = BASE . '/campaign-detail.php?id=' . $cid;

// ── OG Image — campaign's own image, always absolute HTTPS ──
// By this point $campaignImages URLs are already resolved by resolveImgUrl()
// Priority: 1) first campaign_images entry  2) campaigns.image_url  3) logo fallback

$ogImage = '';

if (!empty($campaignImages[0]['image_url'])) {
    $ogImage = $campaignImages[0]['image_url']; // already absolute from resolveImgUrl()
} elseif (!empty($c['image_url'])) {
    $ogImage = $c['image_url']; // already resolved above
}

// If still relative for any reason, make it absolute
if (!empty($ogImage) && strpos($ogImage, 'http') !== 0) {
    $ogImage = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($ogImage, '/');
}

// Force HTTPS — WhatsApp and Facebook reject HTTP images
if (!empty($ogImage)) {
    $ogImage = preg_replace('#^http://#', 'https://', $ogImage);
}

// Fallback to dynamic branded campaign card
if (empty($ogImage)) {
    $ogTitle_enc = urlencode($c['title']);
    $ogSub_enc   = urlencode($c['currency'] . ' ' . number_format($c['raised_amount']) . ' raised · ' . $totalDonorsAll . ' supporters');
    $ogImage     = 'https://' . $_SERVER['HTTP_HOST'] . '/campaign-og.php?title=' . $ogTitle_enc . '&sub=' . $ogSub_enc;
}

$ogTitle      = htmlspecialchars($c['title'], ENT_QUOTES);
$ogDesc       = htmlspecialchars(
    ($c['currency'] . ' ' . number_format($c['raised_amount']) . ' raised · ' .
     number_format($totalDonorsAll) . ' supporters · ' .
     substr(strip_tags($c['description']), 0, 120) . '…'),
    ENT_QUOTES
);
$pageTitle    = htmlspecialchars($c['title']).' – ChamaFunds';
$pageDescription = $ogDesc;

// ── Build rich share text ────────────────────────────────────
$raised_fmt   = number_format($c['raised_amount']);
$goal_fmt     = number_format($c['goal_amount']);
$shareText    = $c['title'] . "\n\n"
              . substr(strip_tags($c['description']), 0, 120) . "…\n\n"
              . $c['currency'] . " " . $raised_fmt . " raised of " . $c['currency'] . " " . $goal_fmt . " goal\n"
              . $totalDonorsAll . " contributors · " . $daysStr . "\n\n"
              . "Support here: " . $canonicalUrl;
$shareTextEnc = urlencode($shareText);
$shareTitleEnc = urlencode($c['title'] . ' – ChamaFunds');

$extraCss = <<<HTML
  <!-- ══ Open Graph (Facebook · WhatsApp · Telegram · iMessage) ══ -->
  <meta property="og:type"              content="website"/>
  <meta property="og:url"               content="{$canonicalUrl}"/>
  <meta property="og:title"             content="{$ogTitle}"/>
  <meta property="og:description"       content="{$ogDesc}"/>
  <meta property="og:image"             content="{$ogImage}"/>
  <meta property="og:image:secure_url"  content="{$ogImage}"/>
  <meta property="og:image:width"       content="1200"/>
  <meta property="og:image:height"      content="630"/>
  <meta property="og:image:alt"         content="{$ogTitle}"/>
  <meta property="og:site_name"         content="ChamaFunds"/>
  <meta property="og:locale"            content="en_US"/>
  <!-- ══ Twitter / X Card ══ -->
  <meta name="twitter:card"             content="summary_large_image"/>
  <meta name="twitter:site"             content="@ChamaFunds"/>
  <meta name="twitter:title"            content="{$ogTitle}"/>
  <meta name="twitter:description"      content="{$ogDesc}"/>
  <meta name="twitter:image"            content="{$ogImage}"/>
  <meta name="twitter:image:alt"        content="{$ogTitle}"/>
  <!-- ══ Canonical ══ -->
  <link rel="canonical"                 href="{$canonicalUrl}"/>
HTML;

include __DIR__ . '/includes/header.php';
?>

<!-- REST OF YOUR HTML CONTENT REMAINS EXACTLY THE SAME -->
<!-- (All the HTML, CSS, and JavaScript below stays unchanged) -->

<div class="cd-page">

<!-- ══════════════════════════════════════
     HERO — fixed category background
══════════════════════════════════════════ -->
<div class="cd-hero" style="background-image:url('<?= htmlspecialchars($heroImg) ?>');">
  <div class="cd-hero-overlay"></div>
  <div class="cd-hero-inner">
    <div class="container">

      <!-- Breadcrumb -->
      <nav class="cd-breadcrumb">
        <a href="<?= BASE ?>/index.php">Home</a>
        <span>/</span>
        <a href="<?= BASE ?>/campaign-drives.php">Campaigns</a>
        <span>/</span>
        <span><?= htmlspecialchars($c['category']) ?></span>
      </nav>

      <!-- Category + status -->
      <div class="cd-hero-badges">
        <span class="cd-cat-badge"><?= htmlspecialchars($c['category']) ?></span>
        <span class="cd-status-badge cd-st-<?= $c['status'] ?>">
          <span class="cd-status-dot"></span><?= ucfirst($c['status']) ?>
        </span>
        <?php if ($daysUrgent && $daysLeft >= 0): ?>
        <span class="cd-urgent-badge">
          <i class="fas fa-bolt"></i> <?= $daysLeft === 0 ? 'Ends today' : "$daysLeft days left" ?>
        </span>
        <?php endif; ?>
      </div>

      <!-- Title -->
      <h1 class="cd-hero-title"><?= htmlspecialchars($c['title']) ?></h1>

      <!-- Meta row: campaigner · location · views · shares -->
      <div class="cd-hero-meta">
        <span class="cd-meta-item">
          <?php if ($c['campaigner_avatar']): ?>
            <img src="<?= htmlspecialchars($c['campaigner_avatar']) ?>" class="cd-mini-avatar" alt="" />
          <?php else: ?>
            <span class="cd-mini-avatar cd-mini-init"><?= strtoupper(substr($c['campaigner_name'],0,1)) ?></span>
          <?php endif; ?>
          <strong><?= htmlspecialchars($c['campaigner_name']) ?></strong>
        </span>
        <span class="cd-meta-item">
          <i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($c['country']) ?>
        </span>
        <span class="cd-meta-item">
          <i class="fas fa-eye"></i><?= number_format($c['view_count']) ?> views
        </span>
        <span class="cd-meta-item">
          <i class="fas fa-share-alt"></i> <span id="shareCountHero"><?= number_format($c['share_count']) ?></span> shares
        </span>
        <span class="cd-meta-item <?= $daysUrgent ? 'cd-urgent' : '' ?>">
          <i class="fas fa-clock"></i><?= htmlspecialchars($daysStr) ?>
        </span>
      </div>

    </div>
  </div>
</div>
<!-- Hero progress bar pinned to bottom -->
<div class="cd-hero-bar">
  <div class="cd-hero-bar-fill" style="width:<?= $pct ?>%"></div>
</div>

<!-- ══════════════════════════════════════
     STICKY TAB NAV
══════════════════════════════════════════ -->
<div class="cd-tabbar" id="cdTabbar">
  <div class="container">
    <div class="cd-tabbar-row">
      <div class="cd-tabs">
        <button class="cd-tab active" data-tab="story">
          <i class="fas fa-book-open"></i> Story
        </button>
        <button class="cd-tab" data-tab="donations">
          <i class="fas fa-heart"></i> Donations
          <span class="cd-tab-pill"><?= number_format($totalDonorsAll) ?></span>
        </button>
        <button class="cd-tab" data-tab="how">
          <i class="fas fa-question-circle"></i> How It Works
        </button>
      </div>
      <?php if ($c['status']==='active'): ?>
      <!-- <a href="#donateWidget" class="cd-tab-donate">
        <i class="fas fa-heart"></i> Donate Now
      </a> -->
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     MAIN BODY
══════════════════════════════════════════ -->
<div class="cd-body">
  <div class="container">
    <div class="cd-layout">

      <!-- ════════════ LEFT ════════════ -->
      <div class="cd-left">

        <!-- ── PANEL: Story ── -->
        <div class="cd-panel active" id="panel-story">

          <!-- Campaign uploaded photos -->
          <?php if (!empty($campaignImages)): ?>
          <div class="cd-photos-section">
            <div class="cd-photo-main" id="cdPhotoMain">
              <img src="<?= htmlspecialchars($campaignImages[0]['image_url']) ?>"
                   alt="<?= htmlspecialchars($c['title']) ?>"
                   id="cdPhotoMainImg" />
            </div>
            <?php if (count($campaignImages) > 1): ?>
            <div class="cd-photo-thumbs">
              <?php foreach ($campaignImages as $i => $img): ?>
              <button class="cd-photo-thumb <?= $i===0?'active':'' ?>"
                      onclick="switchPhoto(this,'<?= htmlspecialchars($img['image_url'],ENT_QUOTES) ?>')">
                <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="" />
              </button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- ── Copy Link button (below image) ──────────────── -->
          <div class="cd-order-1" style="margin-bottom:14px;">
            <button onclick="copyLink(this)"
                    class="cd-copy-btn"
                    style="width:100%;background:#1A2A6C;color:#fff;border:none;border-radius:12px;
                           padding:11px 20px;font-size:.88rem;font-weight:700;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;gap:8px;">
              <i class="fas fa-link"></i> Copy Campaign Link
            </button>
          </div>

          <!-- ── Mini progress bar ─────────────────────────── -->
          <div class="cd-mini-progress cd-order-2">
            <div class="cd-mini-prog-row">
              <span class="cd-mini-prog-raised"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</span>
              <span class="cd-mini-prog-pct"><?= $pct ?>% of <?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></span>
            </div>
            <div class="cd-mini-prog-track">
              <div class="cd-mini-prog-fill" style="width:<?= $pct ?>%;"></div>
            </div>
            <div class="cd-mini-prog-stats">
              <span><i class="fas fa-users"></i> <?= number_format($totalDonorsAll) ?> supporter<?= $totalDonorsAll !== 1 ? 's' : '' ?></span>
              <span><i class="fas fa-eye"></i> <?= number_format($c['view_count']) ?> views</span>
              <span><i class="fas fa-share-alt"></i> <span id="shareCountMini"><?= number_format($c['share_count']) ?></span> shares</span>
              <span class="<?= $daysUrgent ? 'cd-urgent-txt' : '' ?>"><i class="fas fa-clock"></i> <?= htmlspecialchars($daysStr) ?></span>
            </div>
          </div>

          <!-- Campaign story -->
          <div class="cd-section cd-story-section cd-order-3">
            <h2 class="cd-section-h">Campaign Story</h2>
            <div class="cd-story">
              <?= nl2br(htmlspecialchars($c['description'])) ?>
            </div>
            <div class="cd-verified">
              <i class="fas fa-shield-alt"></i>
              Verified campaign — funds go directly to the beneficiary's mobile money account.
            </div>
          </div>

          <?php if (!empty($recentDonsArr)):
            // ── Time-ago helper ───────────────────────────────
            function timeAgo($datetime) {
                $diff = time() - strtotime($datetime);
                if ($diff < 60)           return $diff . 's ago';
                if ($diff < 3600)         return floor($diff/60) . 'm ago';
                if ($diff < 86400)        return floor($diff/3600) . 'h ago';
                if ($diff < 604800)       return floor($diff/86400) . 'd ago';
                return date('M j', strtotime($datetime));
            }
          ?>
          <!-- ── Recent Supporters (after story) ─────────────── -->
          <div class="cd-section cd-recent-supporters cd-order-4">
            <div class="cd-section-head">
              <h2 class="cd-section-h">
                <i class="fas fa-heart" style="color:#FF6B4A;margin-right:8px;font-size:.9em;"></i>
                Recent Supporters
              </h2>
              <?php if ($totalDonorsAll > 5): ?>
              <button class="cd-see-all-btn" onclick="switchTab('donations')">
                See all <?= number_format($totalDonorsAll) ?> <i class="fas fa-arrow-right"></i>
              </button>
              <?php endif; ?>
            </div>
            <div class="cd-recent-list">
              <?php foreach ($recentDonsArr as $rd):
                $isAnon   = (bool)$rd['is_anonymous'];
                $name     = $isAnon ? 'Anonymous' : htmlspecialchars($rd['donor_name']);
                if ($isAnon) {
                    $initials = '?';
                } else {
                    $parts    = explode(' ', trim($rd['donor_name']));
                    $initials = strtoupper(substr($parts[0], 0, 1));
                    if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
                }
                $colours  = ['#FF6B4A','#1A2A6C','#10b981','#f59e0b','#3b82f6','#8b5cf6','#ec4899'];
                $colIdx   = $isAnon ? 0 : (ord($initials[0]) % count($colours));
                $bgColour = $colours[$colIdx];
              ?>
              <div class="cd-recent-row">
                <div class="cd-recent-ava" style="background:<?= $bgColour ?>;">
                  <?= $isAnon ? '<i class="fas fa-user-secret"></i>' : htmlspecialchars($initials) ?>
                </div>
                <div class="cd-recent-body">
                  <div class="cd-recent-name"><?= $name ?></div>
                  <div class="cd-recent-meta"><?= timeAgo($rd['payment_date']) ?></div>
                </div>
                <div class="cd-recent-amt">
                  +<?= $c['currency'] ?> <?= number_format($rd['amount']) ?>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($totalDonorsAll > 5): ?>
            <button class="cd-see-all-full" onclick="switchTab('donations')">
              <i class="fas fa-users" style="margin-right:6px;"></i>
              See all <?= number_format($totalDonorsAll) ?> supporters
            </button>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Progress block (sidebar on desktop, inline on mobile) -->
          <div class="cd-progress-card cd-stats-section">
            <div class="cd-prog-numbers">
              <div>
                <p class="cd-prog-raised"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?></p>
                <p class="cd-prog-sub">raised of <?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?> goal</p>
              </div>
              <div style="text-align:right">
                <p class="cd-prog-pct"><?= $pct ?>%</p>
                <p class="cd-prog-sub <?= $daysUrgent?'cd-urgent':'' ?>"><?= htmlspecialchars($daysStr) ?></p>
              </div>
            </div>
            <div class="cd-prog-track">
              <div class="cd-prog-fill" data-w="<?= $pct ?>"></div>
            </div>
            <div class="cd-prog-stats">
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val"><?= number_format($totalDonorsAll) ?></span>
                <span class="cd-prog-stat-lbl">Supporters</span>
              </div>
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val"><?= $c['currency'] ?> <?= number_format($remaining) ?></span>
                <span class="cd-prog-stat-lbl">Still needed</span>
              </div>
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val"><?= number_format($c['view_count']) ?></span>
                <span class="cd-prog-stat-lbl">Views</span>
              </div>
              <div class="cd-prog-stat">
                <span class="cd-prog-stat-val" id="shareCountCard"><?= number_format($c['share_count']) ?></span>
                <span class="cd-prog-stat-lbl">Shares</span>
              </div>
            </div>
          </div>

        </div><!-- /panel-story -->

        <!-- ── PANEL: Donations ── -->
        <div class="cd-panel" id="panel-donations">
          <div class="cd-section">
            <div class="cd-section-head">
              <h2 class="cd-section-h">
                <?= number_format($totalDonorsAll) ?> <?= $totalDonorsAll === 1 ? 'Supporter' : 'Supporters' ?>
              </h2>
              <span class="cd-live-dot"><span></span>Live</span>
            </div>

            <?php if ($dons && $dons->num_rows > 0):
              $donations_arr = [];
              while ($d = $dons->fetch_assoc()) $donations_arr[] = $d;
            ?>
            <div class="cd-don-list">
              <?php foreach ($donations_arr as $d):
                $isAnon   = (bool)$d['is_anonymous'];
                $donName  = $isAnon ? 'Anonymous' : htmlspecialchars($d['donor_name']);
                // Build initials
                if ($isAnon) {
                    $dInit = '?'; $dBg = '#9ca3af';
                } else {
                    $parts = explode(' ', trim($d['donor_name']));
                    $dInit = strtoupper(substr($parts[0], 0, 1));
                    if (isset($parts[1])) $dInit .= strtoupper(substr($parts[1], 0, 1));
                    $dColours = ['#FF6B4A','#1A2A6C','#10b981','#f59e0b','#3b82f6','#8b5cf6','#ec4899'];
                    $dBg = $dColours[ord($dInit[0]) % count($dColours)];
                }
              ?>
              <div class="cd-don-row">
                <div class="cd-don-ava" style="background:<?= $dBg ?>;">
                  <?= $isAnon ? '<i class="fas fa-user-secret"></i>' : htmlspecialchars($dInit) ?>
                </div>
                <div class="cd-don-body">
                  <div class="cd-don-top">
                    <span class="cd-don-name"><?= $donName ?></span>
                    <span class="cd-don-amt">+<?= $c['currency'] ?> <?= number_format($d['amount']) ?></span>
                  </div>
                  <div class="cd-don-meta-row">
                    <?= timeAgo($d['payment_date']) ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($totalDonorsAll > 20): ?>
            <p class="cd-more-note">+<?= number_format($totalDonorsAll - 20) ?> more generous supporters</p>
            <?php endif; ?>

            <?php else: ?>
            <div class="cd-empty">
              <i class="fas fa-seedling"></i>
              <p><strong>Be the first to contribute!</strong></p>
              <p>Every donation, big or small, makes a real difference.</p>
            </div>
            <?php endif; ?>
          </div>
        </div><!-- /panel-donations -->

        <!-- ── PANEL: How It Works ── -->
        <div class="cd-panel" id="panel-how">
          <div class="cd-section">
            <h2 class="cd-section-h">How Donating Works</h2>
            <div class="cd-how-list">
              <?php
              $steps = [
                ['Enter your amount', 'Choose any amount — minimum '.$c['currency'].' 1,000.'],
                ['Secure checkout', 'You are redirected to Pesapal\'s secure payment page. Enter your mobile money details there.'],
                ['Confirm on your phone', 'A payment prompt is sent to your phone. Enter your mobile money PIN to complete.'],
                ['Campaigner withdraws', 'After admin review (48 hrs max), funds are paid out to mobile money same day.'],
              ];
              foreach ($steps as $i => $step): ?>
              <div class="cd-how-row">
                <div class="cd-how-num"><?= $i+1 ?></div>
                <div>
                  <p class="cd-how-title"><?= $step[0] ?></p>
                  <p class="cd-how-desc"><?= $step[1] ?></p>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

            <div class="cd-faq-list">
              <?php
              $faqs = [
                ['Is my donation secure?', 'Yes. All payments are processed through Pesapal — a licensed payment provider. Your PIN never leaves your phone, encrypted with 256-bit SSL.'],
                ['Can I donate anonymously?', 'Yes — toggle "Remain anonymous" in the donation form. Your name will appear as "Anonymous" on the public contributions list.'],
                ['Which networks are supported?', 'MTN Mobile Money, Airtel Money, Orange Money, and Safaricom M-Pesa across Uganda, Kenya, Rwanda, Tanzania and more.'],
                ['How do I know funds reached the right person?', 'Every contribution is logged on the live public ledger on this page. All campaigns are verified before going live.'],
                ['How does the campaigner get funds?', 'After admin review (48 hrs max), funds are paid out directly to the campaigner\'s mobile money account.'],
              ];
              foreach ($faqs as $faq): ?>
              <div class="cd-faq-item">
                <button class="cd-faq-q" onclick="toggleFaq(this)">
                  <?= $faq[0] ?> <i class="fas fa-chevron-down"></i>
                </button>
                <div class="cd-faq-a"><?= $faq[1] ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div><!-- /panel-how -->

      </div><!-- /cd-left -->

      <!-- ════════════ RIGHT ════════════ -->
      <div class="cd-right">
        <div id="donateWidget" style="position:sticky;top:118px;">

          <?php if ($c['status']==='active'): ?>
          <!-- Donation widget -->
          <div class="cd-widget">
            <div class="cd-widget-head">
              <p class="cd-widget-title">Support This Campaign</p>
              <p class="cd-widget-sub">Fast · Secure · Mobile Money</p>
            </div>

            <div class="cd-error-box" id="donationError" style="display:none;"></div>

            <!-- Quick amounts -->
            <div class="cd-quick">
              <button type="button" class="cd-q-btn" data-amount="5000">UGX 5K</button>
              <button type="button" class="cd-q-btn" data-amount="25000">UGX 10K</button>
              <button type="button" class="cd-q-btn" data-amount="25000">UGX 25K</button>
              <!-- <button type="button" class="cd-q-btn" data-amount="50000">UGX 50K</button> -->
              <button type="button" class="cd-q-btn" data-amount="100000">UGX 100K</button>
            </div>

            <div class="cd-field">
              <label class="cd-label">Amount (<?= $c['currency'] ?>) <span>*</span></label>
              <div class="cd-input-wrap">
                <span class="cd-input-pre"><?= $c['currency'] ?></span>
                <input type="number" id="contributionAmount" class="cd-input" placeholder="e.g. 5000" min="1000" value="5000" />
              </div>
            </div>

            <div class="cd-field">
              <label class="cd-label">Your Name <span>*</span></label>
              <input type="text" id="donorName" class="cd-input" placeholder="e.g. James Mwangi" />
            </div>

            <div class="cd-field">
              <label class="cd-label">Phone Number <span>*</span></label>
              <div class="cd-input-wrap">
                <i class="fas fa-phone cd-input-icon"></i>
                <input type="tel" id="donorPhone" class="cd-input cd-input-icon-pad" placeholder="256712345678" />
              </div>
            </div>

            <div class="cd-field">
              <label class="cd-label">Network</label>
              <select id="momoNetwork" class="cd-input">
                <option>MTN Mobile Money</option>
                <option>Airtel Money</option>
                <option>Orange Money</option>
                <option>Safaricom M-PESA</option>
                <option>Card Payment</option>
              </select>
            </div>

            <div class="cd-field">
              <label class="cd-label">Email <em>(optional)</em></label>
              <input type="email" id="donorEmail" class="cd-input" placeholder="you@email.com" />
            </div>

            <label class="cd-anon">
              <input type="checkbox" id="anonymousToggle" />
              <span class="cd-toggle-track"></span>
              <span>Remain anonymous</span>
            </label>

            <button class="cd-donate-btn cd-donate-btn-full" id="donateBtn" data-campaign="<?= $cid ?>">
              <i class="fas fa-heart"></i>
              Donate <?= $c['currency'] ?> <span id="donateBtnAmt">10,000</span>
            </button>

            <div class="cd-secure">
              <span><i class="fas fa-lock"></i> PawaPay Secured</span>
              <span><i class="fas fa-shield-alt"></i> SSL</span>
              <span><i class="fas fa-check-circle"></i> Verified</span>
            </div>
          </div>

          <!-- WhatsApp share nudge -->
          <a href="https://wa.me/?text=<?= $shareTextEnc ?>"
             target="_blank" class="cd-wa-nudge" onclick="trackShare()">
            <i class="fab fa-whatsapp"></i>
            <span>Share on WhatsApp — doubles donations</span>
            <i class="fas fa-arrow-right"></i>
          </a>

          <!-- Campaigner card — below donate widget -->
          <div class="cd-widget cd-camp-widget">
            <div class="cd-campaigner-row">
              <?php if ($c['campaigner_avatar']): ?>
                <img src="<?= htmlspecialchars($c['campaigner_avatar']) ?>" class="cd-camp-ava" alt="" />
              <?php else: ?>
                <div class="cd-camp-ava cd-camp-ava-init"><?= strtoupper(substr($c['campaigner_name'],0,1)) ?></div>
              <?php endif; ?>
              <div class="cd-camp-info">
                <p style="font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Campaign by</p>
                <p class="cd-camp-name"><?= htmlspecialchars($c['campaigner_name']) ?></p>
                <p class="cd-camp-sub">
                  <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['country']) ?>
                  &nbsp;·&nbsp; <?= date('M Y', strtotime($c['created_at'])) ?>
                </p>
              </div>
              <?php if ($isOwner): ?>
              <a href="<?= BASE ?>/edit-campaign.php?id=<?= $cid ?>" class="cd-edit-btn">
                <i class="fas fa-edit"></i> Edit
              </a>
              <?php endif; ?>
            </div>
          </div>

          <?php else: ?>
          <div class="cd-widget" style="text-align:center;padding:40px 24px;">
            <i class="fas fa-lock" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:14px;"></i>
            <p style="font-weight:800;color:#0f172a;font-size:1rem;margin-bottom:6px;">Campaign <?= ucfirst($c['status']) ?></p>
            <p style="color:#9ca3af;font-size:.86rem;margin-bottom:20px;">No longer accepting donations.</p>
            <a href="<?= BASE ?>/campaign-drives.php" class="cd-donate-btn cd-donate-btn-full">Browse Active Campaigns</a>
          </div>
          <?php endif; ?>

        </div>
      </div><!-- /cd-right -->

    </div><!-- /cd-layout -->
  </div><!-- /container -->
</div><!-- /cd-body -->
</div><!-- /cd-page -->

<!-- ══ MODALS ══ -->
<div class="modal-overlay" id="donationModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-body" style="text-align:center;padding:48px 28px;">
      <div style="font-size:3.5rem;margin-bottom:14px;animation:pop .4s ease;">✅</div>
      <h3 style="font-weight:800;color:#0f172a;font-size:1.2rem;margin-bottom:6px;">Thank You!</h3>
      <p style="color:#6b7280;margin-bottom:6px;">Your contribution of</p>
      <p style="font-size:1.9rem;font-weight:800;color:#FF6B4A;margin-bottom:12px;" id="modalAmount"></p>
      <p style="color:#9ca3af;font-size:.78rem;margin-bottom:6px;">Ref: <code id="modalTxRef" style="background:#f3f4f6;padding:2px 8px;border-radius:6px;"></code></p>
      <p style="color:#9ca3af;font-size:.78rem;margin-bottom:28px;">SMS confirmation sent to your phone shortly.</p>
      <button data-close-modal="donationModal" class="cd-donate-btn cd-donate-btn-full">Done</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="ussdModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header" style="border-bottom:1px solid #f1f5f9;padding-bottom:14px;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:36px;height:36px;background:#FF6B4A;border-radius:10px;display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-mobile-alt" style="color:#fff;font-size:.9rem;"></i>
        </div>
        <div>
          <p style="font-weight:800;color:#0f172a;font-size:.9rem;margin:0;">Mobile Money Payment</p>
          <p style="font-size:.7rem;color:#9ca3af;margin:0;">USSD Push · No app needed</p>
        </div>
      </div>
      <button class="modal-close" onclick="document.getElementById('ussdModal').classList.remove('open')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" style="padding:18px 22px 24px;">
      <div id="ussdStep1">
        <div class="cd-ussd-steps">
          <?php foreach(['We send a payment request to your phone','USSD prompt appears — enter your PIN','Payment confirmed instantly ✓'] as $i=>$st): ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <span class="cd-ussd-n"><?= $i+1 ?></span>
            <span style="font-size:.82rem;color:#374151;"><?= htmlspecialchars($st) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="cd-ussd-table">
          <div class="cd-ussd-row"><span>Amount</span><strong id="ussdAmount">—</strong></div>
          <div class="cd-ussd-row"><span>Campaign</span><strong id="ussdCampaign" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;text-align:right;">—</strong></div>
          <div class="cd-ussd-row"><span>Phone</span><strong id="ussdPhone">—</strong></div>
          <div class="cd-ussd-row" style="border:none;"><span>Network</span><strong id="ussdNetwork">—</strong></div>
        </div>
        <button id="ussdSendBtn" class="cd-donate-btn cd-donate-btn-full" style="margin-bottom:8px;">
          <i class="fas fa-paper-plane"></i> Send Payment Request
        </button>
        <p style="text-align:center;font-size:.7rem;color:#9ca3af;"><i class="fas fa-lock"></i> Your PIN never leaves your phone</p>
      </div>
      <div id="ussdStep2" style="display:none;text-align:center;padding:8px 0;">
        <div style="font-size:3rem;margin-bottom:14px;animation:bounce-phone .7s infinite alternate;">📱</div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:8px;">Check Your Phone!</h4>
        <p style="color:#6b7280;font-size:.86rem;margin-bottom:16px;line-height:1.6;">Enter your <strong>mobile money PIN</strong> when the USSD prompt appears on <strong id="ussdPhoneConfirm"></strong>.</p>
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:.8rem;color:#9ca3af;margin-bottom:16px;">
          <span style="width:7px;height:7px;background:#FF6B4A;border-radius:50%;animation:dots .8s infinite;"></span>
          <span style="width:7px;height:7px;background:#FF6B4A;border-radius:50%;animation:dots .8s .15s infinite;"></span>
          <span style="width:7px;height:7px;background:#FF6B4A;border-radius:50%;animation:dots .8s .3s infinite;"></span>
          <span>Waiting for confirmation…</span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <button id="ussdConfirmBtn" class="cd-donate-btn" style="border-radius:10px;padding:12px;font-size:.88rem;"><i class="fas fa-check"></i> PIN Entered ✓</button>
          <button onclick="document.getElementById('ussdModal').classList.remove('open')" style="border-radius:10px;padding:12px;font-size:.88rem;border:1.5px solid #e2e8f0;background:#fff;font-weight:700;color:#64748b;cursor:pointer;">Cancel</button>
        </div>
      </div>
      <div id="ussdStep3" style="display:none;text-align:center;padding:24px 0;">
        <div style="width:60px;height:60px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
          <i class="fas fa-spinner fa-spin" style="font-size:1.4rem;color:#0f172a;"></i>
        </div>
        <p style="font-weight:800;color:#0f172a;margin-bottom:6px;">Processing…</p>
        <p style="color:#9ca3af;font-size:.84rem;">Confirming with the network.</p>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($similarCampaigns)): ?>
<!-- ── Similar Campaigns ──────────────────────────────────── -->
<section class="cd-similar-section">
  <div class="container">
    <div class="cd-similar-header">
      <h2 class="cd-similar-title">
        <i class="fas fa-th-large"></i> Similar Campaigns
      </h2>
      <a href="<?= BASE ?>/campaign-drives.php?category=<?= urlencode($c['category']) ?>" class="cd-similar-more">
        See all <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    <div class="cd-similar-grid">
      <?php foreach ($similarCampaigns as $sc):
        $sPct      = min(100, (float)$sc['pct']);
        $sDaysLeft = (int)$sc['days_left'];
        $sDaysStr  = $sDaysLeft > 0 ? "$sDaysLeft days left" : ($sDaysLeft === 0 ? 'Ends today' : 'Ended');
        $sImg      = !empty($sc['image_url']) ? htmlspecialchars($sc['image_url']) : 'https://picsum.photos/seed/' . $sc['campaign_id'] . '/400/220';
      ?>
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= $sc['campaign_id'] ?>" class="cd-sim-card">
        <div class="cd-sim-img">
          <img src="<?= $sImg ?>" alt="<?= htmlspecialchars($sc['title']) ?>" loading="lazy" />
          <span class="cd-sim-cat"><?= htmlspecialchars($sc['category']) ?></span>
        </div>
        <div class="cd-sim-body">
          <p class="cd-sim-title"><?= htmlspecialchars($sc['title']) ?></p>
          <div class="cd-sim-prog-track">
            <div class="cd-sim-prog-fill" style="width:<?= $sPct ?>%;"></div>
          </div>
          <div class="cd-sim-meta">
            <span class="cd-sim-raised"><?= $sc['currency'] ?> <?= number_format($sc['raised_amount']) ?></span>
            <span class="cd-sim-days <?= $sDaysLeft >= 0 && $sDaysLeft <= 5 ? 'cd-sim-urgent' : '' ?>"><?= $sDaysStr ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- ── Sticky mobile donate bar (hidden on desktop via CSS) ── -->
<?php if ($c['status']==='active'): ?>
<div class="cd-mobile-donate-bar">
  <div class="cd-mob-raised">
    <strong><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</strong>
    <span><?= $pct ?>% of goal · <?= htmlspecialchars($daysStr) ?></span>
  </div>
  <a href="#donateWidget" onclick="this.closest('.cd-mobile-donate-bar').style.display='none';">
    <i class="fas fa-heart"></i> Donate Now
  </a>
</div>
<?php endif; ?>

<style>
/* ── Base ──────────────────────────────────────────────────── */
.cd-page { margin-top:64px; background:#f8fafc; min-height:100vh; }

/* ── Hero ──────────────────────────────────────────────────── */
.cd-hero {
  position:relative; min-height:380px;
  background-size:cover; background-position:center;
  background-attachment:scroll; /* safe default — fixed breaks iOS */
  display:flex; align-items:flex-end;
}
/* Parallax only on true desktops where fixed works */
@media(min-width:1024px) and (hover:hover) {
  .cd-hero { background-attachment:fixed; }
}
.cd-hero-overlay {
  position:absolute; inset:0;
  background:linear-gradient(to bottom,
    rgba(0,0,0,.3) 0%,
    rgba(0,0,0,.55) 55%,
    rgba(0,0,0,.82) 100%);
}
.cd-hero-inner { position:relative; z-index:2; width:100%; padding:40px 0 52px; }

/* Breadcrumb */
.cd-breadcrumb {
  display:flex; align-items:center; gap:8px;
  font-size:.76rem; color:rgba(255,255,255,.55);
  margin-bottom:18px;
}
.cd-breadcrumb a { color:rgba(255,255,255,.55); text-decoration:none; }
.cd-breadcrumb a:hover { color:#FF6B4A; }
.cd-breadcrumb span:not(.cd-breadcrumb a) { color:rgba(255,255,255,.4); }

/* Badges */
.cd-hero-badges { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
.cd-cat-badge {
  background:rgba(255,255,255,.15); backdrop-filter:blur(6px);
  color:#fff; font-size:.72rem; font-weight:700;
  padding:4px 12px; border-radius:99px;
  border:1px solid rgba(255,255,255,.2); letter-spacing:.04em; text-transform:uppercase;
}
.cd-status-badge {
  display:inline-flex; align-items:center; gap:5px;
  font-size:.72rem; font-weight:700; padding:4px 12px; border-radius:99px;
  border:1px solid rgba(255,255,255,.2); backdrop-filter:blur(6px);
}
.cd-st-active    { background:rgba(16,185,129,.25); color:#6ee7b7; }
.cd-st-completed { background:rgba(255,255,255,.18); color:#fff; }
.cd-st-draft,.cd-st-paused { background:rgba(255,255,255,.12); color:rgba(255,255,255,.7); }
.cd-st-flagged   { background:rgba(239,68,68,.25); color:#fca5a5; }
.cd-status-dot {
  width:6px; height:6px; border-radius:50%; background:currentColor;
  animation:cd-pulse 1.4s infinite;
}
.cd-urgent-badge {
  background:#FF6B4A; color:#fff; font-size:.72rem; font-weight:700;
  padding:4px 12px; border-radius:99px;
  animation:cd-pulse-red 1.5s infinite;
}

/* Hero title + meta */
.cd-hero-title {
  font-size:clamp(1.4rem,4vw,2.5rem); font-weight:800;
  color:#fff; line-height:1.2; margin-bottom:16px;
  text-shadow:0 2px 16px rgba(0,0,0,.4);
}
.cd-hero-meta {
  display:flex; flex-wrap:wrap; gap:16px;
  font-size:.82rem; color:rgba(255,255,255,.75);
}
.cd-meta-item { display:flex; align-items:center; gap:5px; }
.cd-meta-item i { font-size:.76rem; opacity:.7; }
.cd-meta-item.cd-urgent { color:#fca5a5; font-weight:700; }
.cd-mini-avatar {
  width:22px; height:22px; border-radius:50%; object-fit:cover;
  border:1.5px solid rgba(255,255,255,.4);
}
.cd-mini-init {
  background:rgba(255,255,255,.2); color:#fff;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:.65rem; font-weight:800;
}

/* Hero bar */
.cd-hero-bar { height:5px; background:rgba(255,255,255,.15); }
.cd-hero-bar-fill {
  height:100%; width:0;
  background:linear-gradient(90deg,#FF6B4A,#facc15);
  transition:width 1.4s cubic-bezier(.22,1,.36,1);
  box-shadow:0 0 10px rgba(255,107,74,.6);
}

/* ── Tab bar ───────────────────────────────────────────────── */
.cd-tabbar {
  background:#fff; border-bottom:1px solid #e2e8f0;
  position:sticky; top:64px; z-index:200;
  box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.cd-tabbar-row {
  display:flex; align-items:center;
  justify-content:space-between; gap:8px;
}
.cd-tabs { display:flex; gap:0; }
.cd-tab {
  display:inline-flex; align-items:center; gap:6px;
  padding:14px 18px; font-size:.86rem; font-weight:600;
  color:#64748b; background:none; border:none; cursor:pointer;
  border-bottom:3px solid transparent;
  transition:color .15s, border-color .15s; white-space:nowrap;
}
.cd-tab:hover { color:#0f172a; }
.cd-tab.active { color:#0f172a; border-bottom-color:#FF6B4A; }
.cd-tab-pill {
  background:#f1f5f9; color:#64748b; border-radius:99px;
  font-size:.66rem; font-weight:700; padding:2px 6px;
}
.cd-tab.active .cd-tab-pill { background:#FF6B4A; color:#fff; }
.cd-tab-donate {
  display:inline-flex; align-items:center; gap:6px;
  background:#FF6B4A; color:#fff; border-radius:99px;
  padding:9px 20px; font-size:.86rem; font-weight:700;
  text-decoration:none; transition:all .2s; flex-shrink:0; margin-right:2px;
  box-shadow:0 3px 10px rgba(255,107,74,.3);
}
.cd-tab-donate:hover { background:#e85a3a; transform:translateY(-1px); }

/* ── Body layout ───────────────────────────────────────────── */
.cd-body { padding:32px 0 80px; }
.cd-layout { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }

/* ── Panels ────────────────────────────────────────────────── */
.cd-panel { display:none; }
.cd-panel.active { display:flex; flex-direction:column; }

/* ── Mobile section order ──────────────────────────────────── */
/* Desktop: natural DOM order */
.cd-order-1 { order: 1; }
.cd-order-2 { order: 2; }
.cd-order-3 { order: 3; }
.cd-order-4 { order: 4; }
/* Mobile: image → copy button → stats → story → supporters */
@media (max-width: 1023px) {
  .cd-panel.active { display: flex; flex-direction: column; }
  .cd-photos-section  { order: 0; }
  .cd-order-1         { order: 1; } /* copy link button */
  .cd-order-2         { order: 2; } /* mini progress / raised stats */
  .cd-order-3         { order: 3; } /* story */
  .cd-order-4         { order: 4; } /* recent supporters */
  .cd-stats-section   { order: 5; } /* full progress card */
}

/* ── Campaign photos ───────────────────────────────────────── */
.cd-photos-section {
  background:#fff; border-radius:16px;
  overflow:hidden; margin-bottom:20px;
  box-shadow:0 1px 12px rgba(0,0,0,.07);
}
.cd-photo-main {
  overflow:hidden;
  background:#0f172a;
  /* Responsive: show full image at natural 16:9-ish ratio */
  aspect-ratio:16/10;
  display:flex;
  align-items:center;
  justify-content:center;
}
.cd-photo-main img {
  width:100%;
  height:100%;
  object-fit:contain;   /* show full image — no cropping */
  display:block;
  transition:transform .5s ease;
}
.cd-photo-main:hover img { transform:scale(1.02); }
.cd-photo-thumbs {
  display:flex; gap:4px; padding:6px;
  background:#0f172a; overflow-x:auto; scrollbar-width:none;
}
.cd-photo-thumbs::-webkit-scrollbar { display:none; }
.cd-photo-thumb {
  flex-shrink:0; width:68px; height:48px; border-radius:6px;
  overflow:hidden; border:2.5px solid transparent; padding:0; cursor:pointer;
  transition:border-color .18s;
}
.cd-photo-thumb.active { border-color:#FF6B4A; }
.cd-photo-thumb img { width:100%; height:100%; object-fit:cover; display:block; }

/* ── Progress card ─────────────────────────────────────────── */
.cd-progress-card {
  background:#fff; border-radius:16px; padding:22px;
  margin-bottom:20px; box-shadow:0 1px 12px rgba(0,0,0,.07);
}
.cd-prog-numbers { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:14px; }
.cd-prog-raised  { font-size:1.75rem; font-weight:800; color:#0f172a; }
.cd-prog-sub     { font-size:.76rem; color:#94a3b8; margin-top:2px; }
.cd-prog-pct     { font-size:1.3rem; font-weight:800; color:#FF6B4A; }
.cd-prog-sub.cd-urgent { color:#ef4444; font-weight:700; }
.cd-prog-track { height:9px; background:#f1f5f9; border-radius:99px; overflow:hidden; margin-bottom:16px; }
.cd-prog-fill  {
  height:100%; width:0; border-radius:99px;
  background:linear-gradient(90deg,#0f172a,#FF6B4A);
  transition:width 1.4s cubic-bezier(.22,1,.36,1);
}
.cd-prog-stats {
  display:grid; grid-template-columns:repeat(4,1fr); gap:8px;
}
.cd-prog-stat {
  text-align:center; background:#f8fafc;
  border-radius:10px; padding:10px 6px;
}
.cd-prog-stat-val { display:block; font-size:.88rem; font-weight:800; color:#0f172a; }
.cd-prog-stat-lbl { display:block; font-size:.68rem; color:#94a3b8; margin-top:2px; }

/* ── Sections ──────────────────────────────────────────────── */
.cd-section {
  background:#fff; border-radius:16px; padding:22px;
  margin-bottom:20px; box-shadow:0 1px 12px rgba(0,0,0,.07);
}
.cd-section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.cd-section-h { font-size:.95rem; font-weight:800; color:#0f172a; }
.cd-live-dot {
  display:inline-flex; align-items:center; gap:5px;
  background:#fee2e2; color:#991b1b;
  font-size:.68rem; font-weight:700;
  padding:3px 9px; border-radius:99px;
}
.cd-live-dot span {
  width:6px; height:6px; border-radius:50%;
  background:#ef4444; animation:cd-pulse 1s infinite;
}

/* ── Similar Campaigns ─────────────────────────────────────── */
.cd-similar-section {
  background: #f8fafc;
  padding: 48px 0 56px;
  border-top: 1px solid #e2e8f0;
}
.cd-similar-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 24px;
}
.cd-similar-title {
  font-size: 1.15rem; font-weight: 800; color: #1A2A6C;
  display: flex; align-items: center; gap: 10px;
}
.cd-similar-title i { color: #FF6B4A; }
.cd-similar-more {
  font-size: .82rem; font-weight: 700; color: #FF6B4A;
  text-decoration: none; display: flex; align-items: center; gap: 5px;
}
.cd-similar-more:hover { text-decoration: underline; }
.cd-similar-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.cd-sim-card {
  background: #fff; border-radius: 16px; overflow: hidden;
  text-decoration: none; color: inherit;
  box-shadow: 0 2px 12px rgba(26,42,108,.07);
  transition: transform .2s, box-shadow .2s;
  display: flex; flex-direction: column;
}
.cd-sim-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(26,42,108,.13);
}
.cd-sim-img {
  position: relative; aspect-ratio: 16/9; overflow: hidden; background: #0f172a;
}
.cd-sim-img img {
  width: 100%; height: 100%; object-fit: cover; display: block;
  transition: transform .4s ease;
}
.cd-sim-card:hover .cd-sim-img img { transform: scale(1.04); }
.cd-sim-cat {
  position: absolute; top: 8px; left: 8px;
  background: rgba(0,0,0,.55); color: #fff;
  font-size: .66rem; font-weight: 700; padding: 3px 8px;
  border-radius: 99px; backdrop-filter: blur(4px);
}
.cd-sim-body { padding: 14px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
.cd-sim-title {
  font-weight: 700; color: #1A2A6C; font-size: .84rem; line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.cd-sim-prog-track {
  height: 5px; background: #e2e8f0; border-radius: 99px; overflow: hidden;
}
.cd-sim-prog-fill {
  height: 100%; background: linear-gradient(90deg,#FF6B4A,#f59e0b);
  border-radius: 99px; min-width: 2px;
}
.cd-sim-meta {
  display: flex; justify-content: space-between; align-items: center;
  font-size: .75rem;
}
.cd-sim-raised { font-weight: 700; color: #10b981; }
.cd-sim-days   { color: #9ca3af; }
.cd-sim-urgent { color: #ef4444 !important; font-weight: 600; }
@media (max-width: 900px) {
  .cd-similar-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  .cd-similar-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .cd-sim-body { padding: 10px; }
}
/* ── Copy link button ──────────────────────────────────────── */
.cd-copy-btn { transition: background .2s !important; }
.cd-copy-btn:hover { background: #2a3f8a !important; }

.cd-mini-progress {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 16px 18px;
  margin-bottom: 20px;
}
.cd-mini-prog-row {
  display: flex; justify-content: space-between; align-items: baseline;
  margin-bottom: 8px;
}
.cd-mini-prog-raised {
  font-weight: 800; color: #1A2A6C; font-size: 1rem;
}
.cd-mini-prog-pct {
  font-size: .78rem; color: #6b7280; font-weight: 600;
}
.cd-mini-prog-track {
  height: 8px; background: #e2e8f0; border-radius: 99px;
  overflow: hidden; margin-bottom: 12px;
}
.cd-mini-prog-fill {
  height: 100%; border-radius: 99px;
  background: linear-gradient(90deg, #FF6B4A, #f59e0b);
  transition: width .6s ease;
  min-width: 2px;
}
.cd-mini-prog-stats {
  display: flex; gap: 16px; flex-wrap: wrap;
  font-size: .78rem; color: #6b7280; font-weight: 600;
}
.cd-mini-prog-stats i { margin-right: 4px; color: #9ca3af; }
.cd-urgent-txt { color: #ef4444 !important; }
.cd-urgent-txt i { color: #ef4444 !important; }

/* Story */
.cd-story { font-size:.92rem; color:#334155; line-height:2; margin-bottom:16px; }
.cd-verified {
  display:flex; align-items:flex-start; gap:8px;
  background:#f8fafc; border-radius:10px;
  padding:11px 14px; font-size:.8rem; color:#475569;
  border-left:3px solid #0f172a;
}
.cd-verified i { color:#0f172a; margin-top:1px; flex-shrink:0; }

/* ── Recent Supporters Preview ─────────────────────────────── */
.cd-recent-supporters { margin-top:0; }
.cd-see-all-btn {
  background: none; border: none; cursor: pointer;
  color: #FF6B4A; font-size: .82rem; font-weight: 700;
  display: inline-flex; align-items: center; gap: 5px;
  padding: 4px 0; white-space: nowrap;
}
.cd-see-all-btn:hover { text-decoration: underline; }
.cd-recent-list { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
.cd-recent-row {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 14px; border-radius: 12px;
  background: #f8fafc; border: 1px solid #f1f5f9;
  transition: background .15s;
}
.cd-recent-row:hover { background: #f1f5f9; }
.cd-recent-ava {
  width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 800; font-size: .82rem;
  flex-shrink: 0; letter-spacing: .02em;
}
.cd-recent-body { flex: 1; min-width: 0; }
.cd-recent-name {
  font-weight: 700; color: #1A2A6C; font-size: .88rem;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.cd-recent-meta { font-size: .74rem; color: #94a3b8; margin-top: 1px; }
.cd-recent-amt {
  font-weight: 800; color: #10b981; font-size: .9rem;
  white-space: nowrap; flex-shrink: 0;
}
.cd-see-all-full {
  width: 100%; margin-top: 14px;
  background: #fff; border: 2px solid #e2e8f0; border-radius: 12px;
  padding: 12px; font-size: .86rem; font-weight: 700; color: #1A2A6C;
  cursor: pointer; transition: all .2s;
  display: flex; align-items: center; justify-content: center;
}
.cd-see-all-full:hover { border-color: #FF6B4A; color: #FF6B4A; background: #fff8f6; }

/* Inline donate CTA */
.cd-inline-cta {
  background:#0f172a; border-radius:16px;
  padding:20px 22px; margin-bottom:20px;
  display:flex; align-items:center; justify-content:space-between; gap:16px;
}
.cd-inline-cta-title { font-weight:800; color:#fff; font-size:.95rem; margin-bottom:2px; }
.cd-inline-cta-sub   { font-size:.78rem; color:rgba(255,255,255,.55); }

/* Campaigner */
.cd-campaigner-row { display:flex; align-items:center; gap:12px; }
.cd-camp-ava {
  width:50px; height:50px; border-radius:50%; object-fit:cover;
  border:2px solid #e2e8f0; flex-shrink:0;
}
.cd-camp-ava-init {
  background:#0f172a; color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-size:1.1rem; font-weight:800;
}
.cd-camp-name { font-weight:800; color:#0f172a; font-size:.9rem; }
.cd-camp-sub  { font-size:.76rem; color:#94a3b8; margin-top:2px; }
.cd-camp-sub i { font-size:.68rem; }
.cd-edit-btn {
  margin-left:auto; display:inline-flex; align-items:center; gap:5px;
  border:1.5px solid #e2e8f0; border-radius:99px;
  padding:6px 14px; font-size:.78rem; font-weight:700;
  color:#475569; text-decoration:none; transition:all .15s;
}
.cd-edit-btn:hover { border-color:#0f172a; color:#0f172a; }

/* Share */
.cd-share-block {
  background:#fff; border-radius:16px; padding:18px 22px;
  margin-bottom:20px; box-shadow:0 1px 12px rgba(0,0,0,.07);
}
.cd-share-lbl { font-size:.86rem; font-weight:700; color:#0f172a; margin-bottom:12px; }
.cd-share-lbl i { color:#FF6B4A; margin-right:6px; }
.cd-share-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.cd-share-row a, .cd-share-row button {
  display:flex; align-items:center; justify-content:center; gap:6px;
  padding:10px; border-radius:10px; font-size:.8rem; font-weight:700;
  text-decoration:none; border:none; cursor:pointer; transition:all .18s;
}
.cd-share-row a:hover, .cd-share-row button:hover { transform:translateY(-2px); }
.cd-share-wa   { background:#25D366; color:#fff; }
.cd-share-fb   { background:#1877F2; color:#fff; }
.cd-share-tw   { background:#000; color:#fff; }
.cd-share-copy { background:#f1f5f9; color:#0f172a; }

/* ── Donations ─────────────────────────────────────────────── */
.cd-don-list { display:flex; flex-direction:column; gap:0; }
.cd-don-row {
  display:flex; align-items:flex-start; gap:12px;
  padding:12px 6px; border-radius:12px; transition:background .13s;
}
.cd-don-row:hover { background:#f8fafc; }
.cd-don-ava {
  width:40px; height:40px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-weight:800; font-size:.82rem; color:#fff; letter-spacing:.02em;
}
.cd-don-name { font-weight:700; color:#0f172a; font-size:.88rem; }
.cd-don-amt  { font-weight:800; color:#10b981; font-size:.88rem; white-space:nowrap; }
.cd-don-meta-row { font-size:.74rem; color:#94a3b8; margin-top:2px; }
.cd-more-note { text-align:center; font-size:.76rem; color:#94a3b8; padding:12px 0; }
.cd-empty { text-align:center; padding:32px 0; color:#94a3b8; }  display:flex; align-items:center; justify-content:center;
  font-weight:700; font-size:.85rem; color:#fff;
}
.cd-don-body { flex:1; min-width:0; }
.cd-don-top  { display:flex; justify-content:space-between; align-items:center; margin-bottom:4px; }
.cd-don-name { font-weight:700; color:#0f172a; font-size:.86rem; }
.cd-don-amt  { font-weight:800; color:#FF6B4A; font-size:.88rem; white-space:nowrap; }
.cd-don-bottom { display:flex; align-items:center; gap:10px; }
.cd-don-stars i { font-size:.68rem; }
.cd-star-on  { color:#FF6B4A; }
.cd-star-off { color:#e2e8f0; }
.cd-don-meta { font-size:.7rem; color:#94a3b8; }
.cd-don-meta i { font-size:.62rem; }
.cd-more-note { text-align:center; font-size:.76rem; color:#94a3b8; padding:12px 0; }
.cd-empty { text-align:center; padding:32px 0; color:#94a3b8; }
.cd-empty i { font-size:2.4rem; color:#0f172a; margin-bottom:12px; display:block; }
.cd-empty p  { font-size:.86rem; }
.cd-empty p strong { color:#0f172a; }

/* ── How It Works ──────────────────────────────────────────── */
.cd-how-list { display:flex; flex-direction:column; gap:0; margin-bottom:22px; }
.cd-how-row {
  display:flex; gap:14px; align-items:flex-start;
  padding:14px 0; border-bottom:1px solid #f1f5f9;
}
.cd-how-row:last-child { border-bottom:none; }
.cd-how-num {
  width:34px; height:34px; border-radius:10px; flex-shrink:0;
  background:#0f172a; color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-weight:800; font-size:.88rem;
}
.cd-how-title { font-weight:700; color:#0f172a; font-size:.88rem; margin-bottom:3px; }
.cd-how-desc  { font-size:.8rem; color:#64748b; line-height:1.6; }

/* FAQ */
.cd-faq-list { border-top:1px solid #f1f5f9; padding-top:18px; }
.cd-faq-item { border-bottom:1px solid #f1f5f9; }
.cd-faq-item:last-child { border-bottom:none; }
.cd-faq-q {
  width:100%; display:flex; justify-content:space-between; align-items:center;
  padding:13px 0; font-size:.86rem; font-weight:700; color:#0f172a;
  background:none; border:none; cursor:pointer; text-align:left; transition:color .15s;
}
.cd-faq-q:hover { color:#FF6B4A; }
.cd-faq-q i { transition:transform .25s; font-size:.7rem; flex-shrink:0; }
.cd-faq-item.open .cd-faq-q i { transform:rotate(180deg); }
.cd-faq-a {
  max-height:0; overflow:hidden; font-size:.82rem;
  color:#64748b; line-height:1.7;
  transition:max-height .28s ease, padding .28s ease;
}
.cd-faq-item.open .cd-faq-a { max-height:180px; padding-bottom:13px; }

/* ── Donation widget ───────────────────────────────────────── */
.cd-widget {
  background:#fff; border-radius:18px;
  box-shadow:0 4px 32px rgba(0,0,0,.1); padding:24px;
  position:relative; overflow:hidden; margin-bottom:12px;
}
@media(max-width:767px){
  .cd-widget { padding:18px 14px; border-radius:14px; }
  .cd-widget-title { font-size:.95rem; }
  .cd-input { font-size:16px; } /* prevents iOS zoom on focus */
  .cd-q-btn { padding:10px 4px; font-size:.78rem; }
  .cd-donate-btn-full {
    padding:16px; font-size:1rem;
    /* ensure button is never cut off */
    width:100%; box-sizing:border-box;
  }
}
.cd-widget::before {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
  background:linear-gradient(90deg,#0f172a 40%,#FF6B4A);
}
/* Campaigner widget (right column, below donate) */
.cd-camp-widget { padding:16px 20px; margin-top:0; }
.cd-camp-widget::before { display:none; }
.cd-widget-title { font-weight:800; color:#0f172a; font-size:1rem; margin-bottom:3px; }
.cd-widget-sub   { font-size:.76rem; color:#94a3b8; margin-bottom:18px; }

.cd-quick { display:flex; gap:6px; margin-bottom:14px; }
.cd-q-btn {
  flex:1; padding:8px 4px; border-radius:8px;
  border:1.5px solid #e2e8f0; background:#f8fafc;
  font-size:.76rem; font-weight:700; color:#475569;
  cursor:pointer; transition:all .15s;
}
.cd-q-btn:hover, .cd-q-btn.selected {
  background:#0f172a; color:#fff; border-color:#0f172a;
}

.cd-field { margin-bottom:14px; }
.cd-label { display:block; font-size:.82rem; font-weight:700; color:#334155; margin-bottom:5px; }
.cd-label span { color:#FF6B4A; }
.cd-label em { font-style:normal; font-weight:400; color:#94a3b8; font-size:.72rem; }
.cd-input {
  width:100%; padding:11px 14px;
  border:1.5px solid #e2e8f0; border-radius:10px;
  font-size:.88rem; color:#0f172a; background:#fff;
  outline:none; transition:border-color .15s; font-family:inherit;
}
.cd-input:focus { border-color:#FF6B4A; box-shadow:0 0 0 3px rgba(255,107,74,.1); }
.cd-input-wrap { position:relative; }
.cd-input-pre {
  position:absolute; left:12px; top:50%; transform:translateY(-50%);
  font-size:.78rem; font-weight:700; color:#94a3b8; pointer-events:none;
}
.cd-input-icon {
  position:absolute; left:12px; top:50%; transform:translateY(-50%);
  color:#94a3b8; font-size:.78rem; pointer-events:none;
}
.cd-input-wrap .cd-input { padding-left:44px; }
.cd-fee-hint { font-size:.72rem; color:#94a3b8; margin-top:5px; }
.cd-fee-hint strong { color:#0f172a; }

/* Anonymous toggle */
.cd-anon {
  display:flex; align-items:center; gap:10px; cursor:pointer;
  margin-bottom:16px; padding:10px 13px;
  background:#f8fafc; border-radius:10px;
  font-size:.82rem; color:#475569;
}
.cd-anon input { display:none; }
.cd-toggle-track {
  width:38px; height:21px; border-radius:99px; background:#cbd5e1;
  position:relative; flex-shrink:0; transition:background .2s;
}
.cd-toggle-track::after {
  content:''; position:absolute; width:15px; height:15px;
  background:#fff; border-radius:50%; top:3px; left:3px;
  transition:transform .2s; box-shadow:0 1px 4px rgba(0,0,0,.18);
}
.cd-anon input:checked ~ .cd-toggle-track { background:#FF6B4A; }
.cd-anon input:checked ~ .cd-toggle-track::after { transform:translateX(17px); }

/* Donate button */
.cd-donate-btn {
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  background:linear-gradient(135deg,#FF6B4A,#e85a3a);
  color:#fff; border:none; cursor:pointer;
  padding:13px 24px; border-radius:12px;
  font-size:.94rem; font-weight:800;
  transition:all .22s; box-shadow:0 4px 14px rgba(255,107,74,.35);
  text-decoration:none; font-family:inherit;
}
.cd-donate-btn:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(255,107,74,.45); }
.cd-donate-btn:disabled { opacity:.65; cursor:not-allowed; transform:none; }
.cd-donate-btn-full { width:100%; justify-content:center; margin-bottom:14px; }

.cd-error-box {
  background:#fee2e2; color:#991b1b; border-radius:9px;
  padding:9px 13px; font-size:.82rem; margin-bottom:13px;
  border-left:3px solid #ef4444;
}
.cd-secure {
  display:flex; justify-content:center; gap:14px;
  font-size:.68rem; color:#94a3b8;
}
.cd-secure i { color:#0f172a; margin-right:3px; }

/* WhatsApp nudge */
.cd-wa-nudge {
  display:flex; align-items:center; gap:10px;
  background:#25D366; color:#fff; border-radius:12px;
  padding:12px 16px; text-decoration:none;
  font-size:.82rem; font-weight:700; transition:all .2s;
}
.cd-wa-nudge:hover { transform:translateY(-2px); background:#22c55e; }
.cd-wa-nudge i:first-child { font-size:1.1rem; flex-shrink:0; }
.cd-wa-nudge span { flex:1; }

/* USSD modal */
.cd-ussd-steps {
  background:#f8fafc; border-radius:10px; padding:12px 14px;
  border:1px solid #e2e8f0; margin-bottom:14px;
}
.cd-ussd-n {
  width:20px; height:20px; border-radius:50%; flex-shrink:0;
  background:#0f172a; color:#fff;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:.62rem; font-weight:800;
}
.cd-ussd-table {
  background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;
  overflow:hidden; margin-bottom:14px;
}
.cd-ussd-row {
  display:flex; justify-content:space-between; align-items:center;
  padding:9px 13px; border-bottom:1px solid #f1f5f9; font-size:.82rem;
}
.cd-ussd-row span { color:#64748b; }
.cd-ussd-row strong { color:#0f172a; }

/* Animations */
@keyframes cd-pulse     { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:.4;transform:scale(.7);} }
@keyframes cd-pulse-red { 0%,100%{opacity:1;} 50%{opacity:.65;} }
@keyframes bounce-phone { from{transform:translateY(0);} to{transform:translateY(-8px);} }
@keyframes dots { 0%,80%,100%{opacity:.2;transform:scale(.7);} 40%{opacity:1;transform:scale(1);} }
@keyframes pop  { 0%{transform:scale(0);opacity:0;} 100%{transform:scale(1);opacity:1;} }

/* ── Responsive ────────────────────────────────────────────── */
@media(max-width:1023px){
  /* Grid collapses — left comes first (photos→story→cta), right (widget) second */
  .cd-layout { grid-template-columns:1fr; }
  /* NO order reversal — widget naturally follows the story content */
  .cd-right { order:2; }
  .cd-left  { order:1; }
  /* Disable fixed attachment on tablets/mobile — broken on iOS */
  .cd-hero { background-attachment:scroll; }
  .cd-prog-stats { grid-template-columns:repeat(2,1fr); }
  /* Widget loses sticky on mobile, just flows inline */
  #donateWidget { position:static !important; }
  .cd-widget { border-radius:16px; }
}
@media(max-width:767px){
  .cd-hero { min-height:280px; }
  .cd-hero-title { font-size:1.3rem; }
  .cd-hero-meta { gap:10px; font-size:.78rem; }
  .cd-tab  { padding:11px 10px; font-size:.76rem; }
  .cd-tab i { display:none; }  /* hide icons on small screens, keep text */
  .cd-share-row { grid-template-columns:1fr 1fr; }
  .cd-prog-stats { grid-template-columns:repeat(2,1fr); }
  .cd-quick { flex-wrap:wrap; }
  .cd-q-btn { min-width:calc(50% - 4px); flex:none; }
  /* Photos — taller aspect on portrait mobile screens */
  .cd-photo-main { aspect-ratio:4/3; }
  /* Compact body padding — leave room for sticky donate bar */
  .cd-body { padding:20px 0 100px; }
  /* Make inline CTA stack on mobile */
  .cd-inline-cta { flex-direction:column; align-items:flex-start; gap:12px; }
  .cd-donate-btn-full { width:100%; }

  /* ── Story BEFORE stats on mobile ── */
  .cd-story-section { order:1; }
  .cd-stats-section  { order:2; }
  /* cd-photos-section has no order override — stays first naturally */

  /* ── Sticky bottom donate bar on mobile ── */
  .cd-mobile-donate-bar {
    display:flex !important;
    position:fixed; bottom:0; left:0; right:0; z-index:500;
    background:#fff;
    border-top:1px solid #e2e8f0;
    padding:12px 16px;
    gap:10px;
    align-items:center;
    box-shadow:0 -4px 20px rgba(0,0,0,.1);
  }
  .cd-mobile-donate-bar .cd-mob-raised {
    flex:1; min-width:0;
  }
  .cd-mobile-donate-bar .cd-mob-raised strong {
    display:block; font-size:.9rem; font-weight:800; color:#0f172a;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .cd-mobile-donate-bar .cd-mob-raised span {
    font-size:.72rem; color:#94a3b8;
  }
  .cd-mobile-donate-bar a {
    flex-shrink:0;
    display:inline-flex; align-items:center; gap:7px;
    background:linear-gradient(135deg,#FF6B4A,#e85a3a);
    color:#fff; border-radius:12px;
    padding:13px 22px; font-size:.92rem; font-weight:800;
    text-decoration:none;
    box-shadow:0 4px 14px rgba(255,107,74,.4);
    white-space:nowrap;
  }
}

/* Mobile donate bar hidden on desktop */
.cd-mobile-donate-bar { display:none; }
</style>

<script>
// ── Hero progress bar animate ──────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.cd-prog-fill[data-w]').forEach(function(el) {
    setTimeout(function(){ el.style.width = el.dataset.w + '%'; }, 300);
  });
  document.querySelectorAll('.cd-hero-bar-fill').forEach(function(el){
    var w = el.style.width; el.style.width = '0';
    setTimeout(function(){ el.style.width = w; }, 200);
  });

  // ── NEW: Auto‑show modal on return from Pesapal ────────────
  const urlParams = new URLSearchParams(window.location.search);
  const paymentStatus = urlParams.get('payment');
  const donationId = urlParams.get('donation_id'); // optional, if passed

  // Clean the URL (remove payment & donation_id params) to avoid re‑trigger on refresh
  if (paymentStatus || donationId) {
    let cleanUrl = window.location.pathname + '?id=' + '<?= $cid ?>';
    if (window.history && window.history.replaceState) {
      window.history.replaceState({}, document.title, cleanUrl);
    }
  }

  if (paymentStatus === 'success') {
    // Show the success modal
    const modal = document.getElementById('donationModal');
    if (modal) {
      // You could optionally set the modal amount here if you stored it in session/URL
      // For now, we just show the generic "Thank you" – the IPN will update the DB.
      modal.classList.add('open');
    }
  } else if (paymentStatus === 'cancelled') {
    alert('Payment was cancelled. You can try donating again anytime.');
  }
});

// ── Photo gallery switcher ─────────────────────────────────
function switchPhoto(thumb, url) {
  document.getElementById('cdPhotoMainImg').src = url;
  document.querySelectorAll('.cd-photo-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

// ── Tab switching ──────────────────────────────────────────
document.querySelectorAll('.cd-tab').forEach(function(tab) {
  tab.addEventListener('click', function() {
    var target = this.dataset.tab;
    document.querySelectorAll('.cd-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.cd-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    var panel = document.getElementById('panel-' + target);
    if (panel) { panel.classList.add('active'); window.scrollTo({top:0,behavior:'smooth'}); }
  });
});

// ── switchTab — called by "See all" buttons in story panel ──
function switchTab(tabName) {
  document.querySelectorAll('.cd-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.cd-panel').forEach(p => p.classList.remove('active'));
  var btn = document.querySelector('.cd-tab[data-tab="' + tabName + '"]');
  if (btn) btn.classList.add('active');
  var panel = document.getElementById('panel-' + tabName);
  if (panel) { panel.classList.add('active'); }
  // Scroll to tabbar
  var tabbar = document.getElementById('cdTabbar');
  if (tabbar) tabbar.scrollIntoView({behavior:'smooth', block:'start'});
}

// ── FAQ toggle ─────────────────────────────────────────────
function toggleFaq(btn) {
  var item = btn.closest('.cd-faq-item');
  var wasOpen = item.classList.contains('open');
  document.querySelectorAll('.cd-faq-item.open').forEach(i => i.classList.remove('open'));
  if (!wasOpen) item.classList.add('open');
}

// ── Quick amount pills ─────────────────────────────────────
document.querySelectorAll('.cd-q-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var amt = parseInt(this.dataset.amount);
    document.getElementById('contributionAmount').value = amt;
    document.querySelectorAll('.cd-q-btn').forEach(b => b.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('donateBtnAmt').textContent = amt.toLocaleString();
  });
});

document.getElementById('contributionAmount')?.addEventListener('input', function(){
  var amt = parseFloat(this.value) || 0;
  document.getElementById('donateBtnAmt').textContent = amt.toLocaleString();
});

// ── Copy link — copies rich share text ────────────────────────
function copyLink(btn) {
  var shareText = <?= json_encode($shareText) ?>;
  navigator.clipboard.writeText(shareText).then(function() {
    var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.style.background = '#d1fae5'; btn.style.color = '#065f46';
    setTimeout(function(){ btn.innerHTML = orig; btn.style.background=''; btn.style.color=''; }, 2200);
    window.showToast && window.showToast('Share text copied!', 'success');
  });
}

// ── Track share count — updates all counters in real time ──
var _shareCount = <?= (int)$c['share_count'] ?>;

function trackShare() {
  fetch('<?= BASE ?>/api/campaigns.php?action=track_share', {
    method: 'POST',
    body: (() => { var fd = new FormData(); fd.append('campaign_id', '<?= $cid ?>'); return fd; })()
  })
  .then(function(res) { return res.json(); })
  .then(function(data) {
    if (data.success) {
      _shareCount++;
      var fmt = _shareCount.toLocaleString();
      // Update all share count displays
      var ids = ['shareCountMini', 'shareCountCard', 'shareCountHero'];
      ids.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
          el.textContent = fmt;
          // Brief pulse animation
          el.style.transition = 'color .3s';
          el.style.color = '#FF6B4A';
          setTimeout(function() { el.style.color = ''; }, 1000);
        }
      });
    }
  })
  .catch(function() {});
}

// ── Donate button → Pesapal Checkout ──────────────────────────
document.getElementById('donateBtn')?.addEventListener('click', async function() {
  var errDiv  = document.getElementById('donationError');
  errDiv.style.display = 'none';

  var amount  = document.getElementById('contributionAmount').value;
  var name    = document.getElementById('donorName').value.trim();
  var phone   = document.getElementById('donorPhone').value.trim();
  var network = document.getElementById('momoNetwork').value;
  var anon    = document.getElementById('anonymousToggle').checked;
  var email   = document.getElementById('donorEmail').value.trim();

  // ── Client-side validation ─────────────────────────────────
  if (!amount || Number(amount) < 1000) {
    errDiv.textContent = 'Minimum is <?= $c['currency'] ?> 1,000.';
    errDiv.style.display = 'block'; return;
  }
  if (phone.length < 9) {
    errDiv.textContent = 'Enter a valid phone number.';
    errDiv.style.display = 'block'; return;
  }
  if (!anon && !name) {
    errDiv.textContent = 'Enter your name or choose "Remain anonymous".';
    errDiv.style.display = 'block'; return;
  }

  // ── Show loading state ─────────────────────────────────────
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting to Pesapal…';

  var fd = new FormData();
  fd.append('action',                'submit');
  fd.append('campaign_id',           '<?= $cid ?>');
  fd.append('amount',                amount);
  fd.append('donor_name',            name);
  fd.append('donor_email',           email);
  fd.append('donor_phone',           phone);
  fd.append('mobile_money_network',  network);
  fd.append('is_anonymous',          anon ? '1' : '');

  try {
    var res  = await fetch('<?= BASE ?>/api/donations.php?action=submit', {method:'POST', body:fd});
    var text = await res.text();
    var data = {};
    try { data = JSON.parse(text); } catch (e) { data = { message: text }; }

    if (res.ok && data.success && data.redirect_url) {
      window.location.href = data.redirect_url;
    } else {
      errDiv.textContent   = data.message || 'Payment initiation failed. Please try again.';
      errDiv.style.display = 'block';
      btn.disabled  = false;
      btn.innerHTML = '<i class="fas fa-heart"></i> Donate <?= $c['currency'] ?> <span id="donateBtnAmt">'
                      + Number(amount).toLocaleString() + '</span>';
    }
  } catch (ex) {
    errDiv.textContent   = 'Network error. Please check your connection and try again.';
    errDiv.style.display = 'block';
    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-heart"></i> Donate <?= $c['currency'] ?> <span id="donateBtnAmt">'
                    + Number(amount).toLocaleString() + '</span>';
  }
});

// ── Modal close ────────────────────────────────────────────
document.querySelectorAll('[data-close-modal]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById(this.dataset.closeModal)?.classList.remove('open');
  });
});
document.querySelectorAll('.modal-overlay').forEach(function(ol) {
  ol.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});
</script>
