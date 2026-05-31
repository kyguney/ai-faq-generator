<?php
/**
 * Class Loader
 *
 * Autoloader for plugin classes
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes;

use WPBits\AiFaqGenerator\Admin\Admin;

class Loader
{
    private array $classes = [];

    public function __construct()
    {
        $this->classes = [
            'WPBits\\AiFaqGenerator\\Admin\\Admin' => AFG_PLUGIN_PATH . 'admin/class-admin.php',
        ];
    }

    public function init(): void
    {
        spl_autoload_register([$this, 'autoload']);

        // Initialize admin if in admin area
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
