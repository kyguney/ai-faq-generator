<?php
/**
 * Plugin Name: AI FAQ Generator
 * Plugin URI: https://wpbits.net/ai-faq-generator
 * Description: AI-powered FAQ generation for WordPress
 * Version: 1.0.0
 * Author: WPBits
 * Author URI: https://wpbits.net
 * Text Domain: ai-faq-generator
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AFG_PLUGIN_VERSION', '1.0.0');
define('AFG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AFG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AFG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize the plugin
 */
function init(): void
{
    // Load dependencies
    require_once AFG_PLUGIN_PATH . 'includes/class-loader.php';

    // Initialize components
    $loader = new Includes\Loader();
    $loader->init();
}

// Start the plugin after WordPress is fully loaded
add_action('plugins_loaded', __NAMESPACE__ . '\\init');
