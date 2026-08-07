<?php
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
      <div style="font-size:4rem;margin-bottom:16px;">🔍</div>
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
    'Medical'    => 'https://images.unsplash.com/photo-1666214280557-f1b5022eb634?w=1600&q=80',
    'Education'  => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1600&q=80',
    'Community'  => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1600&q=80',
    'Family'     => 'https://images.unsplash.com/photo-1602928321679-560bb453f190?w=1600&q=80',
    'Business'   => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1600&q=80',
    'Emergency'  => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?w=1600&q=80',
    'Marriage'   => 'https://images.unsplash.com/photo-1607462109225-6b64ae2dd3cb?w=1600&q=80',
    'Funeral'    => 'https://images.unsplash.com/photo-1501436513145-30f24e19fcc8?w=1600&q=80',
    'Agriculture'=> 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=1600&q=80',
    'Religion'   => 'https://images.unsplash.com/photo-1438232992991-995b671e4b8a?w=1600&q=80',
    'Sports'     => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=1600&q=80',
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

// ── Donations ────────────────────────────────────────────────
$dons = $conn->query(
    "SELECT donor_name, is_anonymous, amount, mobile_money_network, payment_date
     FROM donations WHERE campaign_id=$cid AND status='completed'
     ORDER BY payment_date DESC LIMIT 20"
);
$totalDonorsAll = (int)$conn->query(
    "SELECT COUNT(*) FROM donations WHERE campaign_id=$cid AND status='completed'"
)->fetch_row()[0];

$pct       = min(100, (float)$c['pct']);
$daysLeft  = (int)$c['days_left'];
$daysStr   = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Campaign ended');
$daysUrgent= $daysLeft >= 0 && $daysLeft <= 5;
$remaining = max(0, $c['goal_amount'] - $c['raised_amount']);
$isOwner   = isset($_SESSION['user_id']) &&
             ($_SESSION['user_id'] == $c['campaigner_id'] || ($_SESSION['role'] ?? '') === 'admin');

$protocol     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$canonicalUrl = BASE . '/campaign-detail.php?id=' . $cid;
$ogImage      = !empty($campaignImages[0]['image_url'])
                    ? (strpos($campaignImages[0]['image_url'],'http')===0
                        ? $campaignImages[0]['image_url']
                        : $protocol.'://'.$_SERVER['HTTP_HOST'].$campaignImages[0]['image_url'])
                    : $heroImg;
$ogTitle      = htmlspecialchars($c['title'], ENT_QUOTES);
$ogDesc       = htmlspecialchars(substr(strip_tags($c['description']),0,160), ENT_QUOTES);
$pageTitle    = htmlspecialchars($c['title']).' – ChamaFunds';
$pageDescription = $ogDesc;

$extraCss = <<<HTML
  <meta property="og:type"         content="website"/>
  <meta property="og:url"          content="{$canonicalUrl}"/>
  <meta property="og:title"        content="{$ogTitle}"/>
  <meta property="og:description"  content="{$ogDesc}"/>
  <meta property="og:image"        content="{$ogImage}"/>
  <meta name="twitter:card"        content="summary_large_image"/>
  <meta name="twitter:title"       content="{$ogTitle}"/>
  <meta name="twitter:description" content="{$ogDesc}"/>
  <meta name="twitter:image"       content="{$ogImage}"/>
  <link rel="canonical"            href="{$canonicalUrl}"/>
HTML;

include __DIR__ . '/includes/header.php';
?>

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
          <i class="fas fa-share-alt"></i><?= number_format($c['share_count']) ?> shares
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
      <a href="#donateWidget" class="cd-tab-donate">
        <i class="fas fa-heart"></i> Donate Now
      </a>
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

          <!-- Progress block -->
          <div class="cd-progress-card">
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
                <span class="cd-prog-stat-val"><?= number_format($c['contributor_count']) ?></span>
                <span class="cd-prog-stat-lbl">Contributors</span>
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
                <span class="cd-prog-stat-val"><?= number_format($c['share_count']) ?></span>
                <span class="cd-prog-stat-lbl">Shares</span>
              </div>
            </div>
          </div>

          <!-- Campaign story -->
          <div class="cd-section">
            <h2 class="cd-section-h">Campaign Story</h2>
            <div class="cd-story">
              <?= nl2br(htmlspecialchars($c['description'])) ?>
            </div>
            <div class="cd-verified">
              <i class="fas fa-shield-alt"></i>
              Verified campaign — funds go directly to the beneficiary's mobile money account.
            </div>
          </div>

          <!-- Donate CTA (inline, after story) -->
          <?php if ($c['status']==='active'): ?>
          <div class="cd-inline-cta">
            <div>
              <p class="cd-inline-cta-title">Support this campaign</p>
              <p class="cd-inline-cta-sub">Every contribution makes a real difference</p>
            </div>
            <a href="#donateWidget" class="cd-donate-btn">
              <i class="fas fa-heart"></i> Donate Now
            </a>
          </div>
          <?php endif; ?>

          <!-- Campaigner -->
          <div class="cd-section">
            <h2 class="cd-section-h">About the Campaigner</h2>
            <div class="cd-campaigner-row">
              <?php if ($c['campaigner_avatar']): ?>
                <img src="<?= htmlspecialchars($c['campaigner_avatar']) ?>" class="cd-camp-ava" alt="" />
              <?php else: ?>
                <div class="cd-camp-ava cd-camp-ava-init"><?= strtoupper(substr($c['campaigner_name'],0,1)) ?></div>
              <?php endif; ?>
              <div class="cd-camp-info">
                <p class="cd-camp-name"><?= htmlspecialchars($c['campaigner_name']) ?></p>
                <p class="cd-camp-sub">
                  <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['country']) ?>
                  &nbsp;·&nbsp; Started <?= date('M j, Y', strtotime($c['created_at'])) ?>
                </p>
              </div>
              <?php if ($isOwner): ?>
              <a href="<?= BASE ?>/edit-campaign.php?id=<?= $cid ?>" class="cd-edit-btn">
                <i class="fas fa-edit"></i> Edit
              </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Share -->
          <div class="cd-share-block">
            <p class="cd-share-lbl"><i class="fas fa-bullhorn"></i> Share this campaign</p>
            <div class="cd-share-row">
              <a href="https://wa.me/?text=<?= urlencode($c['title'].' – Support: '.$canonicalUrl) ?>"
                 target="_blank" class="cd-share-wa" onclick="trackShare()">
                <i class="fab fa-whatsapp"></i> WhatsApp
              </a>
              <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>"
                 target="_blank" class="cd-share-fb" onclick="trackShare()">
                <i class="fab fa-facebook"></i> Facebook
              </a>
              <a href="https://twitter.com/intent/tweet?text=<?= urlencode($c['title']) ?>&url=<?= urlencode($canonicalUrl) ?>"
                 target="_blank" class="cd-share-tw" onclick="trackShare()">
                <i class="fab fa-twitter"></i> Twitter
              </a>
              <button class="cd-share-copy" onclick="copyLink(this)">
                <i class="fas fa-link"></i> Copy Link
              </button>
            </div>
          </div>

        </div><!-- /panel-story -->

        <!-- ── PANEL: Donations ── -->
        <div class="cd-panel" id="panel-donations">
          <div class="cd-section">
            <div class="cd-section-head">
              <h2 class="cd-section-h">
                <?= number_format($totalDonorsAll) ?> Contributions
              </h2>
              <span class="cd-live-dot"><span></span>Live</span>
            </div>

            <?php if ($dons && $dons->num_rows > 0):
              $donations_arr = [];
              while ($d = $dons->fetch_assoc()) $donations_arr[] = $d;
              // Sort amounts for star rating (highest gets 5 stars)
              $amounts = array_column($donations_arr, 'amount');
              $maxAmt  = max($amounts) ?: 1;
            ?>
            <div class="cd-don-list">
              <?php foreach ($donations_arr as $d):
                // Star rating based on relative amount (1–5 stars)
                $ratio = $d['amount'] / $maxAmt;
                $stars = $ratio >= 0.8 ? 5 : ($ratio >= 0.6 ? 4 : ($ratio >= 0.4 ? 3 : ($ratio >= 0.2 ? 2 : 1)));
              ?>
              <div class="cd-don-row">
                <div class="cd-don-ava">
                  <?= $d['is_anonymous']
                    ? '<i class="fas fa-user-secret"></i>'
                    : strtoupper(substr($d['donor_name'] ?? '?', 0, 1)) ?>
                </div>
                <div class="cd-don-body">
                  <div class="cd-don-top">
                    <span class="cd-don-name">
                      <?= $d['is_anonymous'] ? 'Anonymous' : htmlspecialchars($d['donor_name']) ?>
                    </span>
                    <span class="cd-don-amt">
                      +<?= $c['currency'] ?> <?= number_format($d['amount']) ?>
                    </span>
                  </div>
                  <div class="cd-don-bottom">
                    <span class="cd-don-stars">
                      <?php for ($s=1; $s<=5; $s++): ?>
                        <i class="fas fa-star <?= $s<=$stars?'cd-star-on':'cd-star-off' ?>"></i>
                      <?php endfor; ?>
                    </span>
                    <span class="cd-don-meta">
                      <i class="fas fa-mobile-alt"></i>
                      <?= htmlspecialchars($d['mobile_money_network']) ?>
                      &nbsp;·&nbsp; <?= date('M j, Y', strtotime($d['payment_date'])) ?>
                    </span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($totalDonorsAll > 20): ?>
            <p class="cd-more-note">+<?= number_format($totalDonorsAll-20) ?> more generous contributors</p>
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
                ['Confirm on your phone', 'A USSD prompt appears. Enter your mobile money PIN — no app needed.'],
                ['Funds reach the campaign', '92.5% goes directly to the campaigner, tracked live on the ledger.'],
                ['Campaigner withdraws', 'After admin review (≤48 hrs), funds are paid out to mobile money same day.'],
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
                ['Platform fee?', 'ChamaFunds charges 7.5% per contribution, deducted only at withdrawal. Creating a campaign is always free.'],
                ['Is my donation secure?', 'Yes. All payments run through PawaPay (licensed mobile money partner). Your PIN never leaves your phone — encrypted with 256-bit SSL.'],
                ['Can I donate anonymously?', 'Yes — toggle "Remain anonymous" in the donation widget. Your name shows as "Anonymous" on the public ledger.'],
                ['Which networks are supported?', 'MTN Mobile Money, Airtel Money, Orange Money, and Safaricom M-Pesa across Uganda, Kenya, Rwanda, Tanzania and more.'],
                ['How do I know funds reached the right person?', 'Every contribution is logged on the live public ledger on this page. All campaigns are verified before going live.'],
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
              <button type="button" class="cd-q-btn" data-amount="10000">UGX 10K</button>
              <button type="button" class="cd-q-btn" data-amount="25000">UGX 25K</button>
              <button type="button" class="cd-q-btn" data-amount="50000">UGX 50K</button>
              <button type="button" class="cd-q-btn" data-amount="100000">UGX 100K</button>
            </div>

            <div class="cd-field">
              <label class="cd-label">Amount (<?= $c['currency'] ?>) <span>*</span></label>
              <div class="cd-input-wrap">
                <span class="cd-input-pre"><?= $c['currency'] ?></span>
                <input type="number" id="contributionAmount" class="cd-input" placeholder="Enter amount" min="1000" value="10000" />
              </div>
              <p class="cd-fee-hint" id="feeHint">
                Fee (7.5%): <strong id="feeAmt">750</strong> &nbsp;·&nbsp;
                You contribute: <strong id="netAmt" style="color:#FF6B4A;"><?= $c['currency'] ?> 10,000</strong>
              </p>
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
          <a href="https://wa.me/?text=<?= urlencode($c['title'].' – Support: '.$canonicalUrl) ?>"
             target="_blank" class="cd-wa-nudge" onclick="trackShare()">
            <i class="fab fa-whatsapp"></i>
            <span>Share on WhatsApp — doubles donations</span>
            <i class="fas fa-arrow-right"></i>
          </a>

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

<?php include __DIR__ . '/includes/footer.php'; ?>

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
.cd-panel.active { display:block; }

/* ── Campaign photos ───────────────────────────────────────── */
.cd-photos-section {
  background:#fff; border-radius:16px;
  overflow:hidden; margin-bottom:20px;
  box-shadow:0 1px 12px rgba(0,0,0,.07);
}
.cd-photo-main { overflow:hidden; max-height:400px; }
.cd-photo-main img {
  width:100%; max-height:400px; object-fit:cover;
  display:block; transition:transform .5s ease;
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

/* Story */
.cd-story { font-size:.92rem; color:#334155; line-height:2; margin-bottom:16px; }
.cd-verified {
  display:flex; align-items:flex-start; gap:8px;
  background:#f8fafc; border-radius:10px;
  padding:11px 14px; font-size:.8rem; color:#475569;
  border-left:3px solid #0f172a;
}
.cd-verified i { color:#0f172a; margin-top:1px; flex-shrink:0; }

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
  width:38px; height:38px; border-radius:50%; flex-shrink:0;
  background:#0f172a;
  display:flex; align-items:center; justify-content:center;
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
.cd-widget::before {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
  background:linear-gradient(90deg,#0f172a 40%,#FF6B4A);
}
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
  /* Photos full-width, slightly shorter */
  .cd-photo-main { max-height:240px; }
  .cd-photo-main img { max-height:240px; }
  /* Compact body padding */
  .cd-body { padding:20px 0 60px; }
  /* Make inline CTA stack on mobile */
  .cd-inline-cta { flex-direction:column; align-items:flex-start; gap:12px; }
  .cd-donate-btn-full { width:100%; }
}
</style>

<script>
// ── Hero progress bar animate ──────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.cd-prog-fill[data-w]').forEach(function(el) {
    setTimeout(function(){ el.style.width = el.dataset.w + '%'; }, 300);
  });
  // Hero bar already has inline style, animate it
  document.querySelectorAll('.cd-hero-bar-fill').forEach(function(el){
    var w = el.style.width; el.style.width = '0';
    setTimeout(function(){ el.style.width = w; }, 200);
  });
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
    updateFee(amt);
  });
});

function updateFee(amt) {
  var fee = Math.round(amt * 0.075);
  document.getElementById('feeAmt').textContent    = fee.toLocaleString();
  document.getElementById('netAmt').textContent    = '<?= $c['currency'] ?> ' + amt.toLocaleString();
  document.getElementById('donateBtnAmt').textContent = amt.toLocaleString();
}
document.getElementById('contributionAmount')?.addEventListener('input', function(){
  updateFee(parseFloat(this.value) || 0);
});
updateFee(10000);

// ── Copy link ──────────────────────────────────────────────
function copyLink(btn) {
  navigator.clipboard.writeText(window.location.href).then(function() {
    var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.style.background = '#d1fae5'; btn.style.color = '#065f46';
    setTimeout(function(){ btn.innerHTML = orig; btn.style.background=''; btn.style.color=''; }, 2200);
    window.showToast('Link copied!', 'success');
  });
}

// ── Track share count ──────────────────────────────────────
function trackShare() {
  fetch('<?= BASE ?>/api/campaigns.php?action=track_share', {
    method:'POST',
    body: (() => { var fd=new FormData(); fd.append('campaign_id','<?= $cid ?>'); return fd; })()
  }).catch(()=>{});
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
    var data = await res.json();

    if (data.success && data.redirect_url) {
      // Redirect the user to the Pesapal hosted payment page
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
