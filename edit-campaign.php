<?php
// ============================================================
// ChamaFunds – edit-campaign.php
// Edit an existing campaign (owner or admin only)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE . '/login.php?msg=unauthorized'); exit;
}

// $conn is set by config.php
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];
$cid  = (int)($_GET['id'] ?? 0);

if ($cid <= 0) {
    header('Location: ' . BASE . '/dashboard.php'); exit;
}

// Load the campaign
$result = $conn->query(
    "SELECT * FROM campaigns WHERE campaign_id = $cid LIMIT 1"
);
if (!$result || $result->num_rows === 0) {
    header('Location: ' . BASE . '/dashboard.php'); exit;
}
$c = $result->fetch_assoc();

// Ownership / permission check
if ($role !== 'admin' && $c['campaigner_id'] != $uid) {
    $permError = true;
}

$successMsg = '';
$errorMsg   = '';

// ── Handle form submission ──────────────────────────────────
if (!isset($permError) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $title    = trim($_POST['title']    ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $goal     = (float)($_POST['goal_amount'] ?? 0);
    $currency = $conn->real_escape_string(trim($_POST['currency'] ?? 'UGX'));
    $momoNum  = $conn->real_escape_string(trim($_POST['mobile_money_number'] ?? ''));
    $momoNet  = $conn->real_escape_string(trim($_POST['mobile_money_network'] ?? ''));
    $country  = $conn->real_escape_string(trim($_POST['country'] ?? ''));
    $endDate  = trim($_POST['end_date'] ?? '');

    if (!$title || !$desc || !$category || $goal < 1000 || !$momoNum || !$momoNet) {
        $errorMsg = 'Please fill in all required fields. Goal amount minimum is 1,000.';
    } else {
        $titleEsc  = $conn->real_escape_string($title);
        $descEsc   = $conn->real_escape_string($desc);
        $catEsc    = $conn->real_escape_string($category);
        $endSql    = $endDate ? "'" . $conn->real_escape_string($endDate) . "'" : 'NULL';

        // Photos are managed separately (see the gallery manager below) —
        // this form only ever touches the campaign's text/details fields.
        $conn->query(
            "UPDATE campaigns SET
                title                = '$titleEsc',
                description          = '$descEsc',
                category             = '$catEsc',
                goal_amount          = $goal,
                currency             = '$currency',
                mobile_money_number  = '$momoNum',
                mobile_money_network = '$momoNet',
                country              = '$country',
                end_date             = $endSql,
                updated_at           = NOW()
             WHERE campaign_id = $cid"
        );

        // Refresh campaign data for the form
        $c = $conn->query("SELECT * FROM campaigns WHERE campaign_id = $cid LIMIT 1")->fetch_assoc();
        $successMsg = '✅ Campaign updated successfully!';
    }
}

// ── Gallery photos ────────────────────────────────────────────
// Backfill: older campaigns saved before the gallery table existed
// only have campaigns.image_url set, with no campaign_images row.
if (!isset($permError)) {
    $galleryCount = (int)$conn->query(
        "SELECT COUNT(*) FROM campaign_images WHERE campaign_id = $cid"
    )->fetch_row()[0];
    if ($galleryCount === 0 && !empty($c['image_url'])) {
        $legacyUrlEsc = $conn->real_escape_string($c['image_url']);
        $conn->query(
            "INSERT INTO campaign_images (campaign_id, image_url, is_cover, sort_order)
             VALUES ($cid, '$legacyUrlEsc', 1, 0)"
        );
    }
    $galleryResult = $conn->query(
        "SELECT image_id, image_url, is_cover FROM campaign_images
         WHERE campaign_id = $cid ORDER BY sort_order ASC, image_id ASC"
    );
    $galleryImages = [];
    while ($gi = $galleryResult->fetch_assoc()) $galleryImages[] = $gi;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Campaign – ChamaFunds</title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="shortcut icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="apple-touch-icon" href="<?= BASE ?>/img/favicon.png" />
  <meta name="robots" content="noindex,nofollow" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css?v=<?= @filemtime(__DIR__ . '/css/style.css') ?: time() ?>" />
</head>
<body>

<!-- Mobile Top Bar -->
<nav style="background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 20px;display:none;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:500;" id="mobileTopBar">
  <a href="<?= BASE ?>/index.php" style="display:flex;align-items:center;gap:8px;"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;font-size:.9rem;">ChamaFunds</span></a>
  <button id="mobileSidebarToggle" style="font-size:1.3rem;color:#6b7280;"><i class="fas fa-bars"></i></button>
</nav>
<div class="modal-overlay" id="sidebarOverlay" style="z-index:899;"></div>

<div class="dashboard-layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;">ChamaFunds</span></div>
    <nav class="sidebar-nav">
      <a href="<?= BASE ?>/dashboard.php" class="sidebar-link"><i class="fas fa-th-large"></i>Dashboard</a>
      <a href="<?= BASE ?>/create-campaign.php" class="sidebar-link"><i class="fas fa-plus-circle"></i>Create Campaign</a>
      <a href="<?= BASE ?>/withdraw.php" class="sidebar-link"><i class="fas fa-credit-card"></i>Withdrawals</a>
      <a href="<?= BASE ?>/profile.php" class="sidebar-link"><i class="fas fa-cog"></i>Settings</a>
      <?php if ($role === 'admin'): ?>
      <a href="<?= BASE ?>/admin/index.php" class="sidebar-link" style="color:#FF6B4A;"><i class="fas fa-shield-alt"></i>Admin Panel</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= BASE ?>/api/auth.php?action=logout" class="sidebar-link logout-link"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <!-- Breadcrumb -->
    <div style="font-size:.82rem;color:#9ca3af;margin-bottom:20px;">
      <a href="<?= BASE ?>/dashboard.php" style="color:#FF6B4A;">Dashboard</a>
      <span style="margin:0 8px;">›</span>
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= $cid ?>" style="color:#FF6B4A;">Campaign</a>
      <span style="margin:0 8px;">›</span>
      <span>Edit</span>
    </div>

    <div class="page-header">
      <h1>Edit Campaign</h1>
      <p>Update your campaign details. Changes are saved immediately.</p>
    </div>

    <?php if (isset($permError)): ?>
    <!-- Permission denied -->
    <div class="card" style="padding:48px;text-align:center;max-width:520px;margin:0 auto;">
      <div style="font-size:3rem;margin-bottom:16px;">🔒</div>
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:8px;">Access Denied</h2>
      <p style="color:#9ca3af;margin-bottom:24px;">You don't have permission to edit this campaign.</p>
      <a href="<?= BASE ?>/dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
    </div>

    <?php else: ?>

    <?php if ($successMsg): ?>
    <div style="background:#d1fae5;color:#065f46;padding:14px 20px;border-radius:12px;font-size:.92rem;font-weight:600;margin-bottom:24px;display:flex;align-items:center;gap:10px;">
      <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
      <a href="<?= BASE ?>/dashboard.php" style="margin-left:auto;font-size:.82rem;color:#065f46;text-decoration:underline;">Back to dashboard →</a>
    </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:14px 20px;border-radius:12px;font-size:.92rem;font-weight:600;margin-bottom:24px;display:flex;align-items:center;gap:10px;">
      <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start;max-width:960px;">

      <!-- FORM -->
      <div class="card" style="padding:36px;">
        <form method="POST" enctype="multipart/form-data" id="editForm">

          <!-- Title -->
          <div class="form-group">
            <label class="form-label">Campaign Title <span class="required">*</span></label>
            <input type="text" name="title" class="form-input"
                   value="<?= htmlspecialchars($c['title']) ?>" required />
          </div>

          <!-- Category -->
          <div class="form-group">
            <label class="form-label">Category <span class="required">*</span></label>
            <select name="category" class="form-input" required>
              <?php foreach (['Family','Education','Medical','Community','Business','Emergency','Other'] as $cat): ?>
              <option <?= $c['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label">Campaign Story <span class="required">*</span></label>
            <textarea name="description" class="form-input" rows="7" required><?= htmlspecialchars($c['description']) ?></textarea>
          </div>

          <!-- Goal + Currency -->
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Goal Amount <span class="required">*</span></label>
              <input type="number" name="goal_amount" class="form-input"
                     value="<?= $c['goal_amount'] ?>" min="1000" required />
            </div>
            <div class="form-group">
              <label class="form-label">Currency</label>
              <select name="currency" class="form-input">
                <?php foreach (['UGX','KES','RWF','NGN','ZMW','XOF'] as $cur): ?>
                <option <?= $c['currency'] === $cur ? 'selected' : '' ?>><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Mobile Money -->
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Mobile Money Number <span class="required">*</span></label>
              <input type="tel" name="mobile_money_number" class="form-input"
                     value="<?= htmlspecialchars($c['mobile_money_number']) ?>" required />
            </div>
            <div class="form-group">
              <label class="form-label">Network <span class="required">*</span></label>
              <select name="mobile_money_network" class="form-input" required>
                <?php foreach (['MTN Mobile Money','Airtel Money','Orange Money','Safaricom M-PESA','Tigo Cash'] as $net): ?>
                <option <?= $c['mobile_money_network'] === $net ? 'selected' : '' ?>><?= $net ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Country + End Date -->
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Country</label>
              <select name="country" class="form-input">
                <?php foreach (['Uganda','Kenya','Rwanda','Nigeria','Zambia','Senegal','DR Congo'] as $ctry): ?>
                <option <?= $c['country'] === $ctry ? 'selected' : '' ?>><?= $ctry ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">End Date</label>
              <input type="date" name="end_date" class="form-input"
                     value="<?= $c['end_date'] ? date('Y-m-d', strtotime($c['end_date'])) : '' ?>"
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>" />
            </div>
          </div>

          <!-- Action Buttons -->
          <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
            <button type="submit" id="saveBtn" class="btn btn-primary btn-lg" style="flex:1;justify-content:center;">
              <i class="fas fa-save" style="margin-right:8px;"></i>Save Changes
            </button>
            <a href="<?= BASE ?>/dashboard.php" class="btn btn-outline">Cancel</a>
            <a href="<?= BASE ?>/campaign-detail.php?id=<?= $cid ?>" class="btn btn-secondary btn-sm" target="_blank">
              <i class="fas fa-eye" style="margin-right:6px;"></i>Preview
            </a>
          </div>

        </form>
      </div>

      <!-- CAMPAIGN PHOTOS -->
      <div class="card" style="padding:36px;grid-column:1;margin-top:24px;">
        <label class="form-label" style="margin-bottom:4px;display:block;">Campaign Photos</label>
        <p style="font-size:.78rem;color:#9ca3af;margin-bottom:16px;">The first photo is used as the cover image. Deleting the cover promotes the next photo automatically. Changes save immediately — no need to click "Save Changes".</p>

        <div id="galleryGrid" class="ec-gallery-grid">
          <?php foreach ($galleryImages as $gi): ?>
          <div class="ec-gallery-item" data-image-id="<?= (int)$gi['image_id'] ?>">
            <img src="<?= htmlspecialchars(imgUrl($gi['image_url'])) ?>" alt="" />
            <span class="ec-gallery-cover-badge<?= $gi['is_cover'] ? '' : ' hidden' ?>">Cover</span>
            <button type="button" class="ec-gallery-delete" title="Delete photo"><i class="fas fa-times"></i></button>
          </div>
          <?php endforeach; ?>
        </div>

        <div id="galleryEmpty" class="<?= empty($galleryImages) ? '' : 'hidden' ?>" style="background:#f9fafb;border-radius:12px;padding:16px;text-align:center;color:#9ca3af;font-size:.85rem;margin:12px 0;">
          <i class="fas fa-image" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;"></i>No photos uploaded yet
        </div>

        <div class="file-upload-area" id="addPhotosArea" style="margin-top:12px;">
          <i class="fas fa-cloud-upload-alt" style="font-size:1.6rem;color:#d1d5db;"></i>
          <p style="font-size:.85rem;color:#9ca3af;margin-top:6px;">Click to add photos</p>
          <p style="font-size:.75rem;color:#d1d5db;">PNG, JPG, WEBP — max 5MB each · multiple allowed</p>
          <input type="file" id="addPhotosInput" accept="image/*" multiple style="display:none;" />
        </div>
        <p id="galleryStatus" style="font-size:.8rem;margin-top:10px;"></p>
      </div>

      <!-- SIDEBAR INFO -->
      <div style="display:flex;flex-direction:column;gap:16px;position:sticky;top:24px;">

        <!-- Campaign status -->
        <div class="card" style="padding:20px;">
          <p style="font-weight:700;color:#1A2A6C;font-size:.88rem;margin-bottom:12px;">Campaign Status</p>
          <span class="status-badge status-<?= $c['status'] ?>" style="font-size:.82rem;">
            <?= ucfirst($c['status']) ?>
          </span>
          <p style="font-size:.78rem;color:#9ca3af;margin-top:10px;">
            <?php if ($c['status'] === 'draft'): ?>
            Campaign is pending admin review before going live.
            <?php elseif ($c['status'] === 'active'): ?>
            Campaign is live and accepting donations.
            <?php elseif ($c['status'] === 'paused'): ?>
            Campaign is paused. Contact support to resume.
            <?php else: ?>
            Campaign is <?= $c['status'] ?>.
            <?php endif; ?>
          </p>
        </div>

        <!-- Current progress -->
        <div class="card" style="padding:20px;">
          <p style="font-weight:700;color:#1A2A6C;font-size:.88rem;margin-bottom:12px;">Current Progress</p>
          <p style="font-size:1.3rem;font-weight:800;color:#10b981;"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?></p>
          <p style="font-size:.78rem;color:#9ca3af;">raised of <?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></p>
          <div class="progress-wrap" style="margin:10px 0;">
            <div class="progress-fill" data-width="<?= min(100, round(($c['raised_amount']/$c['goal_amount'])*100, 1)) ?>%"></div>
          </div>
          <p style="font-size:.78rem;color:#9ca3af;"><?= $c['contributor_count'] ?> contributors</p>
        </div>

        <!-- Warning note -->
        <div class="card" style="padding:16px;background:#fff5f3;border-color:#ffe4dd;">
          <p style="font-size:.78rem;color:#92400e;line-height:1.6;">
            <i class="fas fa-info-circle" style="margin-right:6px;color:#FF6B4A;"></i>
            You can update campaign details at any time. The goal amount, mobile money number, and category changes take effect immediately.
          </p>
        </div>

      </div>
    </div>
    <?php endif; ?>
  </main>
</div>

<style>
@media(max-width:1023px){
  .sidebar{display:none;}
  .sidebar.mobile-open{display:flex;position:fixed;left:0;top:0;bottom:0;z-index:900;}
}
@media(max-width:767px){
  div[style*="grid-template-columns:1fr 280px"]{display:block!important;}
  div[style*="grid-template-columns:1fr 280px"] > div:not(:first-child){margin-top:16px;}
}

/* ── Campaign photo gallery manager ────────────────────────── */
.ec-gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
  gap: 10px;
}
.ec-gallery-item {
  position: relative;
  aspect-ratio: 1 / 1;
  border-radius: 10px;
  overflow: hidden;
  background: #f3f4f6;
}
.ec-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ec-gallery-cover-badge {
  position: absolute; top: 6px; left: 6px;
  background: rgba(26,42,108,.85); color: #fff;
  font-size: .62rem; font-weight: 700; padding: 2px 8px;
  border-radius: 99px; letter-spacing: .03em;
}
.ec-gallery-delete {
  position: absolute; top: 6px; right: 6px;
  width: 22px; height: 22px; border-radius: 50%;
  background: rgba(0,0,0,.55); color: #fff; border: none;
  font-size: .68rem; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: background .15s;
}
.ec-gallery-delete:hover { background: #ef4444; }
.ec-gallery-item.ec-uploading { opacity: .5; }
</style>

<script src="<?= BASE ?>/js/main.js"></script>
<script>
// Mobile layout
var mobileBar = document.getElementById('mobileTopBar');
function checkMobile(){ mobileBar.style.display = window.innerWidth < 1024 ? 'flex' : 'none'; }
checkMobile(); window.addEventListener('resize', checkMobile);
document.querySelector('.dashboard-layout').style.paddingTop = window.innerWidth < 1024 ? '60px' : '0';

// ── Campaign photo gallery manager (AJAX — independent of Save Changes) ──
var galleryGrid   = document.getElementById('galleryGrid');
var galleryEmpty  = document.getElementById('galleryEmpty');
var galleryStatus = document.getElementById('galleryStatus');
var addPhotosArea = document.getElementById('addPhotosArea');
var addPhotosInput= document.getElementById('addPhotosInput');
var CAMPAIGN_ID   = <?= (int)$cid ?>;

function setGalleryStatus(msg, isError) {
  galleryStatus.textContent = msg || '';
  galleryStatus.style.color = isError ? '#ef4444' : '#10b981';
}

function updateGalleryEmptyState() {
  var hasItems = galleryGrid.querySelector('.ec-gallery-item') !== null;
  galleryEmpty.classList.toggle('hidden', hasItems);
}

function buildGalleryItem(img) {
  var div = document.createElement('div');
  div.className = 'ec-gallery-item';
  div.dataset.imageId = img.image_id;
  div.innerHTML =
    '<img src="' + img.image_url + '" alt="" />' +
    '<span class="ec-gallery-cover-badge' + (img.is_cover ? '' : ' hidden') + '">Cover</span>' +
    '<button type="button" class="ec-gallery-delete" title="Delete photo"><i class="fas fa-times"></i></button>';
  return div;
}

// Delete a photo (event delegation — works for existing and newly-added tiles)
galleryGrid.addEventListener('click', function(e) {
  var btn = e.target.closest('.ec-gallery-delete');
  if (!btn) return;
  var item = btn.closest('.ec-gallery-item');
  var imageId = item.dataset.imageId;
  if (!confirm('Delete this photo? This cannot be undone.')) return;

  item.classList.add('ec-uploading');
  btn.disabled = true;

  var fd = new FormData();
  fd.append('action', 'delete_image');
  fd.append('image_id', imageId);

  fetch('<?= BASE ?>/api/campaigns.php?action=delete_image', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.success) {
        setGalleryStatus(data.message || 'Could not delete photo.', true);
        item.classList.remove('ec-uploading');
        btn.disabled = false;
        return;
      }
      item.remove();
      if (data.new_cover) {
        var promoted = galleryGrid.querySelector('[data-image-id="' + data.new_cover.image_id + '"]');
        var badge = promoted ? promoted.querySelector('.ec-gallery-cover-badge') : null;
        if (badge) badge.classList.remove('hidden');
      }
      setGalleryStatus('Photo deleted.', false);
      updateGalleryEmptyState();
    })
    .catch(function() {
      setGalleryStatus('Network error — please try again.', true);
      item.classList.remove('ec-uploading');
      btn.disabled = false;
    });
});

// Add new photos
addPhotosArea?.addEventListener('click', function(){ addPhotosInput.click(); });
addPhotosArea?.addEventListener('dragover', function(e){ e.preventDefault(); addPhotosArea.classList.add('dragover'); });
addPhotosArea?.addEventListener('dragleave', function(){ addPhotosArea.classList.remove('dragover'); });
addPhotosArea?.addEventListener('drop', function(e){
  e.preventDefault(); addPhotosArea.classList.remove('dragover');
  if (e.dataTransfer.files.length) uploadPhotos(e.dataTransfer.files);
});
addPhotosInput?.addEventListener('change', function(e){
  if (e.target.files.length) uploadPhotos(e.target.files);
});

function uploadPhotos(fileList) {
  var fd = new FormData();
  fd.append('campaign_id', CAMPAIGN_ID);
  var validCount = 0;
  for (var i = 0; i < fileList.length; i++) {
    var f = fileList[i];
    if (!f.type.startsWith('image/')) continue;
    if (f.size > 5 * 1024 * 1024) { setGalleryStatus('"' + f.name + '" is over 5MB and was skipped.', true); continue; }
    fd.append('images[]', f);
    validCount++;
  }
  if (!validCount) return;

  setGalleryStatus('Uploading ' + validCount + ' photo' + (validCount > 1 ? 's' : '') + '…', false);
  addPhotosArea.style.opacity = '.6';

  fetch('<?= BASE ?>/api/campaigns.php?action=add_images', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      addPhotosArea.style.opacity = '1';
      addPhotosInput.value = '';
      if (!data.success) {
        setGalleryStatus(data.message || 'Upload failed.', true);
        return;
      }
      data.images.forEach(function(img) {
        if (img.is_cover) {
          galleryGrid.querySelectorAll('.ec-gallery-cover-badge').forEach(function(b){ b.classList.add('hidden'); });
        }
        galleryGrid.appendChild(buildGalleryItem(img));
      });
      setGalleryStatus('Added ' + data.images.length + ' photo' + (data.images.length > 1 ? 's' : '') + '.', false);
      updateGalleryEmptyState();
    })
    .catch(function() {
      addPhotosArea.style.opacity = '1';
      setGalleryStatus('Network error — please try again.', true);
    });
}

// Save button loading state
document.getElementById('editForm')?.addEventListener('submit', function(){
  var btn = document.getElementById('saveBtn');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>Saving…';
  btn.disabled = true;
});

<?php if ($successMsg): ?>
// Auto-redirect to dashboard after success
setTimeout(function(){ window.location.href='<?= BASE ?>/dashboard.php'; }, 2500);
<?php endif; ?>
</script>

</body>
</html>
