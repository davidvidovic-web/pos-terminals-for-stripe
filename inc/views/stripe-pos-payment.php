<?php ?>
<div class="wrap">
    <div class="stripe-terminal-admin-container">
        <div class="stripe-terminal-pos">
            <?php
            $wc_currency = get_woocommerce_currency();
            if (!$this->is_currency_supported($wc_currency)):
            ?>
                <div class="notice notice-error">
                    <p>
                        <strong>Warning:</strong> Your current WooCommerce currency (<?php echo esc_html($wc_currency); ?>)
                        is not supported by Stripe Terminal. Please change your WooCommerce currency to one of the following:
                        <?php echo esc_html(implode(', ', array_keys($this->supported_currencies))); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="pos-section">
                <h3>1. Select Terminal</h3>
                <button id="discover-terminals" class="button">Discover Terminals</button>
                <div class="terminal-list"></div>
            </div>

            <div class="pos-section">
                <h3>2. Payment Details</h3>

                <div class="form-row product-search-container">
                    <label for="product-search">Search Products</label>
                    <select id="product-search" class="product-search" style="width: 100%;">
                        <option value="">Search for a product...</option>
                        <?php
                        // Get WooCommerce products if WooCommerce is active
                        if (class_exists('WooCommerce')) {
                            $args = array(
                                'status' => 'publish',
                                'limit' => -1,
                            );
                            $products = wc_get_products($args);

                            // Update the product option output
                            foreach ($products as $product) {
                                $price = esc_attr($product->get_price());
                                $id = absint($product->get_id());  // Using absint for IDs
                                $name = esc_html($product->get_name());
                                $formatted_price = wp_kses(
                                    wc_price($price),
                                    array(
                                        'span' => array(
                                            'class' => array()
                                        )
                                    )
                                );

                                printf(
                                    '<option value="%1$d" data-price="%2$s">%3$s (%4$s)</option>',
                                    esc_attr($id),
                                    esc_attr($price),
                                    esc_attr($name),
                                    esc_attr($formatted_price)
                                );
                            }
                        }
                        ?>
                    </select>
                    <button id="add-product" class="button">Add Product</button>
                </div>

                <div class="cart-container">
                    <table id="cart-table" class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Products will be added here dynamically -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Subtotal</td>
                                <td id="subtotal">$0.00</td>
                                <td></td>
                            </tr>
                            <?php if (get_option('stripe_enable_tax', '0') === '1'): ?>
                                <tr id="tax-row">
                                    <td colspan="3"><span id="tax-rate-display">Sales Tax (<?php echo esc_html(get_option('stripe_sales_tax', '0')); ?>%)</span></td>
                                    <td id="tax">$0.00</td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="3"><strong>Total</strong></td>
                                <td id="total"><strong>$0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="form-row">
                    <label for="payment-description">Additional Notes</label>
                    <input type="text" id="payment-description" placeholder="Additional notes about this order">
                </div>

                <button id="create-payment" class="button" disabled>Create Payment</button>
            </div>

            <div class="pos-section">
                <h3>3. Process Payment</h3>
                <div id="payment-status"></div>
                <div class="button-group payment-controls" style="display: none;">
                    <button id="check-status" class="button">Check Status</button>
                    <button id="cancel-payment" class="button">Cancel Payment</button>
                </div>
            </div>
        </div>
    </div>
</div>