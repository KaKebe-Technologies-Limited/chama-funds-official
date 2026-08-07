<?php
// ============================================================
// ChamaFunds – donate.php  (Browse & donate)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle       = 'Donate – Support Causes in Uganda | ChamaFunds';
$pageDescription = 'Make a difference today. Donate to medical, education, and community campaigns via MTN Mobile Money or Airtel Money.';

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

include __DIR__ . '/includes/header.php';
?>

<section class="hero-gradient" style="padding:100px 0 60px;">
  <div class="container" style="text-align:center;">
    <div class="hero-badge" style="display:inline-flex;margin-bottom:16px;"><i class="fas fa-heart" style="color:#facc15;"></i> Find a Cause</div>
    <h1 style="font-size:clamp(1.8rem,4vw,3rem);font-weight:800;color:#fff;margin-bottom:16px;">Find a Cause You Believe In</h1>
    <p style="color:rgba(255,255,255,.8);max-width:500px;margin:0 auto 28px;font-size:1rem;">Browse active campaigns and make a real difference today with mobile money.</p>
    <div style="max-width:540px;margin:0 auto;position:relative;">
      <input type="text" id="campaignSearch" placeholder="Search campaigns…" class="form-input" style="padding-left:48px;font-size:1rem;border-radius:99px;height:52px;" />
      <i class="fas fa-search" style="position:absolute;left:18px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:1rem;"></i>
    </div>
  </div>
</section>

<section style="background:#fff;border-bottom:1px solid #e5e7eb;padding:20px 0;position:sticky;top:64px;z-index:100;">
  <div class="container">
    <div class="filter-bar" style="justify-content:center;">
      <select id="categoryFilter" class="form-input" style="max-width:180px;">
        <option value="">All Categories</option>
        <option value="family">Family</option><option value="medical">Medical</option>
        <option value="education">Education</option><option value="community">Community</option>
        <option value="business">Business</option><option value="emergency">Emergency</option>
      </select>
      <select id="countryFilter" class="form-input" style="max-width:160px;">
        <option value="">All Countries</option>
        <option value="uganda">Uganda</option><option value="kenya">Kenya</option>
        <option value="rwanda">Rwanda</option><option value="nigeria">Nigeria</option>
      </select>
      <select id="sortFilter" class="form-input" style="max-width:160px;">
        <option value="most-recent">Most Recent</option>
        <option value="most-funded">Most Funded</option>
        <option value="ending-soon">Ending Soon</option>
      </select>
    </div>
  </div>
</section>

<section class="section" style="background:#f9fafb;">
  <div class="container">
    <div id="campaignsGrid" class="campaigns-grid">
      <?php foreach ($campaigns as $c):
        $pct     = min(100, (float)$c['pct']);
        $daysLeft= (int)$c['days_left'];
        $daysStr = $daysLeft > 0 ? "$daysLeft days left" : 'Ended';
        $catLower= strtolower($c['category']);
        $image   = imgUrl($c['image_url'] ?: '');
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
          <div class="card-img-placeholder"><?= $catLower === 'medical' ? '🏥' : ($catLower === 'education' ? '📚' : ($catLower === 'emergency' ? '🆘' : '🌟')) ?></div>
        <?php endif; ?>
        <div class="card-body">
          <div class="campaign-meta">
            <span class="category-badge badge-<?= $catLower ?>"><?= htmlspecialchars($c['category']) ?></span>
            <span class="days-left" <?= $daysLeft <= 3 ? 'style="color:#ef4444;"' : '' ?>><?= $daysStr ?></span>
          </div>
          <p class="campaign-title"><?= htmlspecialchars($c['title']) ?></p>
          <p style="font-size:.78rem;color:#9ca3af;margin-bottom:8px;"><i class="fas fa-map-marker-alt" style="margin-right:4px;"></i><?= htmlspecialchars($c['country']) ?></p>
          <div class="campaign-stats">
            <span><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> raised</span>
            <span style="font-weight:700;color:#1A2A6C;"><?= $pct ?>%</span>
          </div>
          <div style="font-size:.74rem;color:#9ca3af;margin-bottom:4px;">
            Target: <strong style="color:#1A2A6C;"><?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></strong>
          </div>
          <div class="progress-wrap"><div class="progress-fill" data-width="<?= $pct ?>%"></div></div>
          <div class="campaign-footer">
            <span class="contributors-count"><i class="fas fa-users" style="margin-right:4px;"></i><?= $c['contributor_count'] ?></span>
            <span class="btn btn-primary btn-sm">Donate Now</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty($campaigns)): ?>
      <div style="grid-column:span 3;text-align:center;padding:60px 0;color:#9ca3af;">
        No active campaigns at the moment. <a href="<?= BASE ?>/create-campaign.php" style="color:#FF6B4A;">Start one!</a>
      </div>
      <?php endif; ?>
    </div>
    <p id="noResults" style="display:none;text-align:center;color:#9ca3af;padding:40px 0;">No campaigns match your search.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
