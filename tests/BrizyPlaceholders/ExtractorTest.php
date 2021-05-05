<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\Extractor;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\LoopPlaceholder;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{

    public function testExtractWithoutRegisteredPlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(0, $contentPlaceholders, 'It should return 1 placeholder');
        $this->assertCount(0, $instancePlaceholders, 'It should return 1 placeholder');
        $this->assertEquals($content, $returnedContent, 'It should return the same content');
    }

    public function testExtract()
    {
        $registry = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder());
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

    public function testExtractPlaceholdersWithContent()
    {

        $registry = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder());
        $replacer = new Replacer($registry);
        $registry->registerPlaceholder(new LoopPlaceholder($replacer));
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder_loop}}{{placeholder}}{{end_placeholder_loop}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return 1 placeholders');
        $this->assertCount(1, $instancePlaceholders, 'It should return 1 placeholders');
        $this->assertStringNotContainsString(
            "{{placeholder}}",
            $returnedContent,
            'It should return the content with the placeholder replaced'
        );

    }

    public function placeholdersWithAttributesProvider()
    {
        return [
            ["Some content with a {{placeholder attr='1'}}.", 1],
            ["Some content with a {{placeholder attr=\"1\"}}.", 1],
            ["Some content {{placeholder attr='1'}}  with a {{placeholder attr='1'}}.", 2],
            ["Some content {{placeholder attr=\"1\"}}  with a {{placeholder attr=\"1\"}}.", 2],
            ["<img src=\"{{placeholder attr='1'}} 1x {{placeholder attr='1'}} 2x\"/>", 2],
            ['<source srcset="{{placeholder cW=&apos;555&apos; cH=&apos;548&apos;}} 1x, {{placeholder cW=&apos;1110&apos; cH=&apos;1096&apos;}} 2x" media="(min-width: 992px)">', 2],
        ];
    }

    /**
     * @dataProvider placeholdersWithAttributesProvider
     */
    public function testExtractPlaceholdersWithAttributes($content, $count)
    {

        $registry = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder());
        $extractor = new Extractor($registry);

        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount($count, $contentPlaceholders, 'It should return 1 placeholders');
        $this->assertCount($count, $instancePlaceholders, 'It should return 1 placeholders');

    }

    public function testExtractWithRepeatingPlaceholders()
    {
        $registry = new Registry();
        $registry->registerPlaceholder(new TestPlaceholder());
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder}} {{placeholder}} {{placeholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(3, $contentPlaceholders, 'It should return 3 placeholders');
        $this->assertCount(3, $instancePlaceholders, 'It should return 3 placeholders');
        $this->assertStringNotContainsString(
            "{{placeholder}}",
            $returnedContent,
            'It should return the content with the placeholder replaced'
        );
    }

    public function testStripPlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder}}.";
        $strippedContent = $extractor->stripPlaceholders($content);
        $this->assertStringNotContainsString('{{placeholder}}', $strippedContent, 'It should not contain any placeholders');


        $content = "Some content.";
        $strippedContent = $extractor->stripPlaceholders($content);
        $this->assertEquals($content, $strippedContent, 'It should not modify the content');
    }
}
