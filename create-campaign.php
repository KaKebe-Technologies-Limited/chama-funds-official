<?php
// ============================================================
// ChamaFunds – create-campaign.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

// ── Gate: must be logged in to create a campaign ─────────────
if (!isset($_SESSION['user_id'])) {
    // Store intended destination so we can redirect back after login/signup
    $_SESSION['redirect_after_auth'] = BASE . '/create-campaign.php';
    header('Location: ' . BASE . '/signup.php?next=create-campaign');
    exit;
}

$uid  = (int)$_SESSION['user_id'];
$user = $_SESSION['user'];

$pageTitle       = 'Start a Campaign – ChamaFunds';
$pageDescription = 'Launch your crowdfunding campaign in Uganda. Free to create, receive funds via MTN Mobile Money & Airtel Money.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?></title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="shortcut icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="apple-touch-icon" href="<?= BASE ?>/img/favicon.png" />
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
<style>
/* ── Page layout ──────────────────────────────────────────── */
.cc-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 80px 0 60px;
}
.cc-wrap {
  max-width: 780px;
  margin: 0 auto;
  padding: 0 20px;
}
/* ── Header ───────────────────────────────────────────────── */
.cc-header {
  text-align: center;
  margin-bottom: 36px;
}
.cc-header-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,107,74,.1); color: #FF6B4A;
  border-radius: 99px; padding: 6px 16px;
  font-size: .78rem; font-weight: 700;
  margin-bottom: 14px;
}
.cc-header h1 {
  font-size: clamp(1.5rem, 4vw, 2rem);
  font-weight: 800; color: #1A2A6C; margin-bottom: 8px;
}
.cc-header p { color: #6b7280; font-size: .92rem; max-width: 480px; margin: 0 auto; }
</style>
<style>
/* ── Card ─────────────────────────────────────────────────── */
.cc-card {
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 8px 40px rgba(26,42,108,.10);
  overflow: hidden;
}
.cc-card-header {
  background: linear-gradient(135deg, #1A2A6C 0%, #2a3f8a 100%);
  padding: 28px 36px;
  display: flex; align-items: center; gap: 16px;
}
.cc-card-header-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; flex-shrink: 0;
}
.cc-card-header h2 { color: #fff; font-weight: 800; font-size: 1.1rem; margin-bottom: 2px; }
.cc-card-header p  { color: rgba(255,255,255,.65); font-size: .82rem; }
.cc-card-body { padding: 36px; }
/* ── Form sections ────────────────────────────────────────── */
.cc-section {
  margin-bottom: 32px;
  padding-bottom: 32px;
  border-bottom: 1px solid #f1f5f9;
}
.cc-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.cc-section-title {
  display: flex; align-items: center; gap: 10px;
  font-weight: 800; color: #1A2A6C; font-size: .95rem;
  margin-bottom: 18px;
}
.cc-section-title-icon {
  width: 32px; height: 32px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem; flex-shrink: 0;
}
/* ── Fee banner ───────────────────────────────────────────── */
.cc-fee-banner {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border: 1px solid #bbf7d0; border-radius: 14px;
  padding: 14px 18px;
  display: flex; align-items: center; gap: 12px;
  margin-bottom: 28px; font-size: .84rem; color: #065f46;
}
.cc-fee-banner i { font-size: 1.1rem; color: #10b981; flex-shrink: 0; }
/* ── Image upload ─────────────────────────────────────────── */
.cc-dropzone {
  border: 2px dashed #d1d5db; border-radius: 16px;
  padding: 32px 20px; text-align: center; cursor: pointer;
  transition: all .2s; background: #fafafa;
}
.cc-dropzone:hover, .cc-dropzone.drag-over {
  border-color: #FF6B4A; background: #fff8f6;
}
.cc-dropzone.has-files { padding: 16px; }
.cc-preview-grid {
  display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-top: 12px;
}
.cc-thumb {
  position: relative; border-radius: 12px; overflow: hidden;
  aspect-ratio: 4/3; background: #f3f4f6;
  border: 2.5px solid transparent; cursor: grab;
  transition: border-color .2s;
}
.cc-thumb:first-child { border-color: #FF6B4A; }
.cc-thumb:first-child::before {
  content: 'COVER'; position: absolute; top: 6px; left: 6px;
  background: #FF6B4A; color: #fff; font-size: .6rem; font-weight: 800;
  padding: 2px 8px; border-radius: 99px; z-index: 2;
}
.cc-thumb img { width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; }
.cc-thumb-rm {
  position: absolute; top: 5px; right: 5px; z-index: 3;
  width: 22px; height: 22px; border-radius: 50%;
  background: rgba(0,0,0,.55); color: #fff; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: .65rem;
}
.cc-thumb-rm:hover { background: #ef4444; }
.cc-add-btn {
  aspect-ratio: 4/3; border-radius: 12px; border: 2px dashed #d1d5db;
  background: #f9fafb; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  font-size: .75rem; color: #9ca3af; cursor: pointer; gap: 4px; font-weight: 600;
  transition: all .2s;
}
.cc-add-btn:hover { border-color: #FF6B4A; color: #FF6B4A; background: #fff8f6; }
/* ── Submit ───────────────────────────────────────────────── */
.cc-submit-area { margin-top: 32px; }
.cc-submit-btn {
  width: 100%; padding: 16px; border-radius: 14px; border: none;
  background: linear-gradient(135deg, #FF6B4A, #e85a3a);
  color: #fff; font-size: 1rem; font-weight: 800;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
  transition: all .25s; box-shadow: 0 6px 20px rgba(255,107,74,.35);
}
.cc-submit-btn:hover:not(:disabled) {
  transform: translateY(-2px); box-shadow: 0 10px 28px rgba(255,107,74,.45);
}
.cc-submit-btn:disabled { opacity:.65; cursor:not-allowed; transform:none; }
/* ── Sidebar trust pills ──────────────────────────────────── */
.cc-trust { display: flex; flex-direction: column; gap: 10px; margin-top: 28px; }
.cc-trust-item {
  display: flex; align-items: center; gap: 10px;
  background: #fff; border-radius: 12px; padding: 12px 14px;
  box-shadow: 0 2px 8px rgba(26,42,108,.06);
  font-size: .82rem; font-weight: 600; color: #1A2A6C;
}
.cc-trust-icon { font-size: 1rem; flex-shrink: 0; }
@media (max-width: 640px) {
  .cc-card-body { padding: 24px 18px; }
  .cc-preview-grid { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>



<div class="cc-page">
  <div class="cc-wrap">

    <!-- Page header -->
    <div class="cc-header">
      <div class="cc-header-badge"><i class="fas fa-rocket"></i> Free to create</div>
      <h1>Start Your Campaign</h1>
      <p>Fill in the details below. Your campaign goes live within 48 hours after our team reviews it.</p>
    </div>

    <!-- Fee banner -->
    <div class="cc-fee-banner">
      <i class="fas fa-check-circle"></i>
      <span><strong>Free to start.</strong> A 7.5% platform fee is deducted only when you withdraw — never upfront.</span>
    </div>

    <!-- Guide banner -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:14px 18px;
                display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">
      <i class="fas fa-file-pdf" style="color:#FF6B4A;font-size:1.2rem;flex-shrink:0;"></i>
      <span style="flex:1;font-size:.84rem;color:#6b7280;min-width:160px;">
        Before you start, read the <strong style="color:#1A2A6C;">Campaigner Guide</strong> to understand how campaigns, verification and payouts work.
      </span>
      <a href="<?= BASE ?>/admin/CampaignerDetails.pdf" target="_blank"
         style="background:#1A2A6C;color:#fff;padding:8px 16px;border-radius:99px;
                font-size:.8rem;font-weight:700;text-decoration:none;white-space:nowrap;">
        <i class="fas fa-download" style="margin-right:5px;"></i>Read Guide
      </a>
    </div>

    <!-- Main card -->
    <div class="cc-card">
      <div class="cc-card-header">
        <div class="cc-card-header-icon">📋</div>
        <div>
          <h2>Campaign Details</h2>
          <p>Hi <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?> — tell us about your cause</p>
        </div>
      </div>

      <div class="cc-card-body">
        <div id="cc-msg" style="display:none;padding:14px 18px;border-radius:12px;font-size:.88rem;margin-bottom:20px;font-weight:600;"></div>

        <form id="campaignForm" enctype="multipart/form-data">

          <!-- ── SECTION 1: About the campaign ── -->
          <div class="cc-section">
            <div class="cc-section-title">
              <div class="cc-section-title-icon" style="background:rgba(26,42,108,.08);color:#1A2A6C;">
                <i class="fas fa-book-open"></i>
              </div>
              About Your Campaign
            </div>

            <div class="form-group">
              <label class="form-label">Campaign Title <span class="required">*</span></label>
              <input type="text" name="title" id="campaignTitle" class="form-input"
                     placeholder="e.g. Help Sarah Get Surgery" required />
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="form-group">
                <label class="form-label">Category <span class="required">*</span></label>
                <select name="category" id="campaignCategory" class="form-input" required>
                  <option value="">Select a category</option>
                  <option>Family</option><option>Education</option><option>Medical</option>
                  <option>Community</option><option>Business</option><option>Emergency</option><option>Other</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Country <span class="required">*</span></label>
                <select name="country" class="form-input" required>
                  <option>Uganda</option><option>Kenya</option><option>Rwanda</option>
                  <option>Tanzania</option><option>Nigeria</option><option>Ghana</option><option>Zambia</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Campaign Story <span class="required">*</span></label>
              <textarea name="description" id="campaignStory" class="form-input" rows="5"
                        placeholder="Tell your story — why are you raising funds? Be specific and personal." required></textarea>
              <p style="font-size:.74rem;color:#9ca3af;margin-top:4px;">A detailed story increases donations by up to 3×.</p>
            </div>

            <div class="form-group">
              <label class="form-label">End Date <em style="font-weight:400;color:#9ca3af;">(optional)</em></label>
              <input type="date" name="end_date" class="form-input"
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>" />
            </div>
          </div>

          <!-- ── SECTION 2: Photos ── -->
          <div class="cc-section">
            <div class="cc-section-title">
              <div class="cc-section-title-icon" style="background:rgba(255,107,74,.1);color:#FF6B4A;">
                <i class="fas fa-images"></i>
              </div>
              Campaign Photos <span style="font-size:.75rem;font-weight:400;color:#9ca3af;margin-left:6px;">Up to 6 · First = cover</span>
            </div>

            <div class="cc-dropzone" id="miDropzone">
              <input type="file" id="fileInput" name="images[]" accept="image/jpeg,image/png,image/webp" multiple style="display:none;" />
              <div id="miDropzoneInner">
                <i class="fas fa-cloud-upload-alt" style="font-size:2.4rem;color:#d1d5db;margin-bottom:10px;display:block;"></i>
                <p style="font-weight:700;color:#4b5563;font-size:.9rem;margin-bottom:4px;">
                  Drop photos here or <span style="color:#FF6B4A;cursor:pointer;" onclick="document.getElementById('fileInput').click()">browse</span>
                </p>
                <p style="font-size:.75rem;color:#9ca3af;">JPG, PNG, WEBP · Max 5 MB each · Up to 6 images</p>
              </div>
            </div>
            <div class="cc-preview-grid" id="miPreviewGrid"></div>
            <p id="miHint" style="display:none;font-size:.78rem;color:#6b7280;margin-top:8px;display:flex;align-items:center;gap:6px;">
              <i class="fas fa-star" style="color:#F59E0B;"></i>
              First image is the <strong>cover photo</strong>. Drag to reorder.
            </p>
          </div>

          <!-- ── SECTION 3: Goal & Payment ── -->
          <div class="cc-section">
            <div class="cc-section-title">
              <div class="cc-section-title-icon" style="background:rgba(16,185,129,.1);color:#10b981;">
                <i class="fas fa-hand-holding-usd"></i>
              </div>
              Goal &amp; Payout Details
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
              <div class="form-group">
                <label class="form-label">Goal Amount <span class="required">*</span></label>
                <input type="number" name="goal_amount" id="goalAmount" class="form-input"
                       placeholder="e.g. 500000" min="1000" required />
              </div>
              <div class="form-group">
                <label class="form-label">Currency</label>
                <select name="currency" class="form-input">
                  <option>UGX</option><option>KES</option><option>RWF</option><option>NGN</option><option>ZMW</option>
                </select>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="form-group">
                <label class="form-label">Mobile Money Number <span class="required">*</span></label>
                <input type="tel" name="mobile_money_number" class="form-input"
                       placeholder="256712345678"
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required />
              </div>
              <div class="form-group">
                <label class="form-label">Network <span class="required">*</span></label>
                <select name="mobile_money_network" class="form-input" required>
                  <option>MTN Mobile Money</option>
                  <option>Airtel Money</option>
                  <option>Orange Money</option>
                  <option>Safaricom M-PESA</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ── Terms & Submit ── -->
          <div class="cc-submit-area">
            <label style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;cursor:pointer;">
              <input type="checkbox" id="terms" style="margin-top:3px;width:16px;height:16px;accent-color:#FF6B4A;flex-shrink:0;" />
              <span style="font-size:.84rem;color:#6b7280;line-height:1.6;">
                I agree to the <a href="#" style="color:#FF6B4A;font-weight:600;">Terms of Service</a> and confirm all information is accurate and genuine.
                I understand my campaign will be reviewed within <strong style="color:#1A2A6C;">48 working hours</strong>.
              </span>
            </label>
            <button type="button" id="launchBtn" class="cc-submit-btn">
              <i class="fas fa-rocket" id="launchIcon"></i>
              <span id="launchText">Submit Campaign for Review</span>
            </button>
          </div>

        </form>
      </div><!-- /.cc-card-body -->
    </div><!-- /.cc-card -->

    <!-- Trust items below card -->
    <div class="cc-trust">
      <div class="cc-trust-item"><span class="cc-trust-icon">🔒</span> Your data is encrypted and never shared publicly</div>
      <div class="cc-trust-item"><span class="cc-trust-icon">⚡</span> Same-day payout after admin approval</div>
      <div class="cc-trust-item"><span class="cc-trust-icon">📱</span> Works with MTN, Airtel, M-PESA &amp; more</div>
    </div>

  </div>
</div><!-- /.cc-page -->

<script src="<?= BASE ?>/js/main.js"></script>
<script>
// ── Multi-image upload ──────────────────────────────────────
var miFiles   = [];
var MAX_IMG   = 6;
var dropzone  = document.getElementById('miDropzone');
var fileInput = document.getElementById('fileInput');
var grid      = document.getElementById('miPreviewGrid');
var miHint    = document.getElementById('miHint');

dropzone.addEventListener('click', function(e){
  if (!e.target.closest('.cc-thumb')) fileInput.click();
});
dropzone.addEventListener('dragover',  function(e){ e.preventDefault(); dropzone.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', function(){ dropzone.classList.remove('drag-over'); });
dropzone.addEventListener('drop', function(e){
  e.preventDefault(); dropzone.classList.remove('drag-over');
  addFiles(Array.from(e.dataTransfer.files));
});
fileInput.addEventListener('change', function(){
  addFiles(Array.from(this.files)); this.value='';
});

function addFiles(files){
  files.forEach(function(f){
    if (miFiles.length >= MAX_IMG){ window.showToast('Max 6 images allowed','error'); return; }
    if (!['image/jpeg','image/png','image/webp'].includes(f.type)){ window.showToast(f.name+': JPG/PNG/WEBP only','error'); return; }
    if (f.size > 5*1024*1024){ window.showToast(f.name+': exceeds 5 MB','error'); return; }
    miFiles.push(f);
  });
  renderPreviews();
}

function renderPreviews(){
  grid.innerHTML='';
  miFiles.forEach(function(file,idx){
    var t=document.createElement('div'); t.className='cc-thumb'; t.draggable=true; t.dataset.idx=idx;
    var img=document.createElement('img'); img.src=URL.createObjectURL(file); img.alt=file.name;
    var rm=document.createElement('button'); rm.type='button'; rm.className='cc-thumb-rm';
    rm.innerHTML='<i class="fas fa-times"></i>';
    rm.addEventListener('click',function(e){ e.stopPropagation(); miFiles.splice(idx,1); renderPreviews(); });
    t.appendChild(img); t.appendChild(rm); grid.appendChild(t);
    t.addEventListener('dragstart',function(e){ e.dataTransfer.setData('text/plain',idx); t.style.opacity='.4'; });
    t.addEventListener('dragend',function(){ t.style.opacity='1'; });
    t.addEventListener('dragover',function(e){ e.preventDefault(); t.style.outline='2px solid #FF6B4A'; });
    t.addEventListener('dragleave',function(){ t.style.outline=''; });
    t.addEventListener('drop',function(e){
      e.preventDefault(); t.style.outline='';
      var from=parseInt(e.dataTransfer.getData('text/plain'));
      var to=parseInt(t.dataset.idx); if(from===to) return;
      miFiles.splice(to,0,miFiles.splice(from,1)[0]); renderPreviews();
    });
  });
  if (miFiles.length < MAX_IMG){
    var a=document.createElement('div'); a.className='cc-add-btn';
    a.innerHTML='<i class="fas fa-plus"></i><span>Add photo</span>';
    a.addEventListener('click',function(){ fileInput.click(); });
    grid.appendChild(a);
  }
  var inner=document.getElementById('miDropzoneInner');
  if (miFiles.length > 0){
    dropzone.classList.add('has-files'); inner.style.display='none'; miHint.style.display='flex';
  } else {
    dropzone.classList.remove('has-files'); inner.style.display='block'; miHint.style.display='none';
  }
}

// ── Submit ──────────────────────────────────────────────────
document.getElementById('launchBtn').addEventListener('click', async function(){
  var btn=this, icon=document.getElementById('launchIcon'), text=document.getElementById('launchText');
  var msg=document.getElementById('cc-msg');

  // Validate
  if (!document.getElementById('campaignTitle').value.trim()) return window.showToast('Enter a campaign title','error');
  if (!document.getElementById('campaignCategory').value) return window.showToast('Select a category','error');
  if (!document.getElementById('campaignStory').value.trim()) return window.showToast('Write your campaign story','error');
  if (!miFiles.length) return window.showToast('Add at least one photo','error');
  if (!document.getElementById('goalAmount').value || Number(document.getElementById('goalAmount').value) < 1000)
    return window.showToast('Enter a valid goal amount (min 1,000)','error');
  if (!document.getElementById('terms').checked)
    return window.showToast('Please accept the Terms of Service','error');

  btn.disabled=true; icon.className='fas fa-spinner fa-spin'; text.textContent='Submitting…';
  msg.style.display='none';

  // Inject images into the file input so FormData picks them up
  var dt=new DataTransfer();
  miFiles.forEach(function(f){ dt.items.add(f); });
  fileInput.files=dt.files;

  var fd=new FormData(document.getElementById('campaignForm'));
  fd.append('action','create');

  try {
    var res=await fetch('<?= BASE ?>/api/campaigns.php?action=create',{method:'POST',body:fd});
    var data=await res.json();
    if (data.success){
      msg.style.cssText='display:block;background:#d1fae5;color:#065f46;padding:14px 18px;border-radius:12px;font-size:.9rem;font-weight:600;margin-bottom:16px;';
      msg.innerHTML='<i class="fas fa-check-circle" style="margin-right:8px;"></i>' + data.message;
      msg.scrollIntoView({behavior:'smooth',block:'center'});
      
      // ============================================================
      // NOTIFICATIONS ARE NOW HANDLED ON THE SERVER SIDE
      // The API endpoint will call notifyNewCampaign()
      // ============================================================
      
      setTimeout(function(){ window.location.href='<?= BASE ?>/dashboard.php'; }, 2500);
    } else {
      window.showToast(data.message || 'Something went wrong','error');
      btn.disabled=false; icon.className='fas fa-rocket'; text.textContent='Submit Campaign for Review';
    }
  } catch(e){
    window.showToast('Network error. Please try again.','error');
    btn.disabled=false; icon.className='fas fa-rocket'; text.textContent='Submit Campaign for Review';
  }
});
</script>
</body>
</html>