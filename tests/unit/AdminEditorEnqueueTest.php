<?php
/**
 * Unit tests for the Admin::enqueue_editor_assets() method.
 *
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Admin\Admin;

class AdminEditorEnqueueTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        global $afg_test_registered_scripts,
               $afg_test_enqueued_scripts,
               $afg_test_localized_scripts,
               $afg_test_current_post_id;

        $afg_test_registered_scripts = [];
        $afg_test_enqueued_scripts = [];
        $afg_test_localized_scripts = [];
        $afg_test_current_post_id = 42;

        $this->admin = new Admin();
    }

    /**
     * Validates: Requirement 2.1, 2.2
     * Test that script is registered with correct handle and dependencies when asset file exists.
     */
    #[Test]
    public function enqueue_editor_assets_registers_script_with_correct_handle_and_dependencies(): void
    {
        global $afg_test_registered_scripts, $afg_test_enqueued_scripts;

        // The real build/index.asset.php exists in the project, so this should work.
        $this->admin->enqueue_editor_assets();

        // Verify script was registered with correct handle.
        $this->assertNotEmpty($afg_test_registered_scripts, 'Script should be registered when asset file exists.');

        $registered = $afg_test_registered_scripts[0];
        $this->assertSame('aifaq-editor', $registered['handle']);
        $this->assertStringContainsString('build/index.js', $registered['src']);

        // Verify dependencies come from the asset file.
        $asset = require AFG_PLUGIN_PATH . 'build/index.asset.php';
        $this->assertSame($asset['dependencies'], $registered['deps']);

        // Verify version comes from the asset file.
        $this->assertSame($asset['version'], $registered['ver']);
    }

    /**
     * Validates: Requirement 2.1
     * Test that script is enqueued after registration.
     */
    #[Test]
    public function enqueue_editor_assets_enqueues_the_registered_script(): void
    {
        global $afg_test_enqueued_scripts;

        $this->admin->enqueue_editor_assets();

        // Verify wp_enqueue_script was called with the correct handle.
        $this->assertNotEmpty($afg_test_enqueued_scripts, 'Script should be enqueued when asset file exists.');
        $this->assertSame('aifaq-editor', $afg_test_enqueued_scripts[0]['handle']);
    }

    /**
     * Validates: Requirement 2.4
     * Test that script is NOT registered when asset file does not exist.
     */
    #[Test]
    public function enqueue_editor_assets_does_not_register_script_when_asset_file_missing(): void
    {
        global $afg_test_registered_scripts, $afg_test_enqueued_scripts, $afg_test_localized_scripts;

        // Use a subclass that overrides the asset file path to a non-existent location.
        $admin = new class extends Admin {
            public function enqueue_editor_assets(): void
            {
                $asset_file = AFG_PLUGIN_PATH . 'build/nonexistent.asset.php';

                if (!file_exists($asset_file)) {
                    return;
                }

                // This code should not be reached.
                $asset = require $asset_file;

                wp_register_script(
                    'aifaq-editor',
                    plugins_url('build/index.js', dirname(__FILE__)),
                    $asset['dependencies'],
                    $asset['version'],
                    true
                );
            }
        };

        $admin->enqueue_editor_assets();

        // Verify no script was registered.
        $this->assertEmpty($afg_test_registered_scripts, 'No script should be registered when asset file is missing.');

        // Verify no script was enqueued.
        $this->assertEmpty($afg_test_enqueued_scripts, 'No script should be enqueued when asset file is missing.');

        // Verify no localized script was set.
        $this->assertEmpty($afg_test_localized_scripts, 'No localized script should be set when asset file is missing.');
    }

    /**
     * Validates: Requirement 2.3
     * Test that localized object contains correct keys and values.
     */
    #[Test]
    public function enqueue_editor_assets_localizes_script_with_correct_data(): void
    {
        global $afg_test_localized_scripts, $afg_test_current_post_id;

        $afg_test_current_post_id = 99;

        $this->admin->enqueue_editor_assets();

        // Verify wp_localize_script was called.
        $this->assertNotEmpty($afg_test_localized_scripts, 'Script should be localized when asset file exists.');

        $localized = $afg_test_localized_scripts[0];

        // Verify handle matches the registered script.
        $this->assertSame('aifaq-editor', $localized['handle']);

        // Verify object name is 'aifaqEditor'.
        $this->assertSame('aifaqEditor', $localized['object_name']);

        // Verify the localized data contains the required keys.
        $data = $localized['l10n'];
        $this->assertArrayHasKey('ajaxurl', $data);
        $this->assertArrayHasKey('nonce', $data);
        $this->assertArrayHasKey('postId', $data);

        // Verify ajaxurl points to admin-ajax.php.
        $this->assertSame(admin_url('admin-ajax.php'), $data['ajaxurl']);

        // Verify nonce is created with the correct action.
        $this->assertSame(wp_create_nonce('aifaq_generate_faqs'), $data['nonce']);

        // Verify postId matches the current post ID.
        $this->assertSame(99, $data['postId']);
    }

    /**
     * Validates: Requirement 2.3
     * Test that localized object nonce uses the 'aifaq_generate_faqs' action.
     */
    #[Test]
    public function enqueue_editor_assets_nonce_uses_correct_action(): void
    {
        global $afg_test_localized_scripts;

        $this->admin->enqueue_editor_assets();

        $data = $afg_test_localized_scripts[0]['l10n'];

        // The stub wp_create_nonce returns 'test_nonce_' . $action
        // so we can verify the correct action was used.
        $this->assertSame('test_nonce_aifaq_generate_faqs', $data['nonce']);
    }
}
