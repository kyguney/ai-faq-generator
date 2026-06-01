<?php
/**
 * Unit tests for FAQ Accordion Block registration.
 *
 * Validates: Requirements 1.1, 1.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BlockRegistrationTest extends TestCase
{
    /**
     * Actions recorded at bootstrap time (before setUp resets them).
     *
     * @var array<int, array{hook: string, callback: mixed, priority: int}>
     */
    private static array $bootstrap_actions = [];

    /**
     * Capture the actions recorded at bootstrap time (file load).
     */
    public static function setUpBeforeClass(): void
    {
        global $afg_test_actions;
        self::$bootstrap_actions = $afg_test_actions;
    }

    protected function setUp(): void
    {
        global $afg_test_actions,
               $afg_test_registered_blocks,
               $afg_test_register_block_type_return,
               $afg_test_error_log_messages;

        $afg_test_actions = [];
        $afg_test_registered_blocks = [];
        $afg_test_register_block_type_return = true;
        $afg_test_error_log_messages = [];
    }

    /**
     * Validates: Requirement 1.1
     * Test that register_faq_accordion_block() calls register_block_type() with the correct path.
     */
    #[Test]
    public function register_faq_accordion_block_calls_register_block_type_with_correct_path(): void
    {
        global $afg_test_registered_blocks, $afg_test_register_block_type_return;

        $afg_test_register_block_type_return = true;
        $afg_test_registered_blocks = [];

        \WPBits\AiFaqGenerator\Blocks\FaqAccordion\register_faq_accordion_block();

        $this->assertNotEmpty($afg_test_registered_blocks, 'register_block_type() should have been called.');

        $last_call = end($afg_test_registered_blocks);
        $expected_path = AFG_PLUGIN_PATH . 'blocks/faq-accordion';

        $this->assertSame($expected_path, $last_call['block_type']);
        $this->assertArrayHasKey('render_callback', $last_call['args']);
        $this->assertSame(
            'WPBits\\AiFaqGenerator\\Blocks\\FaqAccordion\\render_faq_accordion_block',
            $last_call['args']['render_callback']
        );
    }

    /**
     * Validates: Requirement 1.5
     * Test that error_log is called when register_block_type() returns false.
     */
    #[Test]
    public function register_faq_accordion_block_logs_error_when_registration_returns_false(): void
    {
        global $afg_test_register_block_type_return, $afg_test_registered_blocks;

        $afg_test_register_block_type_return = false;
        $afg_test_registered_blocks = [];

        $temp_file = tempnam(sys_get_temp_dir(), 'afg_test_');
        $original_log = ini_get('error_log');
        ini_set('error_log', $temp_file);

        \WPBits\AiFaqGenerator\Blocks\FaqAccordion\register_faq_accordion_block();

        ini_set('error_log', $original_log ?: '');

        $log_content = file_get_contents($temp_file);
        unlink($temp_file);

        $this->assertStringContainsString(
            'FAQ Accordion Block: Failed to register block.',
            $log_content
        );
    }

    /**
     * Validates: Requirement 1.5
     * Test that error_log is called when register_block_type() returns WP_Error.
     */
    #[Test]
    public function register_faq_accordion_block_logs_error_when_registration_returns_wp_error(): void
    {
        global $afg_test_register_block_type_return, $afg_test_registered_blocks;

        $afg_test_register_block_type_return = new \WP_Error('block_error', 'Registration failed');
        $afg_test_registered_blocks = [];

        $temp_file = tempnam(sys_get_temp_dir(), 'afg_test_');
        $original_log = ini_get('error_log');
        ini_set('error_log', $temp_file);

        \WPBits\AiFaqGenerator\Blocks\FaqAccordion\register_faq_accordion_block();

        ini_set('error_log', $original_log ?: '');

        $log_content = file_get_contents($temp_file);
        unlink($temp_file);

        $this->assertStringContainsString(
            'FAQ Accordion Block: Failed to register block.',
            $log_content
        );
    }

    /**
     * Validates: Requirement 1.1
     * Test that the block registration is hooked to the 'init' action.
     *
     * The add_action() call happens at file load time when class-faq-accordion-block.php
     * is included by the bootstrap. We check the actions captured at that time.
     */
    #[Test]
    public function block_registration_is_hooked_to_init_action(): void
    {
        $init_hooks = array_filter(self::$bootstrap_actions, function ($action) {
            return $action['hook'] === 'init'
                && $action['callback'] === 'WPBits\\AiFaqGenerator\\Blocks\\FaqAccordion\\register_faq_accordion_block';
        });

        $this->assertNotEmpty(
            $init_hooks,
            'register_faq_accordion_block should be hooked to the init action.'
        );
    }

    /**
     * Validates: Requirement 1.1
     * Test that no error is logged when registration succeeds.
     */
    #[Test]
    public function register_faq_accordion_block_does_not_log_error_on_success(): void
    {
        global $afg_test_register_block_type_return, $afg_test_registered_blocks;

        $afg_test_register_block_type_return = true;
        $afg_test_registered_blocks = [];

        $temp_file = tempnam(sys_get_temp_dir(), 'afg_test_');
        $original_log = ini_get('error_log');
        ini_set('error_log', $temp_file);

        \WPBits\AiFaqGenerator\Blocks\FaqAccordion\register_faq_accordion_block();

        ini_set('error_log', $original_log ?: '');

        $log_content = file_get_contents($temp_file);
        unlink($temp_file);

        $this->assertStringNotContainsString(
            'FAQ Accordion Block: Failed to register block.',
            $log_content
        );
    }
}
