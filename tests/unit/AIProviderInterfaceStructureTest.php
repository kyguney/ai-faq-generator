<?php
/**
 * Unit tests for the AIProviderInterface structure.
 *
 * Validates: Requirements 1.3, 1.4, 2.1, 2.2, 2.3, 3.3, 5.1
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;

class AIProviderInterfaceStructureTest extends TestCase
{
    private string $interfaceFilePath;

    protected function setUp(): void
    {
        $this->interfaceFilePath = dirname(__DIR__, 2) . '/includes/interfaces/class-ai-provider-interface.php';
    }

    /**
     * Validates: Requirement 1.3
     * Verify the interface file exists at the expected path.
     */
    #[Test]
    public function interface_file_exists_at_expected_path(): void
    {
        $this->assertFileExists($this->interfaceFilePath);
    }

    /**
     * Validates: Requirement 1.4
     * Verify the interface uses the correct namespace.
     */
    #[Test]
    public function interface_has_correct_namespace(): void
    {
        $reflection = new ReflectionClass(AIProviderInterface::class);

        $this->assertSame(
            'WPBits\AiFaqGenerator\Includes\Interfaces',
            $reflection->getNamespaceName()
        );
    }

    /**
     * Validates: Requirement 2.1
     * Verify the interface has a class-level PHPDoc block.
     */
    #[Test]
    public function interface_has_class_level_phpdoc_block(): void
    {
        $reflection = new ReflectionClass(AIProviderInterface::class);
        $docComment = $reflection->getDocComment();

        $this->assertNotFalse($docComment, 'Interface should have a PHPDoc block');
        $this->assertStringContainsString('Interface AIProviderInterface', $docComment);
        $this->assertStringContainsString('@package', $docComment);
    }

    /**
     * Validates: Requirement 2.2
     * Verify generateFaqs method has a PHPDoc block with @param and @return.
     */
    #[Test]
    public function generate_faqs_method_has_phpdoc_with_param_and_return(): void
    {
        $reflection = new ReflectionClass(AIProviderInterface::class);
        $method = $reflection->getMethod('generateFaqs');
        $docComment = $method->getDocComment();

        $this->assertNotFalse($docComment, 'generateFaqs should have a PHPDoc block');
        $this->assertStringContainsString('@param string $prompt', $docComment);
        $this->assertStringContainsString('@return array', $docComment);
    }

    /**
     * Validates: Requirement 2.3
     * Verify testConnection method has a PHPDoc block with @return.
     */
    #[Test]
    public function test_connection_method_has_phpdoc_with_return(): void
    {
        $reflection = new ReflectionClass(AIProviderInterface::class);
        $method = $reflection->getMethod('testConnection');
        $docComment = $method->getDocComment();

        $this->assertNotFalse($docComment, 'testConnection should have a PHPDoc block');
        $this->assertStringContainsString('@return bool', $docComment);
    }

    /**
     * Validates: Requirement 3.3
     * Verify the file includes declare(strict_types=1).
     */
    #[Test]
    public function interface_file_declares_strict_types(): void
    {
        $contents = file_get_contents($this->interfaceFilePath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('declare(strict_types=1)', $contents);
    }

    /**
     * Validates: Requirement 5.1
     * Verify the Loader class map contains the interface entry.
     */
    #[Test]
    public function loader_class_map_contains_interface_entry(): void
    {
        $loaderFilePath = dirname(__DIR__, 2) . '/includes/class-loader.php';
        $contents = file_get_contents($loaderFilePath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString(
            'WPBits\\\\AiFaqGenerator\\\\Includes\\\\Interfaces\\\\AIProviderInterface',
            $contents
        );
        $this->assertStringContainsString(
            'includes/interfaces/class-ai-provider-interface.php',
            $contents
        );
    }
}
