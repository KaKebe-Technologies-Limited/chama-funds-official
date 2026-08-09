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
  <!-- ══ Swiper (hero photo carousel) ══ -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@14.0.1/swiper-bundle.min.css"/>
HTML;

// DB connection status for popup
$dbConnected = ($conn && !$conn->connect_error);

// Hero photo slider — managed from the admin dashboard
$heroSlidesResult = $conn->query(
    "SELECT image_url, alt_text FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, slide_id ASC"
);
$heroSlides = [];
while ($hs = $heroSlidesResult->fetch_assoc()) $heroSlides[] = $hs;

// Fetch top 5 most recent active campaigns for home grid
// (1 featured + up to 4 more — bento layout kicks in at 3+)
$featured = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status = 'active'
     ORDER BY c.created_at DESC
     LIMIT 5"
);
$featuredArr = [];
while ($fc = $featured->fetch_assoc()) $featuredArr[] = $fc;

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════ HERO ═══════════════════════════ -->
<section class="hero-light hero-section" style="overflow:hidden;margin-top:64px;">
  <div class="container" style="position:relative;z-index:2;">
    <div class="hero-inner">
      <div class="hero-copy">
        <div class="hero-badge"><i class="fas fa-bolt" style="color:#f59e0b"></i> Built for African Causes</div>
        <h1 style="font-size:clamp(1.6rem,4.2vw,2.9rem);font-weight:800;line-height:1.15;color:#1A2A6C;margin-bottom:16px;">
          Pool Money Together for<br>
          <span style="color:#FF6B4A;">What Matters Most</span>
        </h1>
        <div class="hero-cta-row" style="margin-top:14px;">
          <a href="<?= BASE ?>/donate.php" class="btn btn-outline btn-sm">Donate</a>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm">Start a Campaign</a>
        </div>
      </div>
    </div>

    <!-- Coverflow photo showcase — managed from the admin dashboard -->
    <?php if (!empty($heroSlides)): ?>
    <div class="swiper hero-swiper">
      <div class="swiper-wrapper">
        <?php foreach ($heroSlides as $i => $slide): ?>
        <div class="swiper-slide">
          <img src="<?= htmlspecialchars(imgUrl($slide['image_url'])) ?>"
               alt="<?= htmlspecialchars($slide['alt_text']) ?>"
               <?= $i === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> />
        </div>
        <?php endforeach; ?>
      </div>
      <div class="swiper-pagination"></div>
    </div>
    <?php endif; ?>

    <!-- <div class="hero-trust-row">
      <span><i class="fas fa-check-circle" style="color:#10b981;margin-right:6px;"></i>Free to start</span>
      <span><i class="fas fa-check-circle" style="color:#10b981;margin-right:6px;"></i>Payout within 48hrs</span>
      <span><i class="fas fa-check-circle" style="color:#10b981;margin-right:6px;"></i>Live tracking</span>
    </div> -->
  </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
  <div class="container">
    <p style="text-align:center;color:#6b7280;font-size:.88rem;margin-bottom:16px;">
      Launch a campaign or donate to causes you care about. Transparent, secure, and built for mobile money.
    </p>
    <div class="trust-bar-inner">
      <span class="trust-badge"><i class="fas fa-check-circle" style="color:#10b981;"></i>Licensed Partners</span>
      <!-- <span class="trust-badge"><i class="fas fa-globe-africa" style="color:#1A2A6C;"></i>Any Country</span> -->
      <!-- <span class="trust-badge"><i class="fas fa-mobile-alt" style="color:#FF6B4A;"></i>100% Mobile Money</span> -->
      <!-- <span class="trust-badge"><i class="fas fa-chart-line" style="color:#3b82f6;"></i>Live Tracking</span> -->
      <span class="trust-badge"><i class="fas fa-shield-alt" style="color:#10b981;"></i>Mobile Money & Visa Cards</span>
    </div>
  </div>
</div>

<!-- HOW IT WORKS -->
<section class="section" id="how-it-works" style="background:#f9fafb;">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">Simple Process</span>
      <h2 class="section-title">Three Simple Steps. Ninety Seconds.</h2>
      <p class="section-sub">Create, share, and receive — all in under two minutes.</p>
    </div>
    <div class="steps-grid">
      <div class="card step-card">
        <div class="step-icon-wrap">1</div>
        <h3 style="font-weight:800;color:#1A2A6C;font-size:1.1rem;margin-bottom:10px;">Create a Campaign</h3>
        <p style="color:#6b7280;font-size:.9rem;line-height:1.7;">Set up your campaign in 60 seconds. Add details, set your goal, and get a shareable link instantly.</p>
        <p style="margin-top:12px;font-size:.78rem;font-weight:700;color:#FF6B4A;">FREE to start</p>
      </div>
      <div class="card step-card">
        <div class="step-icon-wrap">2</div>
        <h3 style="font-weight:800;color:#1A2A6C;font-size:1.1rem;margin-bottom:10px;">Share with Your People</h3>
        <p style="color:#6b7280;font-size:.9rem;line-height:1.7;">Post your link on WhatsApp, social media, or anywhere. No account needed to contribute.</p>
      </div>
      <div class="card step-card">
        <div class="step-icon-wrap">3</div>
        <h3 style="font-weight:800;color:#1A2A6C;font-size:1.1rem;margin-bottom:10px;">Grow &amp; Receive Funds</h3>
        <p style="color:#6b7280;font-size:.9rem;line-height:1.7;">Watch contributions come in live and withdraw funds to mobile money — processed within 48 hours.</p>
        <p style="margin-top:12px;font-size:.78rem;font-weight:700;color:#10b981;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Payout within 48hrs</p>
      </div>
    </div>
  </div>
</section>

<!-- LIVE CAMPAIGNS -->
<?php
  function campaignCalc($c) {
    $daysLeft = (int)$c['days_left'];
    return [
      'pct'      => min(100, (float)$c['pct']),
      'daysLeft' => $daysLeft,
      'daysStr'  => $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended'),
      'catClass' => 'badge-' . strtolower($c['category']),
      'image'    => imgUrl($c['image_url'] ?: ''),
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
        $lg = campaignCalc($largeCard);
      ?>
      <div class="bento-grid">
        <a href="<?= BASE ?>/campaign-detail.php?id=<?= $largeCard['campaign_id'] ?>" class="bento-large">
          <div class="bento-large-img-wrap">
            <img src="<?= htmlspecialchars($lg['image']) ?>" alt="<?= htmlspecialchars($largeCard['title']) ?>" loading="lazy" />
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
          <?php foreach ($smallCards as $c): $sm = campaignCalc($c); ?>
          <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" class="bento-small">
            <div class="bento-small-img-wrap">
              <img src="<?= htmlspecialchars($sm['image']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
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
        <?php foreach ($featuredArr as $c): $cc = campaignCalc($c); ?>
        <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" class="card campaign-card" style="text-decoration:none;color:inherit;">
          <img class="card-img" src="<?= htmlspecialchars($cc['image']) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
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

<script src="https://cdn.jsdelivr.net/npm/swiper@14.0.1/swiper-bundle.min.js"></script>
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

// ── Hero coverflow photo showcase (Swiper) ──────────────────────
(function() {
  if (!document.querySelector('.hero-swiper') || typeof Swiper === 'undefined') return;
  new Swiper('.hero-swiper', {
    effect: 'coverflow',
    grabCursor: true,
    centeredSlides: true,
    loop: true,
    initialSlide: 1,
    slidesPerView: 'auto',
    coverflowEffect: {
      rotate: 40,
      stretch: 0,
      depth: 140,
      modifier: 1,
      slideShadows: true,
    },
    autoplay: {
      delay: 2600,
      disableOnInteraction: false,
      pauseOnMouseEnter: true,
    },
    pagination: { el: '.hero-swiper .swiper-pagination', clickable: true },
  });
})();
</script>
