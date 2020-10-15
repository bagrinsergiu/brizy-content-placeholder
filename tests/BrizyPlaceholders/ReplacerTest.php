<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\EmptyContext;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;

class ReplacerTest extends TestCase
{
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
}
