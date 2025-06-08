jQuery(document).ready(function($) {
    // Initial state setup
    const $taxCheckbox = $('#stripe_enable_tax');
    const $taxRate = $('#stripe_sales_tax');
    
    // Set initial state
    $taxRate.prop('disabled', !$taxCheckbox.is(':checked'));

    // Handle tax enable/disable toggle
    $taxCheckbox.on('change', function() {
        $taxRate.prop('disabled', !$(this).is(':checked'));
    });
});