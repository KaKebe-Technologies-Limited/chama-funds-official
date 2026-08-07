<!-- On campaign-detail.php -->
<form action="process_donation.php" method="POST" id="donationForm">
    <input type="hidden" name="campaign_id" value="<?php echo $campaign['campaign_id']; ?>">
    <input type="hidden" name="currency" value="<?php echo $campaign['currency']; ?>">
    
    <!-- Amount Selection -->
    <div class="form-group">
        <label>Choose Amount</label>
        <div class="amount-buttons">
            <button type="button" class="amount-btn" data-amount="10000">10K</button>
            <button type="button" class="amount-btn" data-amount="25000">25K</button>
            <button type="button" class="amount-btn active" data-amount="50000">50K</button>
            <button type="button" class="amount-btn" data-amount="100000">100K</button>
        </div>
        <input type="number" name="amount" id="donationAmount" class="form-input mt-2" 
               placeholder="Enter amount" min="1000" value="50000" required>
    </div>
    
    <!-- Donor Details -->
    <div class="form-group">
        <input type="text" name="donor_name" class="form-input" 
               placeholder="Your Full Name *" required>
    </div>
    
    <div class="form-group">
        <input type="email" name="donor_email" class="form-input" 
               placeholder="Your Email (optional)">
    </div>
    
    <div class="form-group">
        <input type="tel" name="donor_phone" class="form-input" 
               placeholder="Mobile Money Number *" required>
    </div>
    
    <div class="form-group checkbox-group">
        <input type="checkbox" name="is_anonymous" id="anonymousCheck">
        <label for="anonymousCheck">Remain Anonymous</label>
    </div>
    
    <button type="submit" class="donate-btn">
        <i class="fas fa-lock"></i> Donate Securely with Pesapal
    </button>
</form>

<!-- Update amount buttons JavaScript -->
<script>
document.querySelectorAll('.amount-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('donationAmount').value = this.dataset.amount;
    });
});
</script>