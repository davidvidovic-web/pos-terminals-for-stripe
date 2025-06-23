<?php

/**
 * Plugin Name: POS Terminals integration for Stripe
 * Plugin URI: https://github.com/davidvidovic-web/stripe-pos-wp
 * Description: A WordPress plugin for Stripe Terminal POS integration with WooCommerce
 * Version: 1.0.0
 * Requires PHP: 7.2
 * Author: David Vidovic
 * Author Email: mail@davidvidovic.com
 * Author URI: https://davidvidovic.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pos-terminals-for-stripe
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

    // Update the supported_currencies property
    private $supported_currencies = [
        'usd' => 'US Dollar',
        'aed' => 'UAE Dirham',
        'afn' => 'Afghan Afghani',
        'all' => 'Albanian Lek',
        'amd' => 'Armenian Dram',
        'ang' => 'Netherlands Antillean Guilder',
        'aoa' => 'Angolan Kwanza',
        'ars' => 'Argentine Peso',
        'aud' => 'Australian Dollar',
        'awg' => 'Aruban Florin',
        'azn' => 'Azerbaijani Manat',
        'bam' => 'Bosnia-Herzegovina Convertible Mark',
        'bbd' => 'Barbadian Dollar',
        'bdt' => 'Bangladeshi Taka',
        'bgn' => 'Bulgarian Lev',
        'bhd' => 'Bahraini Dinar',
        'bif' => 'Burundian Franc',
        'bmd' => 'Bermudan Dollar',
        'bnd' => 'Brunei Dollar',
        'bob' => 'Bolivian Boliviano',
        'brl' => 'Brazilian Real',
        'bsd' => 'Bahamian Dollar',
        'btn' => 'Bhutanese Ngultrum',
        'bwp' => 'Botswanan Pula',
        'byn' => 'Belarusian Ruble',
        'bzd' => 'Belize Dollar',
        'cad' => 'Canadian Dollar',
        'cdf' => 'Congolese Franc',
        'chf' => 'Swiss Franc',
        'clp' => 'Chilean Peso',
        'cny' => 'Chinese Yuan',
        'cop' => 'Colombian Peso',
        'crc' => 'Costa Rican Colón',
        'cve' => 'Cape Verdean Escudo',
        'czk' => 'Czech Koruna',
        'djf' => 'Djiboutian Franc',
        'dkk' => 'Danish Krone',
        'dop' => 'Dominican Peso',
        'dzd' => 'Algerian Dinar',
        'eek' => 'Estonian Kroon',
        'egp' => 'Egyptian Pound',
        'etb' => 'Ethiopian Birr',
        'eur' => 'Euro',
        'fjd' => 'Fijian Dollar',
        'fkp' => 'Falkland Islands Pound',
        'gbp' => 'British Pound',
        'gel' => 'Georgian Lari',
        'ghs' => 'Ghanaian Cedi',
        'gip' => 'Gibraltar Pound',
        'gmd' => 'Gambian Dalasi',
        'gnf' => 'Guinean Franc',
        'gtq' => 'Guatemalan Quetzal',
        'gyd' => 'Guyanaese Dollar',
        'hkd' => 'Hong Kong Dollar',
        'hnl' => 'Honduran Lempira',
        'hrk' => 'Croatian Kuna',
        'htg' => 'Haitian Gourde',
        'huf' => 'Hungarian Forint',
        'idr' => 'Indonesian Rupiah',
        'ils' => 'Israeli New Sheqel',
        'inr' => 'Indian Rupee',
        'isk' => 'Icelandic Króna',
        'jmd' => 'Jamaican Dollar',
        'jod' => 'Jordanian Dinar',
        'jpy' => 'Japanese Yen',
        'kes' => 'Kenyan Shilling',
        'kgs' => 'Kyrgystani Som',
        'khr' => 'Cambodian Riel',
        'kmf' => 'Comorian Franc',
        'krw' => 'South Korean Won',
        'kwd' => 'Kuwaiti Dinar',
        'kyd' => 'Cayman Islands Dollar',
        'kzt' => 'Kazakhstani Tenge',
        'lak' => 'Laotian Kip',
        'lbp' => 'Lebanese Pound',
        'lkr' => 'Sri Lankan Rupee',
        'lrd' => 'Liberian Dollar',
        'lsl' => 'Lesotho Loti',
        'ltl' => 'Lithuanian Litas',
        'lvl' => 'Latvian Lats',
        'mad' => 'Moroccan Dirham',
        'mdl' => 'Moldovan Leu',
        'mga' => 'Malagasy Ariary',
        'mkd' => 'Macedonian Denar',
        'mmk' => 'Myanmar Kyat',
        'mnt' => 'Mongolian Tugrik',
        'mop' => 'Macanese Pataca',
        'mro' => 'Mauritanian Ouguiya',
        'mur' => 'Mauritian Rupee',
        'mvr' => 'Maldivian Rufiyaa',
        'mwk' => 'Malawian Kwacha',
        'mxn' => 'Mexican Peso',
        'myr' => 'Malaysian Ringgit',
        'mzn' => 'Mozambican Metical',
        'nad' => 'Namibian Dollar',
        'ngn' => 'Nigerian Naira',
        'nio' => 'Nicaraguan Córdoba',
        'nok' => 'Norwegian Krone',
        'npr' => 'Nepalese Rupee',
        'nzd' => 'New Zealand Dollar',
        'omr' => 'Omani Rial',
        'pab' => 'Panamanian Balboa',
        'pen' => 'Peruvian Nuevo Sol',
        'pgk' => 'Papua New Guinean Kina',
        'php' => 'Philippine Peso',
        'pkr' => 'Pakistani Rupee',
        'pln' => 'Polish Złoty',
        'pyg' => 'Paraguayan Guarani',
        'qar' => 'Qatari Rial',
        'ron' => 'Romanian Leu',
        'rsd' => 'Serbian Dinar',
        'rub' => 'Russian Ruble',
        'rwf' => 'Rwandan Franc',
        'sar' => 'Saudi Riyal',
        'sbd' => 'Solomon Islands Dollar',
        'scr' => 'Seychellois Rupee',
        'sek' => 'Swedish Krona',
        'sgd' => 'Singapore Dollar',
        'shp' => 'Saint Helena Pound',
        'sle' => 'Sierra Leonean Leone',
        'sll' => 'Sierra Leonean Leone (Old)',
        'sos' => 'Somali Shilling',
        'srd' => 'Surinamese Dollar',
        'std' => 'São Tomé and Príncipe Dobra',
        'svc' => 'Salvadoran Colón',
        'szl' => 'Swazi Lilangeni',
        'thb' => 'Thai Baht',
        'tjs' => 'Tajikistani Somoni',
        'tnd' => 'Tunisian Dinar',
        'top' => 'Tongan Paʻanga',
        'try' => 'Turkish Lira',
        'ttd' => 'Trinidad and Tobago Dollar',
        'twd' => 'New Taiwan Dollar',
        'tzs' => 'Tanzanian Shilling',
        'uah' => 'Ukrainian Hryvnia',
        'ugx' => 'Ugandan Shilling',
        'usdc' => 'USD Coin',
        'uyu' => 'Uruguayan Peso',
        'uzs' => 'Uzbekistan Som',
        'vef' => 'Venezuelan Bolívar',
        'vnd' => 'Vietnamese Dong',
        'vuv' => 'Vanuatu Vatu',
        'wst' => 'Samoan Tala',
        'xaf' => 'Central African CFA Franc',
        'xcd' => 'East Caribbean Dollar',
        'xcg' => 'CFA Franc BCEAO',
        'xof' => 'West African CFA Franc',
        'xpf' => 'CFP Franc',
        'yer' => 'Yemeni Rial',
        'zar' => 'South African Rand',
        'zmw' => 'Zambian Kwacha'
    ];

    private function __construct()
    {
        $this->plugin_dir = plugin_dir_path(__FILE__);
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
        register_setting(
            'stripe_pos_terminals_group',
            'stripe_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        register_setting(
            'stripe_pos_terminals_group',
            'stripe_pos_id',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );

        register_setting(
            'stripe_pos_terminals_group',
            'stripe_enable_tax',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => false
            )
        );

        register_setting(
            'stripe_pos_terminals_group',
            'stripe_sales_tax',
            array(
                'type' => 'number',
                'sanitize_callback' => array($this, 'sanitize_tax_rate'),
                'default' => 0
            )
        );

        register_setting(
            'stripe_pos_terminals_group',
            'stripe_auto_select_terminal',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => true
            )
        );

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

        echo sprintf(
            '<input type="number" id="%1$s" step="0.01" min="0" max="100" name="%1$s" value="%2$s" class="small-text"%3$s /> %%',
            'stripe_sales_tax',
            esc_attr($stripe_sales_tax),
            disabled($enable_tax !== '1', true, false)
        );
        echo wp_kses(
            '<p class="description">Enter your sales tax rate as a percentage (e.g., 10.25 for 10.25%). Set to 0 for no tax.</p>',
            array(
                'p' => array('class' => array()),
            )
        );
    }

    function stripe_auto_select_terminal_callback()
    {
        $auto_select = get_option('stripe_auto_select_terminal', '1');
        echo '<input type="checkbox" id="stripe_auto_select_terminal" name="stripe_auto_select_terminal" value="1" ' . checked('1', $auto_select, false) . ' />';
        echo '<p class="description">Automatically select the first available terminal when discovering readers.</p>';
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

        if (!current_user_can('manage_options')) {
            return;
        }

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
                return new WP_Error('missing_location', 'Stripe POS location ID not configured');
            }

            $readers = \Stripe\Terminal\Reader::all([
                'location' => $stripe_pos_id,
                'limit' => 10,
            ]);

            return $readers->data;
        } catch (Exception $e) {
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
        $currency = isset($_POST['currency']) ? sanitize_text_field(wp_unslash($_POST['currency'])) : 'usd';

        $metadata = isset($_POST['metadata']) && is_array($_POST['metadata'])
            ? array_map(function ($value) {
                if (is_array($value)) {
                    return array_map('sanitize_text_field', $value);
                }
                return sanitize_text_field($value);
            }, wp_unslash($_POST['metadata']))
            : array();

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

        $reader_id = sanitize_text_field(wp_unslash($_POST['reader_id']));
        $payment_intent_id = sanitize_text_field(wp_unslash($_POST['payment_intent_id']));

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

        $payment_intent_id = sanitize_text_field(wp_unslash($_POST['payment_intent_id']));
        $cart_items = array_map(function ($item) {
            return array(
                'product_id'    => absint($item['product_id']),
                'quantity'      => absint($item['quantity']),
                'name'          => sanitize_text_field($item['name']),
                'price'         => floatval($item['price']),
                'variation_id'  => isset($item['variation_id']) ? absint($item['variation_id']) : 0,
                'meta_data'     => isset($item['meta_data']) ? array_map('sanitize_text_field', $item['meta_data']) : array()
            );
        }, json_decode(wp_unslash($_POST['cart_items'], true) ?? []));
        $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
        $reader_id = isset($_POST['reader_id']) ? sanitize_text_field(wp_unslash($_POST['reader_id'])) : '';

        $result = $this->check_payment_intent_status($payment_intent_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            if ($result['status'] === 'succeeded') {
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

        $payment_intent_id = sanitize_text_field(wp_unslash($_POST['payment_intent_id']));

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

        $reader_id = sanitize_text_field(wp_unslash($_POST['reader_id']));
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

        wp_register_script(
            'stripe-terminal-pos',
            plugins_url('/assets/js/main.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'stripe-terminal-pos',
            plugins_url('/assets/css/main.css', __FILE__),
            [],
            '1.0.0'
        );

        $wc_currency = get_woocommerce_currency();
        wp_localize_script('stripe-terminal-pos', 'stripe_terminal_pos', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('stripe_terminal_nonce'),
            'enable_tax' => get_option('stripe_enable_tax', '0') === '1',
            'sales_tax_rate' => floatval(get_option('stripe_sales_tax', '0')) / 100,
            'auto_select_terminal' => get_option('stripe_auto_select_terminal', '1') === '1',
            'currency' => $wc_currency,
            'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
            'currency_supported' => $this->is_currency_supported($wc_currency),
            'supported_currencies' => array_keys($this->supported_currencies)
        ]);
    }

    /**
     * Add a shortcode to display a simple POS terminal interface
     */
    function stripe_terminal_pos_shortcode()
    {
        wp_enqueue_script('stripe-terminal-pos');
    }

    private function create_woocommerce_order($payment_data, $cart_items)
    {
        if (!class_exists('WooCommerce')) {
            return false;
        }

        try {

            $order = wc_create_order();


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


            if (isset($payment_data['tax']) && $payment_data['tax'] > 0) {
                $order->set_cart_tax($payment_data['tax']);
            }


            $order->set_payment_method('stripe_terminal');
            $order->set_payment_method_title('Stripe Terminal');


            $order->set_total($payment_data['amount']);


            if (!empty($payment_data['notes'])) {
                $order->add_order_note($payment_data['notes'], false, false);
            }


            $order->update_meta_data('_stripe_payment_intent_id', $payment_data['payment_intent_id']);
            $order->update_meta_data('_stripe_terminal_reader_id', $payment_data['reader_id']);


            $order->payment_complete($payment_data['payment_intent_id']);
            $order->add_order_note('Payment completed via Stripe Terminal POS');


            $order->save();

            return $order->get_id();
        } catch (Exception $e) {
            // error_log('Stripe Terminal: Error creating WooCommerce order - ' . $e->getMessage());
            return false;
        }
    }

    private function is_currency_supported($currency)
    {
        return isset($this->supported_currencies[strtolower($currency)]);
    }


    private function sanitize_checkbox($input)
    {
        return (isset($input) && true === (bool) $input) ? '1' : '0';
    }

    private function sanitize_tax_rate($input)
    {
        $number = floatval($input);
        return max(0, min(100, $number)); // Ensures value is between 0 and 100
    }
}


function stripe_terminal_pos_init()
{
    return StripeTerminalPOS::get_instance();
}


stripe_terminal_pos_init();
