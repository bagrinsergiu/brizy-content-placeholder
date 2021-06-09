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
            ['<source srcset="{{placeholder cW=&#x27;555&#x27; cH=&#x27;548&#x27;}} 1x, {{placeholder cW=&#x27;1110&#x27; cH=&#x27;1096&#x27;}} 2x" media="(min-width: 992px)">', 2],
            ['<source srcset="{{placeholder cW="555" cH=&#x27;548&#x27;}} 1x, {{placeholder cW=&#x27;1110&#x27; cH=&#x27;1096&#x27;}} 2x" media="(min-width: 992px)">', 2],
            ['{{placeholder type=&#x27;posts&#x27; collection_type=&#x27;/collection_types/5557&#x27; count=&#x27;3&#x27; order_by=&#x27;id&#x27; order=&#x27;DESC&#x27; offset=&#x27;0&#x27;}}
<div class="brz-posts__item">
    <source srcset="{{brizy_dc_img_featured_image cW=&#x27;350&#x27; cH=&#x27;263&#x27;}} 1x,
            {{brizy_dc_img_featured_image cW=&#x27;700&#x27; cH=&#x27;526&#x27;}} 2x\
    " media="(min-width: 992px)">
    <source srcset="{{brizy_dc_img_featured_image cW=&#x27;339&#x27; cH=&#x27;254&#x27;}} 1x,
            {{brizy_dc_img_featured_image cW=&#x27;678&#x27; cH=&#x27;508&#x27;}} 2x\
    " media="(min-width: 768px)"><img class="brz-img brz-p-absolute" srcset="{{brizy_dc_img_featured_image cW=&#x27;400&#x27;
    cH=&#x27;300&#x27;}} 1x, {{brizy_dc_img_featured_image cW=&#x27;800&#x27; cH=&#x27;600&#x27;}} 2x"
    src="{{brizy_dc_img_featured_image cW=&#x27;350&#x27; cH=&#x27;263&#x27;}}" alt draggable="false"
    loading="lazy"></picture></div></div>
<div class="brz-css-ivzoh brz-wrapper">
<div class="brz-wp-title brz-css-zsobs brz-css-kktiy" data-custom-id="sjemssizalhhfifgnfarkeqzptostlczoitd"><span
        class="brz-wp-title-content" style="min-height:20px">{{brizy_dc_post_title}}</span></div></div>
<div class="brz-wrapper-clone brz-css-lzbzn">
<div class="brz-d-xs-flex brz-flex-xs-wrap brz-css-vbdws">
<div class="brz-wrapper-clone__item brz-css-coens" id="mynbaxearjrovxlzahhuxrszqjfbzqnlsneh"><a class="brz-a
                                                                                                    brz-btn
                                                                                                    brz-css-blxcf" href="{{brizy_dc_url_post}}" data-brz-link-type="external" data-custom-id="mynbaxearjrovxlzahhuxrszqjfbzqnlsneh">
<svg id="nc_icon" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
     x="0px" y="0px" viewbox="0 0 24
     24" xml:space="preserve" class="brz-icon-svg brz-css-ajhvz" data-type="glyph" data-name="tail-right">
<g class="nc-icon-wrapper" fill="currentColor">
    <path fill="currentColor"
          d="M22.707,11.293L15,3.586L13.586,5l6,6H2c-0.553,0-1,0.448-1,1s0.447,1,1,1h17.586l-6,6L15,20.414
          l7.707-7.707C23.098,12.316,23.098,11.684,22.707,11.293z\
    "/>
</g></svg><span class="brz-span
                brz-text__editor">READ MORE</span></a></div></div></div></div></div></div>{{end_placeholder}}', 1],
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

        $this->assertCount($count, $contentPlaceholders, 'It should return '.$count.' placeholders');
        $this->assertCount($count, $instancePlaceholders, 'It should return '.$count.' placeholders');

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
