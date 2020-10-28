<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\EmptyContext;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\LoopPlaceholder;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;

class ReplacerTest extends TestCase
{

    public function testReplaceWithoutPlaceholders()
    {
        $registry  = new Registry();
        $replacer = new Replacer($registry);

        $content             = "Some content";
        $context             = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }


    public function testReplaceWithoutRegisteredPlaceholders()
    {
        $registry  = new Registry();
        $replacer = new Replacer($registry);

        $content             = "Some content with a {{placeholder}}.";
        $context             = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content with a {{placeholder}}.",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }

    public function testReplaceWithRegisteredPlaceholders()
    {
        $registry  = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder(),'Placeholder','placeholder','group1');
        $replacer = new Replacer($registry);

        $content             = "Some content with {{placeholder}} and {{placeholder_234}}.";
        $context             = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content with placeholder_value and placeholder_value.",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }

    public function testReplaceWithLoopPlaceholder()
    {
        $registry  = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder(),'Placeholder','placeholder','group1');
        $replacer = new Replacer($registry);
        $registry->registerPlaceholder(new LoopPlaceholder($replacer),'Placeholder','placeholder_loop','group1');

        $content             = "{{placeholder_loop}}{{placeholder}}{{end_placeholder_loop}}";
        $context             = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "placeholder_valueplaceholder_valueplaceholder_valueplaceholder_valueplaceholder_value",
            $contentAfterReplace,
            'It should return the content with replaced placeholders'
        );
    }


    public function testReplaceWithRepeatingPlaceholders()
    {
        $registry  = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder(),'Placeholder','placeholder','group1');
        $replacer = new Replacer($registry);

        $content             = "Some content {{placeholder}} and {{placeholder}}.";
        $context             = new EmptyContext();
        $contentAfterReplace = $replacer->replacePlaceholders($content, $context);

        $this->assertEquals(
            "Some content placeholder_value and placeholder_value.",
            $contentAfterReplace,
            'It should return the content with repeated placeholder content'
        );
    }
}
