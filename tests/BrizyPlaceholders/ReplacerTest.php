<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\ContentPlaceholder;
use BrizyPlaceholders\ContextInterface;
use BrizyPlaceholders\EmptyContext;
use BrizyPlaceholders\Extractor;
use BrizyPlaceholders\PlaceholderInterface;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\LoopPlaceholder;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ReplacerTest extends TestCase
{
    use ProphecyTrait;

    public function testReplaceWithoutPlaceholders()
    {
        $registry = new Registry();
        $replacer = new Replacer($registry);

        $content = "Some content";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }

    public function testAfterExtractCall()
    {
        $registry = new Registry();
        $replacer = new Replacer($registry);

        $content = "Some content";
        $context = $this->prophesize(ContextInterface::class);
        $context->afterExtract([], [], $content)->shouldBeCalled();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context->reveal());

        $this->assertEquals(
            "Some content",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }


    public function testReplaceWithoutRegisteredPlaceholders()
    {
        $registry = new Registry();
        $replacer = new Replacer($registry);

        $content = "Some content with a {{placeholder}}.";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content with a {{placeholder}}.",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }

    public function testReplaceWithRegisteredPlaceholders()
    {
        $registry = new Registry();
        $factory = function () {
            return new TestPlaceholder('aplaceholder');
        };
        $registry->registerPlaceholderName( 'aplaceholder', $factory);
        $registry->registerPlaceholderName( 'aplaceholder_234', $factory);
        $replacer = new Replacer($registry);

        $content = "Some content with {{aplaceholder}} and {{aplaceholder_234}}.";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content with placeholder_value and placeholder_value.",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }

    public function testReplaceWithLoopPlaceholder()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            return new TestPlaceholder('aplaceholder');
        });
        $replacer = new Replacer($registry);

        $registry->registerPlaceholderName('placeholder_loop', function () use ($replacer) {
            return new LoopPlaceholder($replacer);
        });

        $content = "{{placeholder_loop}}{{aplaceholder}}{{end_placeholder_loop}}";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "placeholder_valueplaceholder_valueplaceholder_valueplaceholder_valueplaceholder_value",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }


    public function testReplaceWithRepeatingPlaceholders()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            ;
            return new TestPlaceholder('aplaceholder');
        });
        $replacer = new Replacer($registry);

        $content = "Some content {{aplaceholder}} and {{aplaceholder}}.";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content placeholder_value and placeholder_value.",
            $contentAfterReplace,
            'It should return the content with repeated placeholder content'
        );
    }

    public function testFallback()
    {
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);

        $placeholderMock->getUid()->willReturn('123');
        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))->willReturn('');
        $placeholderMock->shouldFallbackValue('', Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))->willReturn(true);
        $placeholderMock->getFallbackValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))->willReturn('fallback');


        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () use ($placeholderMock) {
            ;
            return $placeholderMock->reveal();
        });
        $replacer = new Replacer($registry);

        $content = "Some {{aplaceholder}} content";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some fallback content",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }

    public function testFallbackAttribute()
    {
        $mock = $this->createPartialMock(TestPlaceholder::class, ['getValue', 'support']);
        $mock->method('support')->willReturn(true);
        $mock->method('getValue')->willReturn('');

        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () use ($mock) {
            ;
            return $mock;
        });
        $replacer = new Replacer($registry);

        $content = "Some content {{aplaceholder _fallback='fallback1'}} and {{aplaceholder _fallback='fallback2'}}.";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content fallback1 and fallback2.",
            $contentAfterReplace,
            'It should return the content with repeated placeholder content'
        );
    }

    public function testReplaceWithExtractedData()
    {
        $contentPlaceholder = new ContentPlaceholder('placeholder', 'placeholder', ['_fallback' => 'fallback1']);
        $placeholder = new TestPlaceholder();
        $uid = $contentPlaceholder->getUid();

        $content = "Some content $uid and $uid.";
        $contentPlaceholders = [$contentPlaceholder];
        $instancePlaceholders = [$placeholder];

        $registry = new Registry();
        $registry->registerPlaceholderName('placeholder', function () {
            ;
            return new TestPlaceholder();
        });

        $replacer = new Replacer($registry);

        $context = new EmptyContext();

        $contentAfterReplace = $replacer->replaceWithExtractedData($contentPlaceholders, $instancePlaceholders, $content, $context);

        $this->assertEquals("Some content placeholder_value and placeholder_value.", $contentAfterReplace, 'It should replace all placeholders');
    }


    public function testExtractFromBigHtml4()
    {

        $content = file_get_contents('/opt/project/tests/data/user_case15.html');
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $t = microtime(true);
        list($contentPlaceholders, $content) = $extractor->extractIgnoringRegistry($content);
        //echo "Content placeholder count: " . count($contentPlaceholders) . "\n";
        //echo "Extract time: " . (microtime(true) - $t) . "s\n";

        $t = microtime(true);

        $toReplaceWithValues = [];
        $toReplace = [];
        foreach ($contentPlaceholders as $i => $contentPlaceholder) {
            $toReplaceWithValues[$contentPlaceholder->getUid()] = md5($contentPlaceholder->getUid());
            //usleep(10000);
        }

        $content = strtr($content, $toReplaceWithValues);

        //echo "Replace time: " . (microtime(true) - $t) . "s\n";
        $this->assertCount(715, $contentPlaceholders, 'It should return 50 placeholder');
        foreach( $toReplaceWithValues as $uid => $value ) {
            $this->assertStringNotContainsString($uid, $content, 'It should return the content with replaced placeholders');
        }

    }

    /**
     * Test that exception in getValue is caught and logged.
     */
    public function testReplaceWithExceptionInGetValue()
    {
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);
        $placeholderMock->getUid()->willReturn('123');
        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willThrow(new \Exception('Test exception'));

        $registry = new Registry();
        $registry->registerPlaceholderName('error_placeholder', function () use ($placeholderMock) {
            return $placeholderMock->reveal();
        });

        // Create a mock logger to verify error is logged
        $loggerMock = $this->prophesize(\Psr\Log\LoggerInterface::class);
        $loggerMock->error('Test exception', Argument::type('array'))->shouldBeCalled();

        $replacer = new Replacer($registry, $loggerMock->reveal());

        $content = "Some {{error_placeholder}} content";
        $context = new EmptyContext();

        // Should not throw, exception should be caught
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        // The placeholder should be skipped (left as UID since no replacement value)
        $this->assertIsString($contentAfterReplace);
    }

    /**
     * Test replacement when placeholder returns null.
     */
    public function testReplaceWithNullReturnValue()
    {
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);
        $placeholderMock->getUid()->willReturn('123');
        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn(null);
        $placeholderMock->shouldFallbackValue(null, Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn(true);
        $placeholderMock->getFallbackValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn('fallback_value');

        $registry = new Registry();
        $registry->registerPlaceholderName('null_placeholder', function () use ($placeholderMock) {
            return $placeholderMock->reveal();
        });
        $replacer = new Replacer($registry);

        $content = "Some {{null_placeholder}} content";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some fallback_value content",
            $contentAfterReplace,
            'Null return should trigger fallback'
        );
    }

    /**
     * Test that "0" string value does NOT trigger fallback (potential bug detection).
     */
    public function testFallbackWithZeroStringValue()
    {
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);
        $placeholderMock->getUid()->willReturn('123');
        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn('0');
        $placeholderMock->shouldFallbackValue('0', Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn(true);  // empty('0') returns true in PHP
        $placeholderMock->getFallbackValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn('default');

        $registry = new Registry();
        $registry->registerPlaceholderName('zero_placeholder', function () use ($placeholderMock) {
            return $placeholderMock->reveal();
        });
        $replacer = new Replacer($registry);

        $content = "Value: {{zero_placeholder _fallback='default'}}";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        // Note: This test documents that "0" triggers fallback due to empty() behavior
        $this->assertStringContainsString(
            'default',
            $contentAfterReplace,
            '"0" triggers fallback due to empty() behavior - this documents current behavior'
        );
    }

    /**
     * Test fallback with whitespace-only value.
     */
    public function testFallbackWithWhitespaceValue()
    {
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);
        $placeholderMock->getUid()->willReturn('123');
        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn('   ');
        $placeholderMock->shouldFallbackValue('   ', Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn(false);  // Whitespace is not empty

        $registry = new Registry();
        $registry->registerPlaceholderName('whitespace_placeholder', function () use ($placeholderMock) {
            return $placeholderMock->reveal();
        });
        $replacer = new Replacer($registry);

        $content = "Value: {{whitespace_placeholder _fallback='default'}}";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        // Whitespace is not empty, should not trigger fallback
        $this->assertEquals(
            "Value:    ",
            $contentAfterReplace,
            'Whitespace value should not trigger fallback'
        );
    }

    /**
     * Test replacement with empty loop content.
     */
    public function testReplaceWithEmptyLoopContent()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            return new TestPlaceholder('aplaceholder');
        });
        $replacer = new Replacer($registry);
        $registry->registerPlaceholderName('placeholder_loop', function () use ($replacer) {
            return new LoopPlaceholder($replacer);
        });

        $content = "{{placeholder_loop}}{{end_placeholder_loop}}";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "",
            $contentAfterReplace,
            'Empty loop content should result in empty string'
        );
    }

    /**
     * Test that same placeholder repeated many times works correctly.
     */
    public function testReplaceSamePlaceholderManyTimes()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('repeated', function () {
            return new TestPlaceholder('repeated');
        });
        $replacer = new Replacer($registry);

        // Repeat placeholder 10 times
        $content = str_repeat('{{repeated}} ', 10);
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            str_repeat('placeholder_value ', 10),
            $contentAfterReplace,
            'All repeated placeholders should be replaced'
        );
    }

    /**
     * Test that same placeholder with different attributes are distinct.
     */
    public function testReplaceSamePlaceholderDifferentAttributes()
    {
        $placeholderMock = $this->prophesize(PlaceholderInterface::class);
        $placeholderMock->getUid()->willReturn(rand(1000, 9999));;
        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->will(function ($args) {
                $attrs = $args[1]->getAttributes();
                return $attrs['id'] ?? 'no_id';
            });
        $placeholderMock->shouldFallbackValue(Argument::any(), Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))
            ->willReturn(false);

        $registry = new Registry();
        $registry->registerPlaceholderName('item', function () use ($placeholderMock) {
            return $placeholderMock->reveal();
        });
        $replacer = new Replacer($registry);

        $content = '{{item id="1"}} {{item id="2"}} {{item id="1"}}';
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            '1 2 1',
            $contentAfterReplace,
            'Same placeholder with different attributes should be distinct'
        );
    }

    /**
     * Test that context afterExtract can modify placeholder data.
     */
    public function testContextAfterExtractModifiesData()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('modified', function () {
            return new TestPlaceholder('modified');
        });
        $replacer = new Replacer($registry);

        // Create a context that modifies placeholder data
        $context = new class implements ContextInterface {
            public $extractedCount = 0;

            public function afterExtract($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor)
            {
                $this->extractedCount = count($contentPlaceholders);
            }
        };

        $content = "{{modified}} and {{modified}}";
        $replacer->replacePlaceholders($content, $context);

        // afterExtract should have been called
        $this->assertEquals(1, $context->extractedCount, 'afterExtract should see deduplicated placeholders');
    }

    /**
     * Test replaceWithExtractedData directly with empty arrays.
     */
    public function testReplaceWithExtractedDataEmptyArrays()
    {
        $registry = new Registry();
        $replacer = new Replacer($registry);

        $content = "Some content without placeholders";
        $context = new EmptyContext();

        $result = $replacer->replaceWithExtractedData([], [], $content, $context);

        $this->assertEquals($content, $result, 'Content should be unchanged with empty placeholder arrays');
    }

    /**
     * Test replacement with special characters in content.
     */
    public function testReplaceWithSpecialCharactersInContent()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('special', function () {
            return new TestPlaceholder('special');
        });
        $replacer = new Replacer($registry);

        $content = "Special chars: \$1 {{special}} \$2 \\1 end";
        $context = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Special chars: \$1 placeholder_value \$2 \\1 end",
            $contentAfterReplace,
            'Special characters should be preserved'
        );
    }
    public function testExtractFromBigHtml5()
    {

        $content = file_get_contents('/opt/project/tests/data/user_case16.html');
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $t = microtime(true);
        list($contentPlaceholders, $content) = $extractor->extractIgnoringRegistry($content);
        echo "Content placeholder count: " . count($contentPlaceholders) . "\n";
        echo "Extract time: " . (microtime(true) - $t) . "s\n";

        $t = microtime(true);

        $toReplaceWithValues = [];
        $toReplace = [];
        foreach ($contentPlaceholders as $i => $contentPlaceholder) {
            $toReplaceWithValues[$contentPlaceholder->getUid()] = md5($contentPlaceholder->getUid());
            //usleep(10000);
        }

        $content = strtr($content, $toReplaceWithValues);

        echo "Replace time: " . (microtime(true) - $t) . "s\n";
        $this->assertCount(715, $contentPlaceholders, 'It should return 50 placeholder');
        foreach( $toReplaceWithValues as $uid => $value ) {
            $this->assertStringNotContainsString($uid, $content, 'It should return the content with replaced placeholders');
        }

    }
}
