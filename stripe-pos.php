<?php
/**
 * Plugin Name: Stripe Terminal POS
 * Plugin URI: https://github.com/davidvidovic-web/stripe-pos-wp
 * Description: A WordPress plugin for Stripe Terminal POS integration with WooCommerce
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: David Vidovic
 * Author Email: mail@davidvidovic.com
 * Author URI: https://davidvidovic.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stripe-pos-wp
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Load Stripe PHP SDK
require_once __DIR__ . '/vendor/autoload.php';

class StripeTerminalPOS
{
    private static $instance = null;
    private $plugin_dir;

    private function __construct()
    {
        $this->plugin_dir = WP_PLUGIN_DIR . '/stripe-pos-wp';
        $this->init_hooks();
    }

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks()
    {
        add_action('admin_menu', [$this, 'register_stripe_settings_menu']);
        add_action('admin_menu', [$this, 'add_stripe_terminal_admin_page'], 11);
        add_action('admin_init', [$this, 'register_stripe_settings']);
        add_action('init', [$this, 'register_stripe_terminal_ajax_endpoints']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_stripe_terminal_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_stripe_terminal_scripts']);
        add_shortcode('stripe_terminal_pos', [$this, 'stripe_terminal_pos_shortcode']);
    }

    public function register_stripe_settings_menu()
    {
        add_menu_page(
            'Stripe Terminals',
            'Stripe Terminals',
            'manage_options',
            'stripe-settings',
            [$this, 'stripe_settings_page'],
            'dashicons-smartphone',
            56
        );
    }

    public function add_stripe_terminal_admin_page()
    {
        add_submenu_page(
            'stripe-settings',
            'Create Payment',
            'Create Payment',
            'manage_options',
            'stripe-terminal-pos',
            [$this, 'display_stripe_terminal_pos_page']
        );

        add_submenu_page(
            'stripe-settings',
            'Settings',
            'Settings',
            'manage_options',
            'stripe-settings-config',
            [$this, 'stripe_settings_page']
        );

        remove_submenu_page('stripe-settings', 'stripe-settings');
    }

    public function register_stripe_settings()
    {
        register_setting('stripe_pos_terminals_group', 'stripe_api_key');
        register_setting('stripe_pos_terminals_group', 'stripe_pos_id');
        register_setting('stripe_pos_terminals_group', 'stripe_enable_tax');
        register_setting('stripe_pos_terminals_group', 'stripe_sales_tax');
        register_setting('stripe_pos_terminals_group', 'stripe_auto_select_terminal');
        register_setting('stripe_pos_terminals_group', 'stripe_default_currency');

        add_settings_section(
            'stripe_settings_section',
            'Configuration',
            [$this, 'stripe_settings_section_callback'],
            'stripe-settings'
        );

        add_settings_field(
            'stripe_api_key',
            'Stripe API Key',
            [$this, 'stripe_api_key_callback'],
            'stripe-settings',
            'stripe_settings_section'
        );

        add_settings_field(
            'stripe_pos_id',
            'Stripe POS ID',
            [$this, 'stripe_pos_id_callback'],
            'stripe-settings',
            'stripe_settings_section'
        );

        add_settings_field(
            'stripe_enable_tax',
            'Enable Sales Tax',
            [$this, 'stripe_enable_tax_callback'],
            'stripe-settings',
            'stripe_settings_section'
        );

        add_settings_field(
            'stripe_sales_tax',
            'Sales Tax Rate (%)',
            [$this, 'stripe_sales_tax_callback'],
            'stripe-settings',
            'stripe_settings_section'
        );

        add_settings_field(
            'stripe_auto_select_terminal',
            'Auto-select Terminal',
            [$this, 'stripe_auto_select_terminal_callback'],
            'stripe-settings',
            'stripe_settings_section'
        );

        add_settings_field(
            'stripe_default_currency',
            'Default Currency',
            [$this, 'stripe_default_currency_callback'],
            'stripe-settings',
            'stripe_settings_section'
        );
    }

    /**
     * Settings page callbacks
     */
    function stripe_settings_section_callback()
    {
        echo '<p>Enter your Stripe API credentials below.</p>';
    }

    function stripe_api_key_callback()
    {
        $stripe_api_key = get_option('stripe_api_key');
        echo '<input type="password" name="stripe_api_key" value="' . esc_attr($stripe_api_key) . '" class="regular-text" />';
        echo '<p class="description">Enter your Stripe API Key here.</p>';
    }

    function stripe_pos_id_callback()
    {
        $stripe_pos_id = get_option('stripe_pos_id');
        echo '<input type="text" name="stripe_pos_id" value="' . esc_attr($stripe_pos_id) . '" class="regular-text" />';
        echo '<p class="description">Enter your Stripe POS ID here.</p>';
    }

    function stripe_enable_tax_callback()
    {
        $enable_tax = get_option('stripe_enable_tax', '0');
        echo '<input type="checkbox" id="stripe_enable_tax" name="stripe_enable_tax" value="1" ' . checked('1', $enable_tax, false) . ' />';
        echo '<p class="description">Check this box to enable sales tax calculation.</p>';
    }

    function stripe_sales_tax_callback()
    {
        $enable_tax = get_option('stripe_enable_tax', '0');
        $stripe_sales_tax = get_option('stripe_sales_tax', '0');
        $disabled = $enable_tax !== '1' ? ' disabled' : '';

        echo '<input type="number" id="stripe_sales_tax" step="0.01" min="0" max="100" 
            name="stripe_sales_tax" value="' . esc_attr($stripe_sales_tax) . '" 
            class="small-text"' . $disabled . ' /> %';
        echo '<p class="description">Enter your sales tax rate as a percentage (e.g., 10.25 for 10.25%). Set to 0 for no tax.</p>';
    }

    function stripe_auto_select_terminal_callback()
    {
        $auto_select = get_option('stripe_auto_select_terminal', '1');
        echo '<input type="checkbox" id="stripe_auto_select_terminal" name="stripe_auto_select_terminal" value="1" ' . checked('1', $auto_select, false) . ' />';
        echo '<p class="description">Automatically select the first available terminal when discovering readers.</p>';
    }

    public function stripe_default_currency_callback()
    {
        $default_currency = $this->get_default_currency();
        $currencies = $this->get_supported_currencies();

        echo '<select id="stripe_default_currency" name="stripe_default_currency">';
        foreach ($currencies as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($default_currency, $code, false) . '>';
            echo esc_html("$code - $name");
            echo '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select the default currency for payments. This will default to your WooCommerce currency if available.</p>';
    }

    /**
     * Settings page display
     */
    function stripe_settings_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('stripe_pos_terminals_group');
                do_settings_sections('stripe-settings');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Display the Stripe Terminal POS admin page
     */
    function display_stripe_terminal_pos_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Ensure shortcode styles and scripts work in admin
        wp_enqueue_script('stripe-terminal-pos');

    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="stripe-terminal-admin-container">
                <?php require_once $this->plugin_dir . '/inc/views/stripe-pos-payment.php'; ?>
            </div>
        </div>
<?php
    }

    /**
     * Initialize Stripe with API key from settings
     */
    private function initialize_stripe()
    {
        $stripe_api_key = get_option('stripe_api_key');

        if (!$stripe_api_key) {
            throw new Exception('Stripe API key not configured. Please check your Stripe Settings.');
        }

        \Stripe\Stripe::setApiKey($stripe_api_key);
    }

    /**
     * Discover readers in the location
     * 
     * @return array|WP_Error List of discovered readers or error
     */
    public function discover_readers()
    {
        try {
            $this->initialize_stripe();

            $stripe_pos_id = get_option('stripe_pos_id');
            if (!$stripe_pos_id) {
                error_log('Stripe Terminal: Missing POS location ID');
                return new WP_Error('missing_location', 'Stripe POS location ID not configured');
            }

            error_log('Stripe Terminal: Attempting to discover readers for location ' . $stripe_pos_id);

            $readers = \Stripe\Terminal\Reader::all([
                'location' => $stripe_pos_id,
                'limit' => 10,
            ]);

            error_log('Stripe Terminal: Found ' . count($readers->data) . ' readers');

            return $readers->data;
        } catch (Exception $e) {
            error_log('Stripe Terminal Error: ' . $e->getMessage());
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Create a payment intent for Terminal payment
     * 
     * @param float $amount Amount to charge in dollars
     * @param string $currency Currency code (e.g., 'usd')
     * @param array $metadata Optional metadata for the payment
     * @return array|WP_Error Payment intent or error
     */
    public function create_terminal_payment_intent($amount, $currency = 'usd', $metadata = [])
    {
        try {
            $this->initialize_stripe();

            // Convert amount to cents
            $amount_in_cents = intval($amount * 100);

            // Generate an idempotency key
            $idempotency_key = uniqid('terminal_payment_', true);

            // Create a payment intent with idempotency key as a header option
            $payment_intent = \Stripe\PaymentIntent::create(
                [
                    'amount' => $amount_in_cents,
                    'currency' => $currency,
                    'payment_method_types' => ['card_present'],
                    'capture_method' => 'automatic',
                    'metadata' => $metadata,
                ],
                [
                    'idempotency_key' => $idempotency_key
                ]
            );

            return [
                'client_secret' => $payment_intent->client_secret,
                'intent_id' => $payment_intent->id,
            ];
        } catch (Exception $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Process a payment on a specific terminal
     * 
     * @param string $reader_id ID of the terminal reader
     * @param string $payment_intent_id ID of the payment intent
     * @return array|WP_Error Result of the process payment operation
     */
    public function process_terminal_payment($reader_id, $payment_intent_id)
    {
        try {
            $this->initialize_stripe();

            // First retrieve the reader
            $reader = \Stripe\Terminal\Reader::retrieve($reader_id);

            // Then call processPaymentIntent on the reader instance
            $process_payment = $reader->processPaymentIntent([
                'payment_intent' => $payment_intent_id
            ]);

            return [
                'success' => true,
                'reader_state' => $process_payment->action->status,
                'process_id' => $process_payment->id,
            ];
        } catch (Exception $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Check the status of a payment intent
     * 
     * @param string $payment_intent_id ID of the payment intent
     * @return array|WP_Error Payment intent status or error
     */
    public function check_payment_intent_status($payment_intent_id)
    {
        try {
            $this->initialize_stripe();

            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

            return [
                'status' => $payment_intent->status,
                'amount' => $payment_intent->amount / 100, // Convert back to dollars
                'currency' => $payment_intent->currency,
                'is_captured' => $payment_intent->amount_received > 0,
                'payment_method' => $payment_intent->payment_method,
            ];
        } catch (Exception $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Cancel a payment intent
     * 
     * @param string $payment_intent_id ID of the payment intent
     * @return array|WP_Error Confirmation of cancellation or error
     */
    public function cancel_payment_intent($payment_intent_id)
    {
        try {
            $this->initialize_stripe();

            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
            $payment_intent->cancel();

            return [
                'success' => true,
                'status' => $payment_intent->status,
            ];
        } catch (Exception $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Clear a terminal's display
     * 
     * @param string $reader_id ID of the terminal reader
     * @return array|WP_Error Result of the clear terminal operation
     */
    public function clear_terminal_display($reader_id)
    {
        try {
            $this->initialize_stripe();

            // Retrieve the reader
            $reader = \Stripe\Terminal\Reader::retrieve($reader_id);

            // Use cancelAction to abort any in-progress reader action
            $result = $reader->cancelAction();

            return [
                'success' => true,
                'reader_state' => $result->action ? $result->action->status : 'cleared',
            ];
        } catch (Exception $e) {
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Create AJAX endpoint to process Stripe Terminal payment
     */
    public function register_stripe_terminal_ajax_endpoints()
    {
        add_action('wp_ajax_stripe_discover_readers', [$this, 'ajax_stripe_discover_readers']);
        add_action('wp_ajax_stripe_create_payment_intent', [$this, 'ajax_stripe_create_payment_intent']);
        add_action('wp_ajax_stripe_process_payment', [$this, 'ajax_stripe_process_payment']);
        add_action('wp_ajax_stripe_check_payment_status', [$this, 'ajax_stripe_check_payment_status']);
        add_action('wp_ajax_stripe_cancel_payment', [$this, 'ajax_stripe_cancel_payment']);
        add_action('wp_ajax_stripe_clear_terminal', [$this, 'ajax_stripe_clear_terminal']);
    }

    /**
     * AJAX handler for discovering readers
     */
    function ajax_stripe_discover_readers()
    {
        check_ajax_referer('stripe_terminal_nonce', 'nonce');

        $readers = $this->discover_readers();

        if (is_wp_error($readers)) {
            wp_send_json_error($readers->get_error_message());
        } else {
            wp_send_json_success($readers);
        }

        wp_die();
    }

    /**
     * AJAX handler for creating payment intent
     */
    function ajax_stripe_create_payment_intent()
    {
        check_ajax_referer('stripe_terminal_nonce', 'nonce');

        if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
            wp_send_json_error('Invalid amount');
            wp_die();
        }

        $amount = floatval($_POST['amount']);
        $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'usd';
        $metadata = isset($_POST['metadata']) && is_array($_POST['metadata']) ? $_POST['metadata'] : [];

        $result = $this->create_terminal_payment_intent($amount, $currency, $metadata);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }

        wp_die();
    }

    /**
     * AJAX handler for processing payment
     */
    function ajax_stripe_process_payment()
    {
        check_ajax_referer('stripe_terminal_nonce', 'nonce');

        if (!isset($_POST['reader_id']) || !isset($_POST['payment_intent_id'])) {
            wp_send_json_error('Missing required parameters');
            wp_die();
        }

        $reader_id = sanitize_text_field($_POST['reader_id']);
        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);

        $result = $this->process_terminal_payment($reader_id, $payment_intent_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }

        wp_die();
    }

    /**
     * AJAX handler for checking payment status
     */
    function ajax_stripe_check_payment_status()
    {
        check_ajax_referer('stripe_terminal_nonce', 'nonce');

        if (!isset($_POST['payment_intent_id']) || !isset($_POST['cart_items'])) {
            wp_send_json_error('Missing required data');
            wp_die();
        }

        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);
        $cart_items = json_decode(stripslashes($_POST['cart_items']), true);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $reader_id = isset($_POST['reader_id']) ? sanitize_text_field($_POST['reader_id']) : '';

        $result = $this->check_payment_intent_status($payment_intent_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            if ($result['status'] === 'succeeded') {
                // Create WooCommerce order
                $order_data = [
                    'amount' => $result['amount'],
                    'tax' => isset($_POST['tax']) ? floatval($_POST['tax']) : 0,
                    'payment_intent_id' => $payment_intent_id,
                    'reader_id' => $reader_id,
                    'notes' => $notes
                ];

                $order_id = $this->create_woocommerce_order($order_data, $cart_items);
                $result['order_id'] = $order_id;
            }
            wp_send_json_success($result);
        }

        wp_die();
    }

    /**
     * AJAX handler for canceling payment
     */
    function ajax_stripe_cancel_payment()
    {
        check_ajax_referer('stripe_terminal_nonce', 'nonce');

        if (!isset($_POST['payment_intent_id'])) {
            wp_send_json_error('Missing payment_intent_id');
            wp_die();
        }

        $payment_intent_id = sanitize_text_field($_POST['payment_intent_id']);

        $result = $this->cancel_payment_intent($payment_intent_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }

        wp_die();
    }

    /**
     * AJAX handler for clearing the terminal display
     */
    function ajax_stripe_clear_terminal()
    {
        check_ajax_referer('stripe_terminal_nonce', 'nonce');

        if (!isset($_POST['reader_id'])) {
            wp_send_json_error('Missing reader_id');
            wp_die();
        }

        $reader_id = sanitize_text_field($_POST['reader_id']);
        $result = $this->clear_terminal_display($reader_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }

        wp_die();
    }

    /**
     * Register and enqueue Stripe Terminal scripts
     */
    public function enqueue_stripe_terminal_scripts()
    {
        $screen = get_current_screen();

        // Enqueue admin scripts only on the settings page
        if ($screen && $screen->base === 'stripe-terminals_page_stripe-settings-config') {
            wp_enqueue_script(
                'stripe-terminal-admin',
                plugins_url('/assets/js/admin.js', __FILE__),
                ['jquery'],
                '1.0.0',
                true
            );
        }

        // Main POS script enqueue
        wp_register_script(
            'stripe-terminal-pos',
            plugins_url('/assets/js/main.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        // Enqueue main CSS
        wp_enqueue_style(
            'stripe-terminal-pos',
            plugins_url('/assets/css/main.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_localize_script('stripe-terminal-pos', 'stripe_terminal_pos', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stripe_terminal_nonce'),
            'enable_tax' => get_option('stripe_enable_tax', '0') === '1',
            'sales_tax_rate' => floatval(get_option('stripe_sales_tax', '0')) / 100,
            'auto_select_terminal' => get_option('stripe_auto_select_terminal', '1') === '1',
            'currency' => $this->get_default_currency(),
            'currency_symbol' => $this->get_currency_symbol($this->get_default_currency())
        ]);
    }

    /**
     * Add a shortcode to display a simple POS terminal interface
     */
    function stripe_terminal_pos_shortcode()
    {
        // Enqueue the script specifically for this shortcode
        wp_enqueue_script('stripe-terminal-pos');

        // Enqueue Select2 for better product search experience
        wp_enqueue_style('select2', WP_PLUGIN_URL . '/stripe-pos-wp/assets/css/select2.min.css');
        wp_enqueue_script('select2', WP_PLUGIN_URL . '/stripe-pos-wp/assets/js/select2.min.js', array('jquery'), null, true);
    }

    private function get_supported_currencies()
    {
        return [
            'usd' => '$',
            'eur' => '€',
            'gbp' => '£',
            'aud' => 'A$',
            'cad' => 'C$',
            'jpy' => '¥',
            'nzd' => 'NZ$',
            'chf' => 'CHF',
            'sgd' => 'S$',
            'hkd' => 'HK$',
        ];
    }

    private function get_default_currency()
    {
        // Try to get WooCommerce currency first
        if (function_exists('get_woocommerce_currency')) {
            $wc_currency = strtolower(get_woocommerce_currency());
            if (array_key_exists($wc_currency, $this->get_supported_currencies())) {
                return $wc_currency;
            }
        }

        // Fall back to saved setting or default to USD
        return get_option('stripe_default_currency', 'usd');
    }

    private function get_currency_symbol($currency_code)
    {
        $symbols = [
            'usd' => '$',
            'eur' => '€',
            'gbp' => '£',
            'aud' => 'A$',
            'cad' => 'C$',
            'jpy' => '¥',
            'nzd' => 'NZ$',
            'chf' => 'CHF',
            'sgd' => 'S$',
            'hkd' => 'HK$',
        ];

        return isset($symbols[$currency_code]) ? $symbols[$currency_code] : $currency_code;
    }

    private function create_woocommerce_order($payment_data, $cart_items) {
        if (!class_exists('WooCommerce')) {
            return false;
        }

        try {
            // Create a new order
            $order = wc_create_order();

            // Add products to the order
            foreach ($cart_items as $item) {
                $product_id = absint($item['product_id']);
                $product = wc_get_product($product_id);
                if ($product) {
                    $order->add_product(
                        $product,
                        absint($item['quantity']),
                        [
                            'subtotal' => floatval($item['price']),
                            'total' => floatval($item['total'])
                        ]
                    );
                }
            }

            // Add tax
            if (isset($payment_data['tax']) && $payment_data['tax'] > 0) {
                $order->set_cart_tax($payment_data['tax']);
            }

            // Set payment method
            $order->set_payment_method('stripe_terminal');
            $order->set_payment_method_title('Stripe Terminal');

            // Set order totals
            $order->set_total($payment_data['amount']);

            // Add note
            if (!empty($payment_data['notes'])) {
                $order->add_order_note($payment_data['notes'], false, false);
            }

            // Add Stripe payment metadata
            $order->update_meta_data('_stripe_payment_intent_id', $payment_data['payment_intent_id']);
            $order->update_meta_data('_stripe_terminal_reader_id', $payment_data['reader_id']);

            // Set order as completed since payment is already processed
            $order->payment_complete($payment_data['payment_intent_id']);
            $order->add_order_note('Payment completed via Stripe Terminal POS');

            // Save the order
            $order->save();

            return $order->get_id();
        } catch (Exception $e) {
            error_log('Stripe Terminal: Error creating WooCommerce order - ' . $e->getMessage());
            return false;
        }
    }
}

// Initialize the plugin
function stripe_terminal_pos_init()
{
    return StripeTerminalPOS::get_instance();
}

// Start the plugin
stripe_terminal_pos_init();
