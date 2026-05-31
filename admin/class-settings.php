<?php
/**
 * Settings class
 *
 * REST controller and sanitizer for plugin settings
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Admin;

class Settings
{
    const OPTION_KEY = 'afg_settings';
    const REST_NAMESPACE = 'ai-faq-generator/v1';
    const REST_ROUTE = '/settings';

    const DEFAULTS = [
        'provider'    => 'openai',
        'api_key'     => '',
        'model'       => 'gpt-4o',
        'temperature' => 0.7,
        'faq_count'   => 5,
    ];

    const ALLOWED_PROVIDERS = [
        'openai',
        'openrouter',
        'ollama',
        'deepseek',
        'localai',
        'lmstudio',
    ];

    /**
     * Initialize the Settings class by hooking into WordPress.
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes for settings.
     */
    public function register_routes(): void
    {
        register_rest_route(self::REST_NAMESPACE, self::REST_ROUTE, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'update_settings'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    /**
     * Check if the current user has permission to manage settings.
     *
     * @param \WP_REST_Request $request The REST request.
     * @return bool True if user has manage_options capability.
     */
    public function permission_check(\WP_REST_Request $request): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Handle GET request — return current settings with masked API key.
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response The settings response.
     */
    public function get_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $stored   = get_option(self::OPTION_KEY, []);
        $settings = array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);

        $has_api_key = !empty($settings['api_key']);

        $response = [
            'provider'    => $settings['provider'],
            'api_key'     => $has_api_key ? $this->mask_api_key($settings['api_key']) : '',
            'model'       => $settings['model'],
            'temperature' => (float) $settings['temperature'],
            'faq_count'   => (int) $settings['faq_count'],
            'has_api_key' => $has_api_key,
        ];

        return new \WP_REST_Response($response, 200);
    }

    /**
     * Handle POST request — sanitize and persist settings.
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response The updated settings response.
     */
    public function update_settings(\WP_REST_Request $request): \WP_REST_Response
    {
        $input     = $request->get_json_params();
        $sanitized = $this->sanitize($input);

        update_option(self::OPTION_KEY, $sanitized);

        $has_api_key = !empty($sanitized['api_key']);

        $response = [
            'success'  => true,
            'settings' => [
                'provider'    => $sanitized['provider'],
                'api_key'     => $has_api_key ? $this->mask_api_key($sanitized['api_key']) : '',
                'model'       => $sanitized['model'],
                'temperature' => (float) $sanitized['temperature'],
                'faq_count'   => (int) $sanitized['faq_count'],
                'has_api_key' => $has_api_key,
            ],
        ];

        return new \WP_REST_Response($response, 200);
    }

    /**
     * Sanitize and validate settings input.
     *
     * Validates provider against allowed list, clamps numeric fields,
     * applies sanitize_text_field() to text inputs, and rejects empty model.
     *
     * @param array $input Raw input values.
     * @return array Sanitized settings merged with current stored values.
     */
    public function sanitize(array $input): array
    {
        $current = get_option(self::OPTION_KEY, []);
        $current = array_merge(self::DEFAULTS, is_array($current) ? $current : []);

        $sanitized = [];

        // Provider: must be in allowed list, otherwise keep current
        if (isset($input['provider']) && in_array($input['provider'], self::ALLOWED_PROVIDERS, true)) {
            $sanitized['provider'] = $input['provider'];
        } else {
            $sanitized['provider'] = $current['provider'];
        }

        // Temperature: clamp to [0.0, 2.0]
        if (isset($input['temperature'])) {
            $temperature = (float) $input['temperature'];
            $sanitized['temperature'] = max(0.0, min(2.0, $temperature));
        } else {
            $sanitized['temperature'] = $current['temperature'];
        }

        // FAQ count: clamp to [1, 20]
        if (isset($input['faq_count'])) {
            $faq_count = (int) $input['faq_count'];
            $sanitized['faq_count'] = max(1, min(20, $faq_count));
        } else {
            $sanitized['faq_count'] = $current['faq_count'];
        }

        // Model: sanitize text, reject empty/whitespace-only
        if (isset($input['model'])) {
            $model = sanitize_text_field($input['model']);
            if (trim($model) !== '') {
                $sanitized['model'] = $model;
            } else {
                $sanitized['model'] = $current['model'];
            }
        } else {
            $sanitized['model'] = $current['model'];
        }

        // API key: sanitize text field
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        } else {
            $sanitized['api_key'] = $current['api_key'];
        }

        return $sanitized;
    }

    /**
     * Mask an API key for safe display.
     *
     * Shows first 3 and last 4 characters, masks the middle with '****'.
     * Keys shorter than 7 characters are fully masked.
     *
     * @param string $key The raw API key.
     * @return string The masked key.
     */
    public function mask_api_key(string $key): string
    {
        if (strlen($key) < 7) {
            return str_repeat('*', strlen($key));
        }

        $first = substr($key, 0, 3);
        $last  = substr($key, -4);

        return $first . '****' . $last;
    }
}
