<?php
// ============================================================
// ChamaFunds – campaign-drives.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'Campaign Drives – Active Fundraising in Uganda | ChamaFunds';
$pageDescription = 'Browse active crowdfunding campaigns in Uganda. Support medical emergencies, education, community projects, and more.';

// Fetch active campaigns (server-side, JS filter also works)
$result = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     JOIN users u ON c.campaigner_id = u.user_id
     WHERE c.status = 'active'
     ORDER BY c.created_at DESC"
);
$campaigns = [];
while ($r = $result->fetch_assoc()) $campaigns[] = $r;

$totalCount = count($campaigns);
$totalSupporters = array_sum(array_column($campaigns, 'contributor_count'));

// A few real campaigner initials for the hero's avatar stack
$avatarSeed = [];
foreach ($campaigns as $c) {
    $n = trim($c['campaigner_name']);
    if ($n && !in_array($n, $avatarSeed, true)) $avatarSeed[] = $n;
    if (count($avatarSeed) >= 4) break;
}
$avatarColours = ['#FF6B4A','#1A2A6C','#10b981','#f59e0b'];

$heroCollage = [
    BASE . '/img/slider/pexels-illustrate-digital-ug-924569584-28100858.jpg',
    BASE . '/img/slider/pexels-lagosfoodbank-9823017.jpg',
    BASE . '/img/slider/pexels-lbk-studio-2149333232-35094475.jpg',
    BASE . '/img/slider/pexels-matazumultimedia-32154741.jpg',
    BASE . '/img/slider/pexels-illustrate-digital-ug-924569584-28101466.jpg',
];
$col1 = [$heroCollage[0], $heroCollage[1], $heroCollage[2], $heroCollage[3]];
$col2 = [$heroCollage[2], $heroCollage[4], $heroCollage[0], $heroCollage[3]];
$col3 = [$heroCollage[4], $heroCollage[1], $heroCollage[3], $heroCollage[0]];

include __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════ HERO ═══════════════════════════ -->
<section class="cdh-hero">
  <div class="container">
    <div class="cdh-grid">
      <div class="cdh-copy">
        <div class="cdh-badge"><i class="fas fa-hand-holding-heart"></i> Community Fundraising</div>
        <h1 class="cdh-title">Give Help to <span class="cdh-accent">People in Need.</span></h1>
        <p class="cdh-sub">Every campaign here is a real story — medical bills, school fees, emergencies, and community projects. Your contribution, big or small, changes a life today.</p>
        <div class="cdh-cta-row">
          <a href="#campaignsGrid" class="btn btn-primary btn-lg">Donate Now</a>
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

    <div class="cdh-footer-row">
      <p class="cdh-count"><span class="cdh-dot"></span><?= $totalCount ?> active campaigns right now</p>
      <a href="<?= BASE ?>/create-campaign.php" class="btn btn-outline btn-sm"><i class="fas fa-plus" style="margin-right:6px;"></i>Start Your Campaign</a>
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
.cdh-sub { color:#64748b; font-size:1rem; line-height:1.7; max-width:440px; margin-bottom:32px; }
.cdh-cta-row { display:flex; align-items:center; gap:24px; flex-wrap:wrap; }
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

.cdh-footer-row {
  display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;
  padding:32px 0 24px; margin-top:24px; border-top:1px solid #f1f5f9;
}
.cdh-count { display:flex; align-items:center; gap:8px; font-size:.86rem; font-weight:700; color:#1A2A6C; }
.cdh-dot { width:8px; height:8px; border-radius:50%; background:#10b981; box-shadow:0 0 0 4px rgba(16,185,129,.18); }

@media (max-width:1023px) {
  .cdh-grid { grid-template-columns:1fr; gap:28px; }
  .cdh-copy { text-align:center; }
  .cdh-sub { margin-left:auto; margin-right:auto; }
  .cdh-cta-row { justify-content:center; }
  /* Simpler 2-column flow on mobile/tablet — shorter, lighter, still "alive" */
  .cdh-collage { grid-template-columns:repeat(2,1fr); height:280px; gap:10px; }
  .cdh-collage .cdh-col:nth-child(3) { display:none; }
  .cdh-col:nth-child(2) { margin-top:-32px; }
}
@media (max-width:640px) {
  /* Full-bleed mobile hero: the flowing photo collage becomes the whole
     background (faded + tinted for contrast), with the headline and the
     CTA + social proof both centered vertically in the middle. */
  .cdh-hero { padding:0; }
  .cdh-grid {
    position:relative; display:block; min-height:100vh;
    grid-template-columns:1fr; gap:0;
  }
  .cdh-grid::before {
    content:''; position:absolute; inset:0; z-index:1; pointer-events:none;
    background:linear-gradient(180deg, rgba(15,23,42,.55) 0%, rgba(26,42,108,.6) 55%, rgba(15,23,42,.85) 100%);
  }
  .cdh-collage {
    position:absolute; inset:0; z-index:0;
    width:100%; height:100%; opacity:.3;
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
  .cdh-social-proof { margin-left:0; }
  .cdh-stat { color:rgba(255,255,255,.85); }
  .cdh-stat strong { color:#fff; }
  .cdh-avatar { border-color:rgba(255,255,255,.85); }
  .cdh-footer-row { padding:22px 0 18px; flex-direction:column; align-items:flex-start; gap:14px; }
}
</style>

<!-- STICKY SEARCH -->
<div style="background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 0;position:sticky;top:64px;z-index:100;">
  <div class="container">
    <div class="search-input-wrap">
      <i class="fas fa-search"></i>
      <input type="text" id="campaignSearch" class="form-input" placeholder="Search campaigns…" />
    </div>
  </div>
</div>

<!-- FILTERS (not sticky) -->
<div style="background:#f9fafb;border-bottom:1px solid #e5e7eb;padding:10px 0;">
  <div class="container">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <select id="categoryFilter" class="form-input" style="max-width:140px;font-size:.82rem;padding:7px 10px;">
        <option value="">All Categories</option>
        <option value="family">Family</option>
        <option value="medical">Medical</option>
        <option value="education">Education</option>
        <option value="community">Community</option>
        <option value="business">Business</option>
        <option value="emergency">Emergency</option>
      </select>
      <select id="countryFilter" class="form-input" style="max-width:130px;font-size:.82rem;padding:7px 10px;">
        <option value="">All Countries</option>
        <option value="uganda">Uganda</option>
        <option value="kenya">Kenya</option>
        <option value="rwanda">Rwanda</option>
        <option value="nigeria">Nigeria</option>
        <option value="zambia">Zambia</option>
      </select>
      <select id="sortFilter" class="form-input" style="max-width:140px;font-size:.82rem;padding:7px 10px;">
        <option value="most-recent">Most Recent</option>
        <option value="most-funded">Most Funded</option>
        <option value="ending-soon">Ending Soon</option>
      </select>
    </div>
  </div>
</div>

<section class="section" style="background:#f9fafb;">
  <div class="container">
    <div id="campaignsGrid" class="campaigns-grid cd-editorial-grid">
      <?php
      $inspiringMessages = [
        ['icon' => 'fa-seedling', 'text' => 'Small acts add up. A single contribution here today might be the reason a child stays in school, a family gets clean water, or someone survives a medical emergency.', 'sub' => '— The ChamaFunds Community'],
        ['icon' => 'fa-hand-holding-heart', 'text' => 'You don\'t need to change the whole world today. Just one person\'s tomorrow. Every campaign below is a real story waiting for people like you.', 'sub' => '— The ChamaFunds Community'],
      ];
      $catEmoji = ['medical'=>'🏥','education'=>'📚','emergency'=>'🆘','community'=>'💧','business'=>'💼'];
      $bannerIdx = 0;
      $cycle = 8; // 4 cards + 1 featured + 3 list rows, then an inspiring banner
      foreach ($campaigns as $i => $c):
        $pct      = min(100, (float)$c['pct']);
        $daysLeft = (int)$c['days_left'];
        $daysStr  = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended');
        $catLower = strtolower($c['category']);
        $catClass = 'badge-' . $catLower;
        $gallery  = campaignCardGallery($conn, $c['campaign_id']);
        $image    = !empty($gallery) ? $gallery[0] : imgUrl($c['image_url'] ?: '');
        $emoji    = $catEmoji[$catLower] ?? '🌟';

        $posInCycle = $i % $cycle;
        $variant = $posInCycle < 4 ? 'card' : ($posInCycle === 4 ? 'featured' : 'list');

        // Drop in an inspiring message right as a fresh cycle begins (but not before the very first item)
        if ($posInCycle === 0 && $i > 0):
          $msg = $inspiringMessages[$bannerIdx % count($inspiringMessages)]; $bannerIdx++;
      ?>
      <div class="cd-quote-banner">
        <i class="fas <?= $msg['icon'] ?> cd-quote-icon"></i>
        <div>
          <p class="cd-quote-text">"<?= htmlspecialchars($msg['text']) ?>"</p>
          <p class="cd-quote-sub"><?= htmlspecialchars($msg['sub']) ?></p>
        </div>
      </div>
      <?php endif; ?>

      <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>"
         class="card campaign-card filterable-card <?= $variant === 'featured' ? 'cd-card-featured' : ($variant === 'list' ? 'cd-card-list' : '') ?>"
         data-title="<?= htmlspecialchars(strtolower($c['title'])) ?>"
         data-category="<?= $catLower ?>"
         data-country="<?= strtolower($c['country']) ?>"
         data-pct="<?= $pct ?>"
         data-days="<?= $daysLeft ?>">

        <?php if ($variant === 'list'): ?>
          <!-- ── Compact list row: image + title ─────────────────── -->
          <div class="cd-list-thumb">
            <?php if ($image): ?>
              <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
            <?php else: ?>
              <div class="card-img-placeholder" style="height:100%;font-size:1.4rem;"><?= $emoji ?></div>
            <?php endif; ?>
          </div>
          <div class="cd-list-body">
            <p class="cd-list-title"><?= htmlspecialchars($c['title']) ?></p>
            <div class="cd-list-meta">
              <span class="category-badge <?= $catClass ?>" style="font-size:.66rem;padding:2px 8px;"><?= htmlspecialchars($c['category']) ?></span>
              <span><?= htmlspecialchars($c['country']) ?></span>
              <span><?= htmlspecialchars($daysStr) ?></span>
            </div>
          </div>
          <div class="cd-list-pct"><?= $pct ?>%</div>

        <?php elseif ($variant === 'featured'): ?>
          <!-- ── Featured spotlight: one campaign, full width, with description ── -->
          <div class="cd-feat-media">
            <?php if (!empty($gallery)): ?>
              <?= renderCardImageSlider($gallery, $c['title']) ?>
            <?php elseif ($image): ?>
              <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
            <?php else: ?>
              <div class="card-img-placeholder" style="height:100%;"><?= $emoji ?></div>
            <?php endif; ?>
          </div>
          <div class="cd-feat-body">
            <span class="cd-feat-eyebrow"><i class="fas fa-star"></i> Featured Campaign</span>
            <div class="campaign-meta" style="margin-bottom:10px;">
              <span class="category-badge <?= $catClass ?>"><?= htmlspecialchars($c['category']) ?></span>
              <span class="days-left" <?= $daysLeft <= 3 ? 'style="color:#ef4444;"' : '' ?>><?= htmlspecialchars($daysStr) ?></span>
            </div>
            <p class="cd-feat-title"><?= htmlspecialchars($c['title']) ?></p>
            <p class="cd-feat-desc"><?= htmlspecialchars(wordLimit(descriptionToPlainText($c['description']), 32)) ?></p>
            <div class="cd-feat-footer">
              <div style="flex:1;min-width:180px;">
                <div class="campaign-stats">
                  <span><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised of <?= number_format($c['goal_amount']) ?></span>
                  <span style="font-weight:700;color:#1A2A6C;"><?= $pct ?>%</span>
                </div>
                <div class="progress-wrap"><div class="progress-fill" data-width="<?= $pct ?>%"></div></div>
              </div>
              <span class="btn btn-primary"><i class="fas fa-heart" style="margin-right:6px;"></i>Donate Now</span>
            </div>
          </div>

        <?php else: ?>
          <!-- ── Regular grid card ────────────────────────────────── -->
          <?php if (!empty($gallery)): ?>
            <?= renderCardImageSlider($gallery, $c['title']) ?>
          <?php elseif ($image): ?>
            <img class="card-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
          <?php else: ?>
            <div class="card-img-placeholder"><?= $emoji ?></div>
          <?php endif; ?>
          <div class="card-body">
            <div class="campaign-meta">
              <span class="category-badge <?= $catClass ?>"><?= htmlspecialchars($c['category']) ?></span>
              <span class="days-left" <?= $daysLeft <= 3 ? 'style="color:#ef4444;"' : '' ?>><?= htmlspecialchars($daysStr) ?></span>
            </div>
            <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
            <p style="font-size:.78rem;color:#9ca3af;margin-bottom:8px;">
              <i class="fas fa-map-marker-alt" style="margin-right:4px;"></i>
              <?= htmlspecialchars($c['country']) ?> · by <?= htmlspecialchars($c['campaigner_name']) ?>
            </p>
            <div class="campaign-stats">
              <span><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</span>
              <span style="font-weight:700;color:#1A2A6C;"><?= $pct ?>%</span>
            </div>
            <div style="font-size:.74rem;color:#9ca3af;margin-bottom:4px;">
              Target: <strong style="color:#1A2A6C;"><?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></strong>
            </div>
            <div class="progress-wrap"><div class="progress-fill" data-width="<?= $pct ?>%"></div></div>
            <div class="campaign-footer">
              <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?> contributors</span>
              <span class="btn btn-primary btn-sm"><i class="fas fa-heart" style="margin-right:5px;"></i>Donate Now</span>
            </div>
          </div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
      <?php if (empty($campaigns)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:60px 0;color:#9ca3af;">
        No active campaigns yet. <a href="<?= BASE ?>/create-campaign.php" style="color:#FF6B4A;">Start one!</a>
      </div>
      <?php endif; ?>
    </div>
    <p id="noResults" style="display:none;text-align:center;color:#9ca3af;padding:40px 0;">No campaigns match your search.</p>
  </div>
</section>

<style>
#campaignsGrid.cd-editorial-grid { grid-template-columns:repeat(4,1fr); align-items:start; }

.cd-card-featured {
  grid-column:1/-1; display:flex; flex-direction:row;
  background:#fff; border-radius:20px; overflow:hidden;
  box-shadow:0 4px 20px rgba(26,42,108,.08);
  transition:transform .2s ease, box-shadow .2s ease;
}
.cd-card-featured:hover { transform:translateY(-3px); box-shadow:0 12px 30px rgba(26,42,108,.14); }
.cd-feat-media { position:relative; width:42%; flex-shrink:0; min-height:280px; background:#0f172a; }
.cd-feat-media img, .cd-feat-media .card-slider { width:100%; height:100%; }
.cd-feat-media img { object-fit:cover; display:block; }
.cd-feat-body { flex:1; padding:32px; display:flex; flex-direction:column; min-width:0; }
.cd-feat-eyebrow { display:inline-flex; align-items:center; gap:6px; font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:#FF6B4A; margin-bottom:12px; }
.cd-feat-title { font-size:1.4rem; font-weight:800; color:#0f172a; margin-bottom:10px; line-height:1.28; }
.cd-feat-desc { color:#64748b; font-size:.92rem; line-height:1.65; margin-bottom:20px; flex:1; }
.cd-feat-footer { display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap; }

.cd-card-list {
  grid-column:span 2; display:flex; flex-direction:row; align-items:center; gap:16px;
  background:#fff; border-radius:14px; padding:10px;
  box-shadow:0 1px 6px rgba(0,0,0,.05);
  transition:transform .15s ease, box-shadow .15s ease;
}
.cd-card-list:hover { transform:translateX(4px); box-shadow:0 4px 14px rgba(0,0,0,.08); }
.cd-list-thumb { width:72px; height:72px; border-radius:10px; overflow:hidden; flex-shrink:0; background:#f1f5f9; }
.cd-list-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.cd-list-body { flex:1; min-width:0; }
.cd-list-title { font-weight:700; color:#0f172a; font-size:.9rem; margin-bottom:5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cd-list-meta { display:flex; align-items:center; gap:10px; font-size:.74rem; color:#94a3b8; flex-wrap:wrap; }
.cd-list-pct { font-weight:800; color:#FF6B4A; font-size:.92rem; flex-shrink:0; padding-right:6px; }

.cd-quote-banner {
  grid-column:1/-1; display:flex; align-items:center; gap:22px;
  background:linear-gradient(135deg,#1A2A6C 0%,#2a3f8a 100%);
  border-radius:20px; padding:36px 44px; color:#fff;
}
.cd-quote-icon { font-size:1.9rem; color:#facc15; flex-shrink:0; }
.cd-quote-text { font-size:1.1rem; font-weight:600; line-height:1.55; font-style:italic; }
.cd-quote-sub { font-size:.8rem; color:rgba(255,255,255,.6); margin-top:8px; font-style:normal; }

@media (max-width:1023px) {
  #campaignsGrid.cd-editorial-grid { grid-template-columns:repeat(2,1fr); }
  .cd-card-featured { flex-direction:column; }
  .cd-feat-media { width:100%; min-height:220px; }
}
@media (max-width:640px) {
  #campaignsGrid.cd-editorial-grid { grid-template-columns:1fr; }
  .cd-feat-body { padding:22px; }
  .cd-card-list { grid-column:1/-1; }
  .cd-list-title { white-space:normal; }
  .cd-quote-banner { flex-direction:column; text-align:center; padding:26px 22px; gap:14px; }
}
</style>

<script>
// On mobile, re-flow each cycle to: 3 cards, then the 3 compact listings,
// then the featured spotlight, then the banner — instead of the desktop
// order (4 cards, featured, 3 listings, banner) — so listings surface sooner.
(function () {
  var grid = document.getElementById('campaignsGrid');
  if (!grid || !grid.classList.contains('cd-editorial-grid')) return;

  var originalOrder = Array.prototype.slice.call(grid.children);
  function isMobile() { return window.innerWidth <= 640; }

  function mobileSequence() {
    var cards = [], lists = [], featured = [], banners = [];
    Array.prototype.slice.call(grid.children).forEach(function (el) {
      if (el.classList.contains('cd-quote-banner')) banners.push(el);
      else if (el.classList.contains('cd-card-featured')) featured.push(el);
      else if (el.classList.contains('cd-card-list')) lists.push(el);
      else if (el.classList.contains('filterable-card')) cards.push(el);
    });
    var out = [], ci = 0, li = 0, fi = 0, bi = 0;
    while (ci < cards.length || li < lists.length || fi < featured.length) {
      for (var n = 0; n < 3 && ci < cards.length; n++) out.push(cards[ci++]);
      for (var m = 0; m < 3 && li < lists.length; m++) out.push(lists[li++]);
      if (ci < cards.length) out.push(cards[ci++]);
      if (fi < featured.length) out.push(featured[fi++]);
      if (bi < banners.length) out.push(banners[bi++]);
    }
    return out;
  }

  var wasMobile = null;
  function sync(force) {
    var mobile = isMobile();
    if (!force && mobile === wasMobile) return;
    wasMobile = mobile;
    var seq = mobile ? mobileSequence() : originalOrder;
    seq.forEach(function (el) { grid.appendChild(el); });
  }

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(sync, 150);
  });
  if (document.readyState === 'complete') sync();
  else window.addEventListener('load', function () { sync(); });
  // Defensive re-check shortly after load, in case the viewport wasn't
  // fully settled yet on the very first pass.
  setTimeout(function () { sync(true); }, 300);
})();
</script>

<section style="background:#1A2A6C;padding:56px 0;">
  <div class="container" style="text-align:center;">
    <h2 style="color:#fff;font-weight:800;font-size:1.7rem;margin-bottom:12px;">Have a cause worth sharing?</h2>
    <p style="color:rgba(255,255,255,.7);margin-bottom:28px;max-width:480px;margin-left:auto;margin-right:auto;">Start your own campaign in under 2 minutes. Free to create, mobile money-first.</p>
    <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-lg">🚀 Start Your Own Campaign</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
