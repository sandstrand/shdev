<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="svea-part-payment-plans">
    <h3><?php _e('Part Payment Plans', 'sveawebpay'); ?></h3>
    <?php for ($i=0;$i<count($campaigns);++$i) : ?>
        <?php $campaign = $campaigns[$i]; ?>
        <?php if($campaign->fromAmount > $total || $campaign->toAmount < $total) continue; ?>
        <div class="part-pay-campaign-input-container">
            <input id="part-pay-campaign-input-<?php echo $i; ?>" type="radio" name="part-pay-input-<?php echo $customer_country; ?>"
            value="<?php echo $campaign->campaignCode; ?>" />
            <label class="part-pay-campaign-input-label" for="part-pay-campaign-input-<?php echo $i; ?>">
                <?php echo $campaign->description; ?>
            </label>
        </div>
    <?php endfor; ?>
</div>