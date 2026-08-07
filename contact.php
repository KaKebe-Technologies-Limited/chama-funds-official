<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Contact Us – ChamaFunds';
$pageDescription = 'Reach out to ChamaFunds for inquiries, guidance, and support. Call or WhatsApp us on 0779712990.';
include __DIR__ . '/includes/header.php';
?>

<section style="background:linear-gradient(135deg,#1A2A6C,#2a3f8a);padding:100px 0 60px;text-align:center;">
  <div class="container">
    <div class="hero-badge" style="display:inline-flex;margin-bottom:16px;"><i class="fas fa-headset" style="color:#facc15;"></i> We're here to help</div>
    <h1 style="color:#fff;font-size:clamp(1.8rem,4vw,2.8rem);font-weight:800;margin-bottom:12px;">Contact ChamaFunds</h1>
    <p style="color:rgba(255,255,255,.75);font-size:1rem;max-width:480px;margin:0 auto;">Have a question, need guidance, or want to know more? Reach us directly.</p>
  </div>
</section>

<section class="section" style="background:#f8fafc;">
  <div class="container" style="max-width:800px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">

      <!-- WhatsApp -->
      <a href="https://wa.me/256779712990?text=ChamaFunds%20Inquiry"
         target="_blank"
         style="background:#fff;border-radius:20px;padding:32px 28px;text-decoration:none;
                box-shadow:0 4px 20px rgba(26,42,108,.08);display:flex;flex-direction:column;
                align-items:center;gap:14px;text-align:center;border:2px solid transparent;
                transition:all .2s;"
         onmouseover="this.style.borderColor='#25D366';this.style.transform='translateY(-3px)'"
         onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
        <div style="width:64px;height:64px;background:#25D366;border-radius:50%;
                    display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;">
          <i class="fab fa-whatsapp"></i>
        </div>
        <div>
          <p style="font-weight:800;color:#1A2A6C;font-size:1rem;margin-bottom:4px;">WhatsApp</p>
          <p style="color:#6b7280;font-size:.88rem;">Chat with us directly</p>
          <p style="color:#25D366;font-weight:700;font-size:.95rem;margin-top:8px;">0779 712 990</p>
        </div>
        <span style="background:#25D366;color:#fff;padding:10px 24px;border-radius:99px;
                     font-weight:700;font-size:.88rem;">Open WhatsApp</span>
      </a>

      <!-- Call -->
      <a href="tel:+256779712990"
         style="background:#fff;border-radius:20px;padding:32px 28px;text-decoration:none;
                box-shadow:0 4px 20px rgba(26,42,108,.08);display:flex;flex-direction:column;
                align-items:center;gap:14px;text-align:center;border:2px solid transparent;
                transition:all .2s;"
         onmouseover="this.style.borderColor='#FF6B4A';this.style.transform='translateY(-3px)'"
         onmouseout="this.style.borderColor='transparent';this.style.transform='translateY(0)'">
        <div style="width:64px;height:64px;background:#FF6B4A;border-radius:50%;
                    display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#fff;">
          <i class="fas fa-phone"></i>
        </div>
        <div>
          <p style="font-weight:800;color:#1A2A6C;font-size:1rem;margin-bottom:4px;">Call Us</p>
          <p style="color:#6b7280;font-size:.88rem;">Mon–Sat, 8am–6pm EAT</p>
          <p style="color:#FF6B4A;font-weight:700;font-size:.95rem;margin-top:8px;">0779 712 990</p>
        </div>
        <span style="background:#FF6B4A;color:#fff;padding:10px 24px;border-radius:99px;
                     font-weight:700;font-size:.88rem;">Call Now</span>
      </a>

    </div>

    <!-- Info card -->
    <div style="background:#fff;border-radius:20px;padding:32px;box-shadow:0 4px 20px rgba(26,42,108,.08);">
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;font-size:1.1rem;">
        <i class="fas fa-info-circle" style="color:#FF6B4A;margin-right:8px;"></i>
        What we can help with
      </h2>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
        <?php
        $items = [
            ['fas fa-rocket','Starting a campaign','How to create and launch your fundraiser'],
            ['fas fa-credit-card','Withdrawals','How to request and receive your funds'],
            ['fas fa-heart','Making donations','How to donate via mobile money'],
            ['fas fa-shield-alt','Campaign verification','What documents are needed'],
            ['fas fa-mobile-alt','Mobile money issues','MTN, Airtel and payment support'],
            ['fas fa-question-circle','General inquiries','Any other questions about ChamaFunds'],
        ];
        foreach ($items as [$icon, $title, $desc]): ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:14px;background:#f8fafc;border-radius:12px;">
          <div style="width:36px;height:36px;background:rgba(255,107,74,.1);border-radius:9px;
                      display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="<?= $icon ?>" style="color:#FF6B4A;font-size:.85rem;"></i>
          </div>
          <div>
            <p style="font-weight:700;color:#1A2A6C;font-size:.85rem;margin-bottom:2px;"><?= $title ?></p>
            <p style="font-size:.78rem;color:#9ca3af;"><?= $desc ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:24px;padding:16px;background:rgba(26,42,108,.04);border-radius:12px;
                  display:flex;align-items:center;gap:12px;">
        <i class="fas fa-clock" style="color:#1A2A6C;font-size:1.1rem;"></i>
        <div>
          <p style="font-weight:700;color:#1A2A6C;font-size:.88rem;">Business Hours</p>
          <p style="font-size:.82rem;color:#6b7280;">Monday – Saturday · 8:00 AM – 6:00 PM (East Africa Time)</p>
        </div>
      </div>
    </div>

    <p style="text-align:center;margin-top:24px;font-size:.84rem;color:#9ca3af;">
      You can also read our
      <a href="<?= BASE ?>/admin/CampaignerDetails.pdf" target="_blank" style="color:#FF6B4A;font-weight:600;">
        <i class="fas fa-file-pdf" style="margin-right:4px;"></i>Campaigner Guide
      </a>
      for detailed information on how ChamaFunds works.
    </p>

  </div>
</section>

<style>
@media(max-width:600px){
  div[style*="grid-template-columns:1fr 1fr"]{display:block!important;}
  div[style*="grid-template-columns:1fr 1fr"] > *{margin-bottom:16px;}
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
