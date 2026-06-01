<?php
/**
 * Class Loader
 *
 * Autoloader for plugin classes
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes;

use WPBits\AiFaqGenerator\Admin\Admin;
use WPBits\AiFaqGenerator\Admin\Settings;

class Loader
{
    private array $classes = [];

    public function __construct()
    {
        $this->classes = [
            'WPBits\\AiFaqGenerator\\Admin\\Admin' => AFG_PLUGIN_PATH . 'admin/class-admin.php',
            'WPBits\\AiFaqGenerator\\Admin\\Settings' => AFG_PLUGIN_PATH . 'admin/class-settings.php',
            'WPBits\\AiFaqGenerator\\Includes\\Interfaces\\AIProviderInterface' => AFG_PLUGIN_PATH . 'includes/interfaces/class-ai-provider-interface.php',
            'WPBits\\AiFaqGenerator\\Includes\\OpenAIClient' => AFG_PLUGIN_PATH . 'includes/class-openai-client.php',
            'WPBits\\AiFaqGenerator\\Includes\\Services\\Prompt_Builder' => AFG_PLUGIN_PATH . 'includes/services/class-prompt-builder.php',
            'WPBits\\AiFaqGenerator\\Includes\\Services\\Faq_Generator' => AFG_PLUGIN_PATH . 'includes/services/class-faq-generator.php',
            'WPBits\\AiFaqGenerator\\Includes\\Services\\Faq_Parser' => AFG_PLUGIN_PATH . 'includes/services/class-faq-parser.php',
            'WPBits\\AiFaqGenerator\\Includes\\Ajax_Generate_Faqs' => AFG_PLUGIN_PATH . 'includes/class-ajax-generate-faqs.php',
        ];
    }

    public function init(): void
    {
        spl_autoload_register([$this, 'autoload']);

        // Register FAQ post meta on init hook.
        add_action('init', [$this, 'register_faq_meta']);

        // Initialize AJAX handler (registered outside is_admin() since
        // wp_ajax hooks route through admin-ajax.php which sets is_admin() true,
        // but registering early ensures the hook is always available).
        $ajax_generate_faqs = new Ajax_Generate_Faqs();
        $ajax_generate_faqs->init();

        // Settings REST routes must be registered on all requests (not just admin)
        // because REST API requests don't pass is_admin() check.
        $settings = new Settings();
        $settings->init();

        // Initialize admin-only functionality
        if (is_admin()) {
            $admin = new Admin();
            $admin->init();
        }
    }

    /**
     * Register the _aifaq_generated_faqs post meta field.
     */
    public function register_faq_meta(): void
    {
        register_meta('post', '_aifaq_generated_faqs', [
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => [$this, 'sanitize_faq_meta'],
            'auth_callback'     => function ($allowed, $meta_key, $post_id) {
                return current_user_can('edit_post', $post_id);
            },
        ]);
    }

    /**
     * Sanitize callback for FAQ meta.
     *
     * Validates that the value is a JSON string representing an array of
     * objects each containing "question" and "answer" string keys.
     *
     * @param string $value The meta value to sanitize.
     * @return string The value unchanged if valid, or empty string if invalid.
     */
    public function sanitize_faq_meta($value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

        $decoded = json_decode($value, true);

        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }

        // Must be a sequential array (not associative).
        if (array_values($decoded) !== $decoded) {
            return '';
        }

        foreach ($decoded as $item) {
            if (!is_array($item)) {
                return '';
            }

            if (!isset($item['question']) || !isset($item['answer'])) {
                return '';
            }

            if (!is_string($item['question']) || !is_string($item['answer'])) {
                return '';
            }
        }

        return $value;
    }

    private function autoload(string $class): void
    {
        if (isset($this->classes[$class])) {
            require_once $this->classes[$class];
        }
    }
}
