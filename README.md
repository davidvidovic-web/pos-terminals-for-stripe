# POS Terminals integration for Stripe

Tested up to: 6.8
License: GPL v2 or later
Stable tag: 1.0.0
A WordPress plugin that integrates Stripe Terminal for Point of Sale (POS) payments using server-driven integration.

## Features

- Stripe Terminal integration
- WooCommerce order creation
- Product search and cart management
- Tax calculation support
- Multiple currency support
- Terminal reader auto-discovery
- Real-time payment status updates

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.2+
- Stripe account with Terminal access
- SSL certificate (for live mode)

## Installation

1. Upload the plugin files to `/wp-content/plugins/pos-terminals-for-stripe`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure your Stripe API keys in the plugin settings
4. Add your Terminal location ID in settings

## Configuration

1. Go to Stripe Terminals > Settings
2. Enter your Stripe API Key
3. Enter your Terminal Location ID
4. Configure tax settings if needed
5. Select your default currency

## Usage

Use the shortcode `[stripe_terminal_pos]` to display the POS interface on any page.

## License

GPL v2 or later
