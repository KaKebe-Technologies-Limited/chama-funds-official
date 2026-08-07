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

// Fetch top 4 most recent active campaigns for home grid
$featured = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status = 'active'
     ORDER BY c.created_at DESC
     LIMIT 4"
);

// Platform stats
$totalRaised      = $conn->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='completed'")->fetch_row()[0];
$activeCampaigns  = $conn->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetch_row()[0];
$totalContributors= $conn->query("SELECT COUNT(DISTINCT donor_phone) FROM donations WHERE status='completed'")->fetch_row()[0];

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════ HERO ═══════════════════════════ -->
<section class="hero-gradient hero-section" style="overflow:hidden;margin-top:64px;">
  <div class="container">
    <div class="hero-inner">
      <div class="hero-copy">
        <div class="hero-badge"><i class="fas fa-bolt" style="color:#facc15"></i> Built for African Causes</div>
        <h1 style="font-size:clamp(1.8rem,5vw,3.4rem);font-weight:800;line-height:1.15;color:#fff;margin-bottom:20px;">
          Pool Money Together for<br>
          <span style="color:#facc15;">What Matters Most</span>
        </h1>
        <p style="font-size:1.05rem;color:rgba(255,255,255,.82);max-width:480px;line-height:1.7;margin-bottom:32px;">
          Launch a campaign or donate to causes you care about. Transparent, secure, and built for mobile money.
        </p>
        <div class="hero-cta-row">
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-lg">Start a Campaign</a>
          <a href="<?= BASE ?>/donate.php" class="btn btn-outline-white btn-lg">Donate Now</a>
        </div>
        <div class="hero-trust-row">
          <span><i class="fas fa-check-circle" style="color:#6ee7b7;margin-right:6px;"></i>Free to start</span>
          <span><i class="fas fa-check-circle" style="color:#6ee7b7;margin-right:6px;"></i>Payout within 48hrs</span>
          <span><i class="fas fa-check-circle" style="color:#6ee7b7;margin-right:6px;"></i>Live tracking</span>
        </div>
      </div>
      <!-- Hero impact panel (hidden on small mobile) -->
      <!-- <div class="hero-panel">
        <p class="hero-panel-label">Platform Impact</p>
        <div class="hero-stat-grid">
          <div class="hero-stat-tile">
            <span class="hero-stat-icon" style="background:rgba(250,204,21,.15);color:#facc15;"><i class="fas fa-hand-holding-heart"></i></span>
            <div>
              <p class="hero-stat-value">UGX <?= number_format($totalRaised) ?></p>
              <p class="hero-stat-label">Total Raised</p>
            </div>
          </div>
          <div class="hero-stat-tile">
            <span class="hero-stat-icon" style="background:rgba(110,231,183,.15);color:#6ee7b7;"><i class="fas fa-rocket"></i></span>
            <div>
              <p class="hero-stat-value"><?= number_format($activeCampaigns) ?></p>
              <p class="hero-stat-label">Active Campaigns</p>
            </div>
          </div>
          <div class="hero-stat-tile">
            <span class="hero-stat-icon" style="background:rgba(147,197,253,.15);color:#93c5fd;"><i class="fas fa-users"></i></span>
            <div>
              <p class="hero-stat-value"><?= number_format($totalContributors) ?></p>
              <p class="hero-stat-label">Contributors</p>
            </div>
          </div>
          <div class="hero-stat-tile">
            <span class="hero-stat-icon" style="background:rgba(255,107,74,.15);color:#FF6B4A;"><i class="fas fa-mobile-alt"></i></span>
            <div>
              <p class="hero-stat-value">100%</p>
              <p class="hero-stat-label">Mobile Money</p>
            </div>
          </div>
        </div>
        <a href="<?= BASE ?>/campaign-drives.php" class="hero-panel-link">
          <i class="fas fa-th-large"></i> Browse All Campaigns <i class="fas fa-arrow-right"></i>
        </a>
      </div> -->
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<div class="trust-bar">
  <div class="container">
    <div class="trust-bar-inner">
      <span class="trust-badge"><i class="fas fa-check-circle" style="color:#10b981;"></i>Licensed Payment Partner</span>
      <span class="trust-badge"><i class="fas fa-globe-africa" style="color:#1A2A6C;"></i>12 Countries Live</span>
      <span class="trust-badge"><i class="fas fa-mobile-alt" style="color:#FF6B4A;"></i>100% Mobile Money</span>
      <span class="trust-badge"><i class="fas fa-chart-line" style="color:#3b82f6;"></i>Live Tracking</span>
      <span class="trust-badge"><i class="fas fa-shield-alt" style="color:#10b981;"></i>MTN · Airtel · Orange</span>
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
<section class="section" style="background:#fff;">
  <div class="container">
    <div class="section-header">
      <span class="section-eyebrow">Live Now</span>
      <h2 class="section-title">Active Campaign Drives</h2>
      <p class="section-sub">Real campaigns, real people, real impact.</p>
    </div>
    <div class="home-campaigns-grid">
      <?php if ($featured && $featured->num_rows > 0): ?>
        <?php while ($c = $featured->fetch_assoc()): ?>
          <?php
            $pct      = min(100, (float)$c['pct']);
            $daysLeft = (int)$c['days_left'];
            $daysStr  = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended');
            $catClass = 'badge-' . strtolower($c['category']);
            $image    = imgUrl($c['image_url'] ?: '');
          ?>
          <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" class="card campaign-card" style="text-decoration:none;color:inherit;">
            <img class="card-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
            <div class="card-body">
              <div class="campaign-meta">
                <span class="category-badge <?= $catClass ?>"><?= htmlspecialchars($c['category']) ?></span>
                <span class="days-left" <?= $daysLeft <= 3 ? 'style="color:#ef4444;"' : '' ?>><?= htmlspecialchars($daysStr) ?></span>
              </div>
              <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
              <div class="campaign-stats">
                <span><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</span>
                <span style="font-weight:700;color:#1A2A6C;"><?= $pct ?>%</span>
              </div>
              <div class="progress-wrap"><div class="progress-fill" data-width="<?= $pct ?>%"></div></div>
              <div class="campaign-footer">
                <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?></span>
                <span class="btn btn-primary btn-sm">Donate</span>
              </div>
            </div>
          </a>
        <?php endwhile; ?>
      <?php else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 0;color:#9ca3af;">
          <i class="fas fa-rocket" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
          No active campaigns yet. <a href="<?= BASE ?>/create-campaign.php" style="color:#FF6B4A;font-weight:700;">Be the first!</a>
        </div>
      <?php endif; ?>
    </div>
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
