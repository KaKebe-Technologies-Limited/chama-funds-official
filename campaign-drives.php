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

include __DIR__ . '/includes/header.php';
?>

<div style="background:#fff;border-bottom:1px solid #e5e7eb;padding:80px 0 28px;">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
      <div>
        <h1 style="font-size:1.8rem;font-weight:800;color:#1A2A6C;">Live Campaign Drives</h1>
        <p style="color:#9ca3af;font-size:.9rem;margin-top:4px;">
          <span style="color:#10b981;font-weight:700;">● <?= $totalCount ?> active campaigns</span>
        </p>
      </div>
      <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:6px;"></i>Start Your Campaign</a>
    </div>
  </div>
</div>

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
    <div id="campaignsGrid" class="campaigns-grid">
      <?php foreach ($campaigns as $c):
        $pct      = min(100, (float)$c['pct']);
        $daysLeft = (int)$c['days_left'];
        $daysStr  = $daysLeft > 0 ? "$daysLeft days left" : ($daysLeft === 0 ? 'Ends today' : 'Ended');
        $catLower = strtolower($c['category']);
        $catClass = 'badge-' . $catLower;
        $image    = imgUrl($c['image_url'] ?: '');
      ?>
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>"
         class="card campaign-card filterable-card"
         style="text-decoration:none;color:inherit;"
         data-title="<?= htmlspecialchars(strtolower($c['title'])) ?>"
         data-category="<?= $catLower ?>"
         data-country="<?= strtolower($c['country']) ?>"
         data-pct="<?= $pct ?>"
         data-days="<?= $daysLeft ?>">
        <?php if ($image): ?>
          <img class="card-img" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($c['title']) ?>" loading="lazy" />
        <?php else: ?>
          <div class="card-img-placeholder"><?= $catLower === 'medical' ? '🏥' : ($catLower === 'education' ? '📚' : ($catLower === 'emergency' ? '🆘' : ($catLower === 'community' ? '💧' : ($catLower === 'business' ? '💼' : '🌟')))) ?></div>
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
            <span class="btn btn-primary btn-sm">Donate Now</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty($campaigns)): ?>
      <div style="grid-column:span 3;text-align:center;padding:60px 0;color:#9ca3af;">
        No active campaigns yet. <a href="<?= BASE ?>/create-campaign.php" style="color:#FF6B4A;">Start one!</a>
      </div>
      <?php endif; ?>
    </div>
    <p id="noResults" style="display:none;text-align:center;color:#9ca3af;padding:40px 0;">No campaigns match your search.</p>
  </div>
</section>

<section style="background:#1A2A6C;padding:56px 0;">
  <div class="container" style="text-align:center;">
    <h2 style="color:#fff;font-weight:800;font-size:1.7rem;margin-bottom:12px;">Have a cause worth sharing?</h2>
    <p style="color:rgba(255,255,255,.7);margin-bottom:28px;max-width:480px;margin-left:auto;margin-right:auto;">Start your own campaign in under 2 minutes. Free to create, mobile money-first.</p>
    <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-lg">🚀 Start Your Own Campaign</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
