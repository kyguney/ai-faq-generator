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
        ];
    }

    public function init(): void
    {
        spl_autoload_register([$this, 'autoload']);

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

    private function autoload(string $class): void
    {
        if (isset($this->classes[$class])) {
            require_once $this->classes[$class];
        }
    }
}
