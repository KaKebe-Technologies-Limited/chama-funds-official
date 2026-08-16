<?php
// ============================================================
// ChamaFunds – index.php  (Home page)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'ChamaFunds – Pool Money Together for What Matters';
$pageDescription = 'Uganda\'s mobile money crowdfunding platform. Launch a campaign in 60 seconds, receive funds via MTN & Airtel Money. Free to start — fast payout.';

// ── OG / Social meta ─────────────────────────────────────────
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protocol = trim($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : $protocol;
}
$siteUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'];  // auto — no hardcoded domain
$ogImage  = BASE . '/img/logo.png';
$ogTitle  = 'ChamaFunds – Pool Money Together for What Matters';
$ogDesc   = 'No spreadsheets, no chasing — just impact: ChamaFunds makes it easy to pool money for causes that matter. No spreadsheets, no chasing — just impact. Live tracking, automated reminders, mobile money donations.';

$extraCss = <<<HTML
  <!-- ══ Open Graph ══ -->
  <meta property="og:type"             content="website"/>
  <meta property="og:url"              content="{$siteUrl}/"/>
  <meta property="og:site_name"        content="ChamaFunds"/>
  <meta property="og:title"            content="{$ogTitle}"/>
  <meta property="og:description"      content="{$ogDesc}"/>
  <meta property="og:image"            content="{$ogImage}"/>
  <meta property="og:image:secure_url" content="{$ogImage}"/>
  <meta property="og:image:width"      content="1200"/>
  <meta property="og:image:height"     content="630"/>
  <meta property="og:image:alt"        content="ChamaFunds – Mobile Money Crowdfunding"/>
  <meta property="og:locale"           content="en_UG"/>
  <!-- ══ Twitter / X ══ -->
  <meta name="twitter:card"            content="summary_large_image"/>
  <meta name="twitter:title"           content="{$ogTitle}"/>
  <meta name="twitter:description"     content="{$ogDesc}"/>
  <meta name="twitter:image"           content="{$ogImage}"/>
  <meta name="twitter:image:alt"       content="ChamaFunds – Mobile Money Crowdfunding"/>
  <!-- ══ Extra SEO ══ -->
  <meta name="keywords"                content="crowdfunding Uganda, mobile money fundraising, MTN mobile money, chama funds, campaign donations Africa"/>
  <meta name="robots"                  content="index, follow"/>
  <link rel="canonical"                href="{$siteUrl}/"/>
HTML;

// DB connection status for popup
$dbConnected = ($conn && !$conn->connect_error);

// Hero photo slider — managed from the admin dashboard
// Gracefully handle missing table (table may not exist on fresh installs)
$heroSlides = [];
// Temporarily suppress strict SQL exceptions for this optional query
$prevReport = mysqli_report(MYSQLI_REPORT_OFF);
$heroSlidesResult = $conn->query(
    "SELECT image_url, alt_text FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, slide_id ASC"
);
mysqli_report($prevReport);
if ($heroSlidesResult) {
    while ($hs = $heroSlidesResult->fetch_assoc()) $heroSlides[] = $hs;
}

// Campaigns an admin has marked as featured also join the hero photo orbit,
// linked through to their campaign page.
$prevReport = mysqli_report(MYSQLI_REPORT_OFF);
$featuredHeroResult = $conn->query(
    "SELECT campaign_id, title, image_url FROM campaigns
     WHERE status = 'active' AND is_featured = 1
     ORDER BY created_at DESC LIMIT 6"
);
mysqli_report($prevReport);
if ($featuredHeroResult) {
    while ($fh = $featuredHeroResult->fetch_assoc()) {
        $fhGallery = campaignCardGallery($conn, $fh['campaign_id'], 1);
        $fhImage   = !empty($fhGallery) ? $fhGallery[0] : imgUrl($fh['image_url'] ?: '');
        if ($fhImage) {
            $heroSlides[] = [
                'image_url' => $fhImage,
                'alt_text'  => $fh['title'],
                'href'      => BASE . '/campaign-detail.php?id=' . $fh['campaign_id'],
            ];
        }
    }
}

// Site-wide donor stat + a few campaigner initials for the hero's avatar
// stack (same approach as campaign-drives.php's hero).
$totalSupporters = (int)($conn->query(
    "SELECT COALESCE(SUM(contributor_count),0) FROM campaigns WHERE status = 'active'"
)->fetch_row()[0]);
$avatarSeed = [];
$avatarSeedResult = $conn->query(
    "SELECT DISTINCT u.full_name FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status = 'active' ORDER BY c.created_at DESC LIMIT 4"
);
if ($avatarSeedResult) {
    while ($as = $avatarSeedResult->fetch_assoc()) {
        $n = trim($as['full_name']);
        if ($n) $avatarSeed[] = $n;
    }
}
$avatarColours = ['#FF6B4A','#1A2A6C','#10b981','#f59e0b'];

// Distribute the hero photos across 3 flowing columns for the collage,
// cycling through whatever images are available.
function cdCollageColumn($images, $offset, $count = 4) {
    $out = []; $n = count($images);
    if ($n === 0) return $out;
    for ($i = 0; $i < $count; $i++) $out[] = $images[($offset + $i) % $n];
    return $out;
}
$heroImages = array_map(function ($s) { return imgUrl($s['image_url']); }, $heroSlides);
$col1 = cdCollageColumn($heroImages, 0);
$col2 = cdCollageColumn($heroImages, 2);
$col3 = cdCollageColumn($heroImages, 4);

// Fetch top 5 active campaigns for home grid — featured campaigns first,
// then most recent (1 featured/spotlight + up to 4 more — bento layout
// kicks in at 3+).
$featured = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status = 'active'
     ORDER BY c.is_featured DESC, c.created_at DESC
     LIMIT 5"
);
$featuredArr = [];
while ($fc = $featured->fetch_assoc()) $featuredArr[] = $fc;

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════ HERO ═══════════════════════════ -->
<section class="cdh-hero">
  <div class="container">
    <div class="cdh-grid">
      <div class="cdh-copy">
        <div class="cdh-badge"><i class="fas fa-bolt"></i> Built for African Causes</div>
        <h1 class="cdh-title"><span class="cdh-bordered">Pool Money</span> Together for <span class="cdh-accent">What Matters Most</span></h1>
        <p class="cdh-sub">Launch a campaign or donate to causes you care about. Transparent, secure, and built for mobile money.</p>
        <div class="cdh-cta-row">
          <div class="cdh-btn-row">
            <a href="<?= BASE ?>/donate.php" class="btn btn-primary">Donate</a>
            <a href="<?= BASE ?>/create-campaign.php" class="btn btn-outline">Start a Campaign</a>
          </div>
          <div class="cdh-social-proof">
            <div class="cdh-avatars">
              <?php foreach ($avatarSeed as $i => $n): ?>
              <span class="cdh-avatar" style="background:<?= $avatarColours[$i % 4] ?>;"><?= strtoupper(substr($n, 0, 1)) ?></span>
              <?php endforeach; ?>
            </div>
            <div class="cdh-stat"><strong><?= number_format($totalSupporters) ?>+</strong><span>Donors</span></div>
          </div>
        </div>
      </div>

      <div class="cdh-collage" aria-hidden="true">
        <div class="cdh-col cdh-col-up">
          <?php foreach (array_merge($col1, $col1) as $src): ?>
          <img src="<?= htmlspecialchars($src) ?>" alt="" loading="lazy" />
          <?php endforeach; ?>
        </div>
        <div class="cdh-col cdh-col-down">
          <?php foreach (array_merge($col2, $col2) as $src): ?>
          <img src="<?= htmlspecialchars($src) ?>" alt="" loading="lazy" />
          <?php endforeach; ?>
        </div>
        <div class="cdh-col cdh-col-up">
          <?php foreach (array_merge($col3, $col3) as $src): ?>
          <img src="<?= htmlspecialchars($src) ?>" alt="" loading="lazy" />
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.cdh-hero { background:#fff; padding:96px 0 0; overflow:hidden; }
.cdh-grid { display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:center; }
.cdh-badge {
  display:inline-flex; align-items:center; gap:8px;
  background:#fff7ed; color:#c2410c; border:1px solid #fed7aa;
  padding:6px 16px; border-radius:99px; font-size:.8rem; font-weight:700;
  margin-bottom:20px;
}
.cdh-title { font-size:clamp(1.6rem,3vw,2.3rem); font-weight:800; line-height:1.16; color:#0f172a; margin-bottom:18px; }
.cdh-accent { color:#FF6B4A; }
.cdh-bordered { border:3px solid #FF6B4A; border-radius:12px; padding:2px 10px; display:inline-block; }
.cdh-sub { color:#64748b; font-size:1rem; line-height:1.7; max-width:440px; margin:0 auto 32px; }
.cdh-copy { text-align:center; }
.cdh-cta-row { display:flex; align-items:center; justify-content:center; gap:24px; flex-wrap:wrap; }
.cdh-btn-row { display:flex; align-items:center; gap:12px; }
.cdh-social-proof { display:flex; align-items:center; gap:12px; }
.cdh-avatars { display:flex; }
.cdh-avatar {
  width:38px; height:38px; border-radius:50%; border:3px solid #fff;
  display:flex; align-items:center; justify-content:center;
  color:#fff; font-weight:700; font-size:.85rem;
  margin-left:-12px; box-shadow:0 2px 6px rgba(0,0,0,.12);
}
.cdh-avatars .cdh-avatar:first-child { margin-left:0; }
.cdh-stat { font-size:.82rem; color:#64748b; line-height:1.3; }
.cdh-stat strong { display:block; color:#1A2A6C; font-size:1.05rem; font-weight:800; }

/* ── Flowing photo collage ──────────────────────────────────── */
.cdh-collage {
  display:grid; grid-template-columns:repeat(3,1fr); gap:14px;
  height:560px; overflow:hidden;
  -webkit-mask-image:linear-gradient(to bottom, transparent, #000 8%, #000 92%, transparent);
  mask-image:linear-gradient(to bottom, transparent, #000 8%, #000 92%, transparent);
}
.cdh-col { display:flex; flex-direction:column; gap:14px; will-change:transform; }
.cdh-col img { width:100%; aspect-ratio:3/4; object-fit:cover; border-radius:18px; display:block; }
.cdh-col-up   { animation:cdhScrollUp 26s linear infinite; }
.cdh-col-down { animation:cdhScrollDown 30s linear infinite; }
.cdh-col:nth-child(2) { margin-top:-48px; }
@keyframes cdhScrollUp   { from { transform:translateY(0); }    to { transform:translateY(-50%); } }
@keyframes cdhScrollDown { from { transform:translateY(-50%); } to { transform:translateY(0); } }
@media (prefers-reduced-motion: reduce) { .cdh-col { animation:none; } }

@media (max-width:1023px) {
  .cdh-grid { grid-template-columns:1fr; gap:28px; }
  /* Simpler 2-column flow on mobile/tablet — shorter, lighter, still "alive" */
  .cdh-collage { grid-template-columns:repeat(2,1fr); height:280px; gap:10px; }
  .cdh-collage .cdh-col:nth-child(3) { display:none; }
  .cdh-col:nth-child(2) { margin-top:-32px; }
}
@media (max-width:640px) {
  /* Full-bleed mobile hero: the flowing photo collage becomes the whole
     background, with the headline and the CTA + social proof both
     centered vertically in the middle. */
  .cdh-hero { padding:0; }
  .cdh-grid {
    position:relative; display:block; min-height:100vh;
    grid-template-columns:1fr; gap:0;
  }
  .cdh-grid::before {
    content:''; position:absolute; inset:0; z-index:1; pointer-events:none;
    background:linear-gradient(180deg, rgba(15,23,42,.62) 0%, rgba(26,42,108,.68) 55%, rgba(15,23,42,.9) 100%);
  }
  .cdh-collage {
    position:absolute; inset:0; z-index:0;
    width:100%; height:100%; opacity:.48;
    grid-template-columns:repeat(3,1fr);
  }
  .cdh-collage .cdh-col:nth-child(3) { display:flex; }
  .cdh-badge { display:none; }
  .cdh-copy {
    position:relative; z-index:2; min-height:100vh;
    display:flex; flex-direction:column; justify-content:center; align-items:center;
    gap:36px; padding:110px 22px 36px;
  }
  .cdh-title { color:#fff; font-size:1.7rem; margin-bottom:0; text-shadow:0 2px 14px rgba(0,0,0,.4); }
  .cdh-sub { display:none; } /* keep mobile lean — headline + CTA say enough */
  .cdh-cta-row { flex-direction:column; align-items:center; gap:16px; }
  .cdh-btn-row { flex-wrap:nowrap; } /* Donate + Start a Campaign stay on one line */
  .cdh-social-proof { margin-left:0; }
  .cdh-stat { color:rgba(255,255,255,.85); }
  .cdh-stat strong { color:#fff; }
  .cdh-avatar { border-color:rgba(255,255,255,.85); }
}
</style>

<!-- HOW IT WORKS -->
<section class="section" id="how-it-works" style="background:#f9fafb;">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">Simple Process</span>
      <h2 class="section-title">Three Simple Steps. Ninety Seconds.</h2>
      <p class="section-sub">Create, share, and receive — all in under two minutes.</p>
    </div>
    <div class="steps-accordion">
      <div class="step-acc-item open">
        <button type="button" class="step-acc-header">
          <span class="step-acc-num">1</span>
          <span class="step-acc-title">Create a Campaign</span>
          <span class="step-acc-icon">+</span>
        </button>
        <div class="step-acc-body">
          <div class="step-acc-body-inner">
            <p>Set up your campaign in 60 seconds. Add details, set your goal, and get a shareable link instantly.</p>
            <p style="margin-top:10px;font-size:.78rem;font-weight:700;color:#FF6B4A;">FREE to start</p>
          </div>
        </div>
      </div>
      <div class="step-acc-item">
        <button type="button" class="step-acc-header">
          <span class="step-acc-num">2</span>
          <span class="step-acc-title">Share with Your People</span>
          <span class="step-acc-icon">+</span>
        </button>
        <div class="step-acc-body">
          <div class="step-acc-body-inner">
            <p>Post your link on WhatsApp, social media, or anywhere. No account needed to contribute.</p>
          </div>
        </div>
      </div>
      <div class="step-acc-item">
        <button type="button" class="step-acc-header">
          <span class="step-acc-num">3</span>
          <span class="step-acc-title">Grow &amp; Receive Funds</span>
          <span class="step-acc-icon">+</span>
        </button>
        <div class="step-acc-body">
          <div class="step-acc-body-inner">
            <p>Watch contributions come in live and withdraw funds to mobile money — processed within 48 hours.</p>
            <p style="margin-top:10px;font-size:.78rem;font-weight:700;color:#10b981;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Payout within 48hrs</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- LIVE CAMPAIGNS -->
<?php
  function campaignCalc($conn, $c) {
    $daysLeft = (int)$c['days_left'];
    $gallery  = campaignCardGallery($conn, $c['campaign_id']);
    return [
      'pct'         => min(100, (float)$c['pct']),
      'daysLeft'    => $daysLeft,
      'daysStr'     => $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended'),
      'catClass'    => 'badge-' . strtolower($c['category']),
      'image'       => !empty($gallery) ? $gallery[0] : imgUrl($c['image_url'] ?: ''),
      'gallery'     => $gallery,
      'isFeatured'  => !empty($c['is_featured']),
    ];
  }
  $featuredCount = count($featuredArr);
?>
<section class="section" style="background:#fff;">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">Live Now</span>
      <h2 class="section-title">Active Campaign Drives</h2>
      <p class="section-sub">Real campaigns, real people, real impact.</p>
    </div>

    <?php if ($featuredCount === 0): ?>
      <div style="text-align:center;padding:60px 0;color:#9ca3af;">
        <i class="fas fa-rocket" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
        No active campaigns yet. <a href="<?= BASE ?>/create-campaign.php" style="color:#FF6B4A;font-weight:700;">Be the first!</a>
      </div>

    <?php elseif ($featuredCount >= 3): ?>
      <!-- Bento layout: one featured campaign + a grid of smaller ones -->
      <?php
        $largeCard  = $featuredArr[0];
        $smallCards = array_slice($featuredArr, 1, 4);
        $lg = campaignCalc($conn, $largeCard);
      ?>
      <div class="bento-grid">
        <a href="<?= BASE ?>/campaign-detail.php?id=<?= $largeCard['campaign_id'] ?>" class="bento-large">
          <div class="bento-large-img-wrap">
            <?php if (!empty($lg['gallery'])): ?>
              <?= renderSliderImages($lg['gallery'], $largeCard['title']) ?>
            <?php else: ?>
              <img src="<?= htmlspecialchars($lg['image']) ?>" alt="<?= htmlspecialchars($largeCard['title']) ?>" loading="lazy" />
            <?php endif; ?>
            <?php if ($lg['isFeatured']): ?><span class="featured-badge"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
            <span class="bento-stat-pill"><i class="fas fa-users" style="margin-right:5px;"></i><?= $largeCard['contributor_count'] ?> supporters</span>
          </div>
          <div class="bento-info">
            <div class="campaign-meta">
              <span class="category-badge <?= $lg['catClass'] ?>"><?= htmlspecialchars($largeCard['category']) ?></span>
              <span class="days-left" <?= $lg['daysLeft'] <= 3 ? 'style="color:#ef4444;"' : '' ?>><?= htmlspecialchars($lg['daysStr']) ?></span>
            </div>
            <p class="bento-large-title"><?= htmlspecialchars($largeCard['title']) ?></p>
            <div class="progress-wrap"><div class="progress-fill" data-width="<?= $lg['pct'] ?>%"></div></div>
            <div class="campaign-stats">
              <span><?= $largeCard['currency'] ?> <?= number_format($largeCard['raised_amount']) ?> raised</span>
              <span style="font-weight:700;color:#1A2A6C;"><?= $lg['pct'] ?>%</span>
            </div>
          </div>
        </a>

        <div class="bento-small-grid">
          <?php foreach ($smallCards as $c): $sm = campaignCalc($conn, $c); ?>
          <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" class="bento-small">
            <div class="bento-small-img-wrap">
              <?php if (!empty($sm['gallery'])): ?>
                <?= renderSliderImages($sm['gallery'], $c['title']) ?>
              <?php else: ?>
                <img src="<?= htmlspecialchars($sm['image']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
              <?php endif; ?>
              <?php if ($sm['isFeatured']): ?><span class="featured-badge"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
              <span class="bento-stat-pill"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?></span>
            </div>
            <div class="bento-info">
              <p class="bento-small-title"><?= htmlspecialchars($c['title']) ?></p>
              <div class="progress-wrap"><div class="progress-fill" data-width="<?= $sm['pct'] ?>%"></div></div>
              <span class="bento-small-amount"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

    <?php else: ?>
      <!-- Fewer than 3 campaigns — simple grid -->
      <div class="home-campaigns-grid">
        <?php foreach ($featuredArr as $c): $cc = campaignCalc($conn, $c); ?>
        <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" class="card campaign-card" style="text-decoration:none;color:inherit;position:relative;">
          <?php if (!empty($cc['gallery'])): ?>
            <?= renderCardImageSlider($cc['gallery'], $c['title']) ?>
          <?php else: ?>
            <img class="card-img" src="<?= htmlspecialchars($cc['image']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
          <?php endif; ?>
          <?php if ($cc['isFeatured']): ?><span class="featured-badge"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
          <div class="card-body">
            <div class="campaign-meta">
              <span class="category-badge <?= $cc['catClass'] ?>"><?= htmlspecialchars($c['category']) ?></span>
              <span class="days-left" <?= $cc['daysLeft'] <= 3 ? 'style="color:#ef4444;"' : '' ?>><?= htmlspecialchars($cc['daysStr']) ?></span>
            </div>
            <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
            <div class="campaign-stats">
              <span><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</span>
              <span style="font-weight:700;color:#1A2A6C;"><?= $cc['pct'] ?>%</span>
            </div>
            <div class="progress-wrap"><div class="progress-fill" data-width="<?= $cc['pct'] ?>%"></div></div>
            <div class="campaign-footer">
              <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?></span>
              <span class="btn btn-primary btn-sm"><i class="fas fa-heart" style="margin-right:5px;"></i>Donate</span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:36px;">
      <a href="<?= BASE ?>/campaign-drives.php" class="btn btn-outline">More Campaigns <i class="fas fa-arrow-right" style="margin-left:6px;"></i></a>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq" style="background:#fff;">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">FAQ</span>
      <h2 class="section-title">Questions, Answered.</h2>
    </div>
    <div>
      <div class="faq-item">
        <button class="faq-question">What makes ChamaFunds different? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">ChamaFunds is built specifically for African mobile money ecosystems, working natively with MTN, Airtel and more.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">How do I know my contribution reaches the right person? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">Every contribution is logged on a live public ledger. Funds are disbursed directly to the campaign creator's verified mobile money number.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">What fees does ChamaFunds charge? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">We charge a 7.5% platform transaction fee per contribution at withdrawal. Creating a campaign is always free.</div></div>
      </div>
      <div class="faq-item">
        <button class="faq-question">How long does it take to receive funds? <span class="faq-icon">+</span></button>
        <div class="faq-answer"><div class="faq-answer-inner">Withdrawals are reviewed and processed within 48 hours during business hours (8am–6pm local time). Funds are sent directly to your mobile money account after approval.</div></div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// ── DB Connection popup on page load ─────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  var dbOk = <?= $dbConnected ? 'true' : 'false' ?>;
  var shown = sessionStorage.getItem('cf_db_ping_shown');
  if (!shown) {
    sessionStorage.setItem('cf_db_ping_shown', '1');
    if (dbOk) {
      // window.showToast('✅ Database connected successfully!', 'success');
    } else {
      window.showToast('❌ Database connections failed!', 'error');
    }
  }
});
</script>
