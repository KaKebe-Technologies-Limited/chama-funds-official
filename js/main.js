/* ============================================================
   ChamaFunds – main.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── 1. MOBILE MENU TOGGLE ─────────────────────────────────── */
  const hamburger   = document.getElementById('hamburger');
  const mobileMenu  = document.getElementById('mobileMenu');
  const menuOverlay = document.getElementById('menuOverlay');
  const menuClose   = document.getElementById('menuClose');

  function openMenu()  { mobileMenu?.classList.add('open'); menuOverlay?.classList.add('open'); }
  function closeMenu() { mobileMenu?.classList.remove('open'); menuOverlay?.classList.remove('open'); }

  hamburger?.addEventListener('click', openMenu);
  menuClose?.addEventListener('click', closeMenu);
  menuOverlay?.addEventListener('click', closeMenu);

  /* ── 2. USER DROPDOWN ──────────────────────────────────────── */
  const userTrigger  = document.getElementById('userMenuTrigger');
  const userDropdown = document.getElementById('userDropdown');

  userTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    userDropdown?.classList.toggle('open');
  });
  document.addEventListener('click', () => userDropdown?.classList.remove('open'));

  /* ── 3. FAQ ACCORDION ──────────────────────────────────────── */
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const isOpen = item.classList.contains('open');
      // close all
      document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  /* ── 4. FEE CALCULATOR ─────────────────────────────────────── */
  const feeSlider    = document.getElementById('feeSlider');
  const feeDisplayEl = document.getElementById('feeDisplay');
  const netDisplayEl = document.getElementById('netDisplay');
  const feeAmtEl     = document.getElementById('feeAmount');

  function updateFeeCalc() {
    if (!feeSlider) return;
    const amt  = parseInt(feeSlider.value);
    const fee  = Math.round(amt * 0.075);
    const net  = amt - fee;
    if (feeDisplayEl) feeDisplayEl.textContent = 'UGX ' + amt.toLocaleString();
    if (netDisplayEl) netDisplayEl.textContent = 'UGX ' + net.toLocaleString();
    if (feeAmtEl)     feeAmtEl.textContent     = 'UGX ' + fee.toLocaleString();
  }
  feeSlider?.addEventListener('input', updateFeeCalc);
  updateFeeCalc();

  /* ── 5. PROGRESS BAR SCROLL ANIMATION ─────────────────────── */
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const fill = entry.target;
        fill.style.width = fill.dataset.width || '0%';
        observer.unobserve(fill);
      }
    });
  }, { threshold: 0.2 });

  document.querySelectorAll('.progress-fill[data-width]').forEach(el => {
    el.style.width = '0%';
    observer.observe(el);
  });

  /* ── 6. QUICK AMOUNT BUTTONS ───────────────────────────────── */
  const amountInput = document.getElementById('contributionAmount');
  document.querySelectorAll('.quick-amount-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.quick-amount-btn').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      if (amountInput) amountInput.value = btn.dataset.amount;
    });
  });

  /* ── 7. DONATION MODAL ─────────────────────────────────────── */
  const donateBtn    = document.getElementById('donateBtn');
  const donationModal = document.getElementById('donationModal');
  const modalAmountSpan = document.getElementById('modalAmount');

  donateBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    const amt = amountInput?.value || '0';
    if (!validateDonationForm()) return;
    if (modalAmountSpan) modalAmountSpan.textContent = 'UGX ' + Number(amt).toLocaleString();
    openModal('donationModal');
  });

  /* ── 8. GENERIC MODAL OPEN/CLOSE ───────────────────────────── */
  function openModal(id) {
    document.getElementById(id)?.classList.add('open');
  }
  function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
  }

  // Close buttons with [data-close-modal]
  document.querySelectorAll('[data-close-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
  });

  // Click overlay to close
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) overlay.classList.remove('open');
    });
  });

  /* ── 9. SHARE MODAL ────────────────────────────────────────── */
  const shareBtn = document.getElementById('shareCampaignBtn');
  shareBtn?.addEventListener('click', () => openModal('shareModal'));

  // Copy link
  const copyLinkBtn = document.getElementById('copyLinkBtn');
  copyLinkBtn?.addEventListener('click', () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
      copyLinkBtn.classList.add('copied');
      copyLinkBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
      showToast('Link copied to clipboard!', 'success');
      setTimeout(() => {
        copyLinkBtn.classList.remove('copied');
        copyLinkBtn.innerHTML = '<i class="fas fa-link"></i> Copy Link';
      }, 2500);
    });
  });

  // WhatsApp share
  document.getElementById('shareWhatsApp')?.addEventListener('click', () => {
    const title = document.querySelector('.campaign-title-heading')?.textContent || 'Campaign';
    const url = window.location.href;
    window.open(`https://wa.me/?text=${encodeURIComponent(title + ' – Support this cause: ' + url)}`, '_blank');
  });

  // Facebook share
  document.getElementById('shareFacebook')?.addEventListener('click', () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, '_blank');
  });

  // Twitter share
  document.getElementById('shareTwitter')?.addEventListener('click', () => {
    const title = document.querySelector('.campaign-title-heading')?.textContent || 'Campaign';
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(window.location.href)}`, '_blank');
  });

  /* ── 10. ANONYMOUS TOGGLE ──────────────────────────────────── */
  const anonToggle   = document.getElementById('anonymousToggle');
  const nameField    = document.getElementById('donorNameField');
  const emailField   = document.getElementById('donorEmailField');

  anonToggle?.addEventListener('change', () => {
    const hidden = anonToggle.checked;
    if (nameField)  nameField.style.opacity  = hidden ? '.4' : '1';
    if (emailField) emailField.style.opacity = hidden ? '.4' : '1';
    const nameInput  = nameField?.querySelector('input');
    const emailInput = emailField?.querySelector('input');
    if (nameInput)  nameInput.disabled  = hidden;
    if (emailInput) emailInput.disabled = hidden;
  });

  /* ── 11. FORM VALIDATION ───────────────────────────────────── */
  function validateDonationForm() {
    let valid = true;
    const phone = document.getElementById('donorPhone');
    if (phone && !phone.value.trim()) {
      showFieldError(phone, 'Phone number is required'); valid = false;
    }
    const anonOn = anonToggle?.checked;
    if (!anonOn) {
      const name = document.getElementById('donorName');
      if (name && !name.value.trim()) {
        showFieldError(name, 'Name is required'); valid = false;
      }
    }
    const amount = document.getElementById('contributionAmount');
    if (amount && (!amount.value || Number(amount.value) < 1000)) {
      showFieldError(amount, 'Minimum contribution is UGX 1,000'); valid = false;
    }
    return valid;
  }

  function showFieldError(input, msg) {
    input.classList.add('error');
    let err = input.parentElement.querySelector('.form-error');
    if (!err) { err = document.createElement('p'); err.className = 'form-error'; input.parentElement.appendChild(err); }
    err.textContent = msg; err.classList.add('visible');
    input.addEventListener('input', () => {
      input.classList.remove('error'); err.classList.remove('visible');
    }, { once: true });
  }

  // Generic form validation for all forms with [data-validate]
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
          showFieldError(field, 'This field is required');
          valid = false;
        }
        // email check
        if (field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
          showFieldError(field, 'Enter a valid email address');
          valid = false;
        }
        // password match
        if (field.id === 'confirmPassword') {
          const pw = document.getElementById('password');
          if (pw && pw.value !== field.value) {
            showFieldError(field, 'Passwords do not match');
            valid = false;
          }
        }
      });
      if (valid) {
        const successMsg = form.dataset.successMsg || 'Saved successfully!';
        showToast(successMsg, 'success');
      }
    });
  });

  /* ── 12. TOAST ─────────────────────────────────────────────── */
  function showToast(msg, type = '') {
    let toast = document.getElementById('globalToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'globalToast';
      toast.className = 'toast';
      document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.className = `toast ${type}`;
    void toast.offsetWidth;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
  }
  window.showToast = showToast;

  /* ── 13. SEARCH / FILTER (Campaign Drives & Donate page) ───── */
  const searchInput  = document.getElementById('campaignSearch');
  const categoryFilter = document.getElementById('categoryFilter');
  const countryFilter  = document.getElementById('countryFilter');
  const sortFilter     = document.getElementById('sortFilter');
  const campaignCards  = document.querySelectorAll('.filterable-card');

  function filterCampaigns() {
    const query    = searchInput?.value.toLowerCase() || '';
    const category = categoryFilter?.value || '';
    const country  = countryFilter?.value  || '';

    let cards = [...campaignCards];

    // Sort
    if (sortFilter) {
      const sort = sortFilter.value;
      cards.sort((a, b) => {
        if (sort === 'most-funded') {
          return (parseFloat(b.dataset.pct) || 0) - (parseFloat(a.dataset.pct) || 0);
        }
        if (sort === 'ending-soon') {
          return (parseInt(a.dataset.days) || 999) - (parseInt(b.dataset.days) || 999);
        }
        return 0; // most-recent = original order
      });
      const grid = document.getElementById('campaignsGrid');
      if (grid) cards.forEach(c => grid.appendChild(c));
    }

    campaignCards.forEach(card => {
      const title    = card.dataset.title?.toLowerCase()    || '';
      const cat      = card.dataset.category?.toLowerCase() || '';
      const cntry    = card.dataset.country?.toLowerCase()  || '';
      const matchSearch   = !query    || title.includes(query);
      const matchCategory = !category || cat   === category.toLowerCase();
      const matchCountry  = !country  || cntry === country.toLowerCase();
      card.style.display  = matchSearch && matchCategory && matchCountry ? '' : 'none';
    });

    // Show "no results" message
    const noResults = document.getElementById('noResults');
    if (noResults) {
      const visible = [...campaignCards].filter(c => c.style.display !== 'none').length;
      noResults.style.display = visible === 0 ? 'block' : 'none';
    }
  }

  searchInput?.addEventListener('input', filterCampaigns);
  categoryFilter?.addEventListener('change', filterCampaigns);
  countryFilter?.addEventListener('change', filterCampaigns);
  sortFilter?.addEventListener('change', filterCampaigns);

  /* ── 14. ADMIN TABS ────────────────────────────────────────── */
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById(target)?.classList.add('active');
    });
  });

  /* ── 15. MOBILE SIDEBAR (dashboard / admin) ─────────────────── */
  const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
  const dashSidebar         = document.querySelector('.sidebar, .admin-sidebar');
  const sidebarOverlay      = document.getElementById('sidebarOverlay');

  mobileSidebarToggle?.addEventListener('click', () => {
    dashSidebar?.classList.add('mobile-open');
    sidebarOverlay?.classList.add('open');
  });
  sidebarOverlay?.addEventListener('click', () => {
    dashSidebar?.classList.remove('mobile-open');
    sidebarOverlay?.classList.remove('open');
  });

  /* ── 16. FILE UPLOAD PREVIEW ───────────────────────────────── */
  const fileArea    = document.getElementById('fileUploadArea');
  const fileInput   = document.getElementById('fileInput');
  const filePreview = document.getElementById('filePreview');
  const previewImg  = document.getElementById('previewImage');
  const removeFile  = document.getElementById('removeFile');

  fileArea?.addEventListener('click', () => fileInput?.click());
  fileArea?.addEventListener('dragover', (e) => { e.preventDefault(); fileArea.classList.add('dragover'); });
  fileArea?.addEventListener('dragleave', () => fileArea.classList.remove('dragover'));
  fileArea?.addEventListener('drop', (e) => { e.preventDefault(); fileArea.classList.remove('dragover'); handleFile(e.dataTransfer.files[0]); });
  fileInput?.addEventListener('change', (e) => { if (e.target.files[0]) handleFile(e.target.files[0]); });

  function handleFile(file) {
    if (!file.type.startsWith('image/')) { showToast('Please upload an image file', 'error'); return; }
    if (file.size > 5 * 1024 * 1024)    { showToast('File size must be under 5MB', 'error'); return; }
    const reader = new FileReader();
    reader.onload = (e) => {
      if (previewImg) previewImg.src = e.target.result;
      filePreview?.classList.remove('hidden');
      fileArea?.classList.add('hidden');
    };
    reader.readAsDataURL(file);
  }
  removeFile?.addEventListener('click', () => {
    filePreview?.classList.add('hidden');
    fileArea?.classList.remove('hidden');
    if (fileInput) fileInput.value = '';
  });

  /* ── 17. ADMIN CHARTS (Chart.js) ───────────────────────────── */
  function initAdminCharts() {
    const palette = { navy: '#1A2A6C', coral: '#FF6B4A', green: '#10b981', amber: '#f59e0b', purple: '#8b5cf6', blue: '#3b82f6' };

    // Contributions line chart
    const ctx1 = document.getElementById('contributionsChart');
    if (ctx1) new Chart(ctx1, {
      type: 'line',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{ label: 'UGX', data: [120000,85000,150000,95000,210000,180000,245000], borderColor: palette.coral, backgroundColor: 'rgba(255,107,74,.1)', fill: true, tension: .3, pointBackgroundColor: palette.navy }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'UGX ' + (v/1000) + 'K' } } } }
    });

    // Category doughnut
    const ctx2 = document.getElementById('categoryChart');
    if (ctx2) new Chart(ctx2, {
      type: 'doughnut',
      data: { labels: ['Family','Medical','Education','Community','Business'], datasets: [{ data: [45,30,55,40,25], backgroundColor: [palette.navy, palette.coral, palette.green, palette.amber, palette.purple] }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });

    // Revenue line chart
    const ctx3 = document.getElementById('revenueChart');
    if (ctx3) new Chart(ctx3, {
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{ label: 'Revenue (UGX M)', data: [1.2,1.5,1.1,1.8,2.2,1.9,2.5,2.8,3.1,2.7,3.4,3.9], borderColor: palette.navy, backgroundColor: 'rgba(26,42,108,.08)', fill: true, tension: .3 }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // User growth bar chart
    const ctx4 = document.getElementById('userGrowthChart');
    if (ctx4) new Chart(ctx4, {
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [{ label: 'New Users', data: [120,210,180,310,260,380], backgroundColor: palette.coral }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // Success rate pie
    const ctx5 = document.getElementById('successRateChart');
    if (ctx5) new Chart(ctx5, {
      type: 'pie',
      data: { labels: ['Completed','Active','Paused','Flagged'], datasets: [{ data: [55,30,10,5], backgroundColor: [palette.green, palette.navy, palette.amber, '#ef4444'] }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });

    // Top campaigns horizontal bar
    const ctx6 = document.getElementById('topCampaignsChart');
    if (ctx6) new Chart(ctx6, {
      type: 'bar',
      data: {
        labels: ['Borehole Project','Family Medical','School Fees','Clean Water','Harvest Fund','Tech Hub','Funeral Cover','Wedding Pool','Church Build','Business Start'],
        datasets: [{ label: 'UGX Raised', data: [1200000,950000,800000,750000,600000,550000,480000,420000,380000,310000], backgroundColor: palette.coral }]
      },
      options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { callback: v => 'UGX ' + (v/1000) + 'K' } } } }
    });
  }

  if (document.getElementById('contributionsChart')) initAdminCharts();

}); // end DOMContentLoaded
