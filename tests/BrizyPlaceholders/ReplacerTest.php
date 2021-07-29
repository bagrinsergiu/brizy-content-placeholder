<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\ContentPlaceholder;
use BrizyPlaceholders\ContextInterface;
use BrizyPlaceholders\EmptyContext;
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
        $context->afterExtract([],[],$content)->shouldBeCalled();
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
        $registry->registerPlaceholder(new TestPlaceholder());
        $replacer = new Replacer($registry);

        $content = "Some content with {{placeholder}} and {{placeholder_234}}.";
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
        $registry->registerPlaceholder(new TestPlaceholder());
        $replacer = new Replacer($registry);
        $registry->registerPlaceholder(new LoopPlaceholder($replacer));

        $content = "{{placeholder_loop}}{{placeholder}}{{end_placeholder_loop}}";
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
        $registry->registerPlaceholder(new TestPlaceholder());
        $replacer = new Replacer($registry);

        $content = "Some content {{placeholder}} and {{placeholder}}.";
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
        $placeholderMock->support('placeholder')->willReturn(true);

        $placeholderMock->getValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))->willReturn('');
        $placeholderMock->shouldFallbackValue('', Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))->willReturn(true);
        $placeholderMock->getFallbackValue(Argument::type(ContextInterface::class), Argument::type(ContentPlaceholder::class))->willReturn('fallback');


        $registry = new Registry();
        $registry->registerPlaceholder($placeholderMock->reveal());
        $replacer = new Replacer($registry);

        $content = "Some {{placeholder}} content";
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
        $mock = $this->createPartialMock(TestPlaceholder::class, ['getValue']);
        $mock->method('getValue')->willReturn('');

        $registry = new Registry();
        $registry->registerPlaceholder($mock);
        $replacer = new Replacer($registry);

        $content = "Some content {{placeholder _fallback='fallback1'}} and {{placeholder _fallback='fallback2'}}.";
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
        $registry->registerPlaceholder(new TestPlaceholder());

        $replacer = new Replacer($registry);

        $context = new EmptyContext();

        $contentAfterReplace = $replacer->replaceWithExtractedData($contentPlaceholders, $instancePlaceholders, $content, $context);

        $this->assertEquals("Some content placeholder_value and placeholder_value.", $contentAfterReplace, 'It should replace all placeholders');
    }

}
