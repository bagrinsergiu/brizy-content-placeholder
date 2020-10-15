<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\Extractor;
use BrizyPlaceholders\Registry;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{

    public function testExtractWithoutRegisteredPlaceholders()
    {
        $registry  = new Registry();
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return 1 placeholder');
        $this->assertCount(1, $instancePlaceholders, 'It should return 1 placeholder');
        $this->assertEquals($content, $returnedContent, 'It should return the same content');
    }

    public function testExtract()
    {
        $registry = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder(), 'Placeholder', 'placeholder', 'group1');
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return 1 placeholders');
        $this->assertCount(1, $instancePlaceholders, 'It should return 1 placeholders');
        $this->assertStringNotContainsString(
            "{{placeholder}}",
            $returnedContent,
            'It should return the content with the placeholder replaced'
        );

    }

    public function testStripPlaceholders()
    {
        $registry  = new Registry();
        $extractor = new Extractor($registry);

        $content         = "Some content with a {{placeholder}}.";
        $strippedContent = $extractor->stripPlaceholders($content);
        $this->assertStringNotContainsString('{{placeholder}}', $strippedContent, 'It should not contain any placeholders');


        $content         = "Some content.";
        $strippedContent = $extractor->stripPlaceholders($content);
        $this->assertEquals($content, $strippedContent, 'It should not modify the content');
    }
}
