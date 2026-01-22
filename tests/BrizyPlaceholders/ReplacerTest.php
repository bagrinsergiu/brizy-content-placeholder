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
        echo "Extract time: " . (microtime(true) - $t) . "s\n";

        $t = microtime(true);

        $toReplaceWithValues = [];
        $toReplace = [];
        foreach ($contentPlaceholders as $i => $contentPlaceholder) {
            $toReplace[] = $contentPlaceholder->getUid();
            $toReplaceWithValues[] = md5($contentPlaceholder->getUid());
            usleep(1000);
        }

        $content = str_replace($toReplace, $toReplaceWithValues, $content);


        echo "Replace time: " . (microtime(true) - $t) . "s\n";
        $this->assertCount(715, $contentPlaceholders, 'It should return 50 placeholder');
    }
}
