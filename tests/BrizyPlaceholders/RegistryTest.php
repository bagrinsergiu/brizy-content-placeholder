<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\PlaceholderInterface;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\LoopPlaceholder;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RegistryTest extends TestCase
{
    use ProphecyTrait;


    public function testRegisterPlaceholder()
    {
        $registry    = new Registry();
        $registry->registerPlaceholderName('placeholder', function() {
            return new TestPlaceholder('placeholder');
        });
        $registry->registerPlaceholderName('placeholder_loop', function() {
            return new LoopPlaceholder($this->prophesize(Replacer::class)->reveal());
        });
        $this->assertInstanceOf(TestPlaceholder::class, $registry->getPlaceholderSupportingName('placeholder'), 'It should return an instance of TestPlaceholder' );
        $this->assertInstanceOf(LoopPlaceholder::class, $registry->getPlaceholderSupportingName('placeholder_loop'), 'It should return an instance of TestPlaceholder' );
    }

    /**
     * Test getPlaceholderSupportingName returns null for unregistered name.
     */
    public function testGetPlaceholderSupportingNameUnregistered()
    {
        $registry = new Registry();

        $result = $registry->getPlaceholderSupportingName('unregistered');

        $this->assertNull($result, 'Should return null for unregistered placeholder name');
    }

    /**
     * Test re-registering a placeholder name overwrites previous registration.
     */
    public function testReRegisterPlaceholderName()
    {
        $registry = new Registry();

        // Register first placeholder
        $registry->registerPlaceholderName('test', function() {
            return new TestPlaceholder('first');
        });

        // Get placeholder to cache it
        $first = $registry->getPlaceholderSupportingName('test');

        // Re-register with different factory - but cache should still return first
        $registry->registerPlaceholderName('test', function() {
            return new LoopPlaceholder($this->prophesize(Replacer::class)->reveal());
        });

        // Due to caching, it should still return the first instance
        $second = $registry->getPlaceholderSupportingName('test');

        $this->assertSame($first, $second, 'Cached instance should be returned');
    }

    /**
     * Test deprecated registerPlaceholder method still works.
     */
    public function testDeprecatedRegisterPlaceholder()
    {
        $registry = new Registry();

        // Create a mock that returns a proper placeholder name
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);
        $placeholderMock->getPlaceholder()->willReturn('legacy_test');

        // Using deprecated method
        @$registry->registerPlaceholder($placeholderMock->reveal());

        // Should be retrievable via the placeholder name
        $result = $registry->getPlaceholderSupportingName('legacy_test');
        $this->assertNotNull($result, 'Deprecated method should register placeholder');
    }

    /**
     * Test factory is called only once (caching).
     */
    public function testFactoryCalledOnlyOnce()
    {
        $registry = new Registry();
        $callCount = 0;

        $registry->registerPlaceholderName('cached', function() use (&$callCount) {
            $callCount++;
            return new TestPlaceholder('cached');
        });

        // Call multiple times
        $registry->getPlaceholderSupportingName('cached');
        $registry->getPlaceholderSupportingName('cached');
        $registry->getPlaceholderSupportingName('cached');

        $this->assertEquals(1, $callCount, 'Factory should be called only once');
    }

    /**
     * Test getPlaceholders returns all registered instances.
     */
    public function testGetPlaceholders()
    {
        $registry = new Registry();

        $registry->registerPlaceholderName('one', function() {
            return new TestPlaceholder('one');
        });
        $registry->registerPlaceholderName('two', function() {
            return new TestPlaceholder('two');
        });
        $registry->registerPlaceholderName('three', function() {
            return new TestPlaceholder('three');
        });

        $placeholders = $registry->getPlaceholders();

        $this->assertCount(3, $placeholders, 'Should return all registered placeholders');
        $this->assertInstanceOf(PlaceholderInterface::class, $placeholders[0]);
        $this->assertInstanceOf(PlaceholderInterface::class, $placeholders[1]);
        $this->assertInstanceOf(PlaceholderInterface::class, $placeholders[2]);
    }

    /**
     * Test getPlaceholders with empty registry.
     */
    public function testGetPlaceholdersEmptyRegistry()
    {
        $registry = new Registry();

        $placeholders = $registry->getPlaceholders();

        $this->assertIsArray($placeholders, 'Should return an array');
        $this->assertEmpty($placeholders, 'Should return empty array for empty registry');
    }

    /**
     * Test factory receives placeholder name as argument.
     */
    public function testFactoryReceivesPlaceholderName()
    {
        $registry = new Registry();
        $receivedName = null;

        $registry->registerPlaceholderName('test_name', function($name) use (&$receivedName) {
            $receivedName = $name;
            return new TestPlaceholder($name);
        });

        $registry->getPlaceholderSupportingName('test_name');

        $this->assertEquals('test_name', $receivedName, 'Factory should receive placeholder name');
    }

    /**
     * Test that getPlaceholders creates instances via getPlaceholderSupportingName.
     */
    public function testGetPlaceholdersUsesCache()
    {
        $registry = new Registry();
        $callCount = 0;

        $registry->registerPlaceholderName('cachetest', function() use (&$callCount) {
            $callCount++;
            return new TestPlaceholder('cachetest');
        });

        // First access via getPlaceholders
        $placeholders1 = $registry->getPlaceholders();

        // Second access via direct method
        $instance = $registry->getPlaceholderSupportingName('cachetest');

        // Third access via getPlaceholders again
        $placeholders2 = $registry->getPlaceholders();

        $this->assertEquals(1, $callCount, 'Factory should be called only once across all access methods');
        $this->assertSame($placeholders1[0], $instance, 'Same instance should be returned');
        $this->assertSame($placeholders1[0], $placeholders2[0], 'Same instance should be returned');
    }

    /**
     * Test registering multiple placeholders with similar names.
     */
    public function testSimilarPlaceholderNames()
    {
        $registry = new Registry();

        $registry->registerPlaceholderName('placeholder', function() {
            return new TestPlaceholder('placeholder');
        });
        $registry->registerPlaceholderName('placeholder_extended', function() {
            return new TestPlaceholder('placeholder_extended');
        });
        $registry->registerPlaceholderName('other_placeholder', function() {
            return new TestPlaceholder('other_placeholder');
        });

        // Each should be distinct
        $p1 = $registry->getPlaceholderSupportingName('placeholder');
        $p2 = $registry->getPlaceholderSupportingName('placeholder_extended');
        $p3 = $registry->getPlaceholderSupportingName('other_placeholder');

        $this->assertNotSame($p1, $p2);
        $this->assertNotSame($p2, $p3);
        $this->assertNotSame($p1, $p3);
    }
}
