<section class="woocommerce-order-details">
  <h2 class="woocommerce-order-details__title" style="margin-top: 50px !important; margin-bottom: -20px !important;"><?php echo __($detailHeader, 'wc-gateway-expresspay') ?></h2>
  <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
    <?php if (!empty($detailResultText)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Status:', 'wc-gateway-expresspay') ?><strong><?php echo __($detailResultText, 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailOrderId)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Order ID:', 'wc-gateway-expresspay') ?><strong><?php echo __($detailOrderId, 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailCurrency)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Currency:', 'wc-gateway-expresspay') ?><strong><?php echo __($detailCurrency, 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailAmount)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Amount:', 'wc-gateway-expresspay') ?><strong><?php echo __(number_format($detailAmount, 2, '.', ','), 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailPaymentOption)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Payment Option:', 'wc-gateway-expresspay') ?><strong><?php echo __($detailPaymentOption, 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailTransactionID)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Transaction ID:', 'wc-gateway-expresspay') ?><strong><?php echo __($detailTransactionID, 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailDateProcessed)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo __('Date Processed:', 'wc-gateway-expresspay') ?><strong><?php echo __($detailDateProcessed, 'wc-gateway-expresspay') ?></strong>
      </li>
    <?php endif; ?>
  </ul>
</section>