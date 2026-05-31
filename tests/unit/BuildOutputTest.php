<?php
/**
 * Integration tests for build output verification.
 *
 * Validates: Requirements 6.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BuildOutputTest extends TestCase
{
    private string $buildDir;

    protected function setUp(): void
    {
        $this->buildDir = AFG_PLUGIN_PATH . 'build/';
    }

    /**
     * Validates: Requirement 6.5
     * Assert build/index.js exists after build.
     */
    #[Test]
    public function build_produces_index_js(): void
    {
        $this->assertFileExists(
            $this->buildDir . 'index.js',
            'Build output build/index.js should exist after running npm run build'
        );
    }

    /**
     * Validates: Requirement 6.5
     * Assert build/index.asset.php exists after build.
     */
    #[Test]
    public function build_produces_index_asset_php(): void
    {
        $this->assertFileExists(
            $this->buildDir . 'index.asset.php',
            'Build output build/index.asset.php should exist after running npm run build'
        );
    }

    /**
     * Validates: Requirement 6.5
     * Assert build/index.asset.php returns a valid array with dependencies and version keys.
     */
    #[Test]
    public function index_asset_php_returns_valid_array(): void
    {
        $assetFile = $this->buildDir . 'index.asset.php';

        $this->assertFileExists($assetFile);

        $asset = include $assetFile;

        $this->assertIsArray($asset, 'index.asset.php should return an array');
        $this->assertArrayHasKey('dependencies', $asset, 'Asset array should have a "dependencies" key');
        $this->assertArrayHasKey('version', $asset, 'Asset array should have a "version" key');
    }
}
