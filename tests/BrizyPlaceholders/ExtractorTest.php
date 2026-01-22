<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\ContentPlaceholder;
use BrizyPlaceholders\Extractor;
use BrizyPlaceholders\ExtractorV2;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\LoopPlaceholder;
use BrizyPlaceholdersTests\Sample\Placeholder;
use BrizyPlaceholdersTests\Sample\PlaceholderWrapper;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ExtractorTest extends TestCase
{
    use ProphecyTrait;

    public function extractedPlaceholderContentObjectsProvider()
    {
        return [
            ['', 0, [], []],
            ['{{ aplaceholder}}', 1, ['aplaceholder'], []],
            ['{{place_holder}}', 1, ['place_holder'], []],
            ['{{place_holder attr="1"}}', 1, ['place_holder'], [['attr' => '1']]],
            ['{{place_holder attr="1" attr2="2"}}', 1, ['place_holder'], [['attr' => '1', 'attr2' => '2']]],
            ['{{place_holder   attr="1"   attr2="2"   }}', 1, ['place_holder'], [['attr' => '1', 'attr2' => '2']]],
            ['{{  place_holder   attr="1"   attr2="2"}}', 1, ['place_holder'], [['attr' => '1', 'attr2' => '2']]],
            ['{{ placeholder-part}}', 1, ['placeholder-part'], []],
            ['{{ placeholder_test-test}}', 1, ['placeholder_test-test'], []],
            ['{{ aplaceholder attr="val1\"val2"}}', 1, ['aplaceholder'], []],
            // Edge case: Empty attribute value
            ['{{place_holder attr=""}}', 1, ['place_holder'], [['attr' => '']]],
            // Edge case: Whitespace attribute value
            ['{{place_holder attr="   "}}', 1, ['place_holder'], [['attr' => '   ']]],
            // Edge case: Placeholder at exact boundaries (no surrounding text)
            ['{{place_holder}}', 1, ['place_holder'], []],
            [
                "{{ aplaceholder content='e3tla2tfZXZlbnRfY2FsZW5kYXJ9fQ==' category='all' group='all'   howmanymonths='8' detail_page='%7B%7B%20brizy_dc_url_post%20%20id=%22/collection_items/16780%22%20%7D%7D' time='0'}}",
                1,
                ['aplaceholder'],
                [
                    [
                        'content' => 'e3tla2tfZXZlbnRfY2FsZW5kYXJ9fQ==',
                        'category' => 'all',
                        'group' => 'all',
                        'howmanymonths' => '8',
                        'detail_page' => urldecode(
                            '%7B%7B%20brizy_dc_url_post%20%20id=%22/collection_items/16780%22%20%7D%7D'
                        ),
                        'time' => '0',
                    ],
                ],
            ],
            [
                "{{ aplaceholder
                            content='e3tla2tfc2VybW9uX2xpc3R9fQ==' 
                            howmany='3' 
                            group='all' 
                            category='all' 
                            series='all'
                            show_title='1' 
                            show_pagination='1' 
                            show_group='0' 
                            show_preacher='0' 
                            show_passage='0'
                            show_preview='0' 
                            show_inline_video='0' 
                            show_inline_audio='0' 
                            show_date='1' 
                            show_images='1'
                            show_series='0' 
                            show_category='0' 
                            show_media_links='1' 
                            show_meta_headings='1'
                            detail_url='%7B%7Bplaceholder%20content='e3sgYnJpenlfZGNfdXJsX3Bvc3QgZW50aXR5SWQ9Ii9jb2xsZWN0aW9uX2l0ZW1zLzE2ODAyIiB9fQ == '%7D%7D'
                            detail_page_button_text='Button' 
                            sticky_space='0' }}",
                1,
                ['aplaceholder'],
                [
                    [
                        'content' => 'e3tla2tfc2VybW9uX2xpc3R9fQ==',
                        'howmany' => '3',
                        'group' => 'all',
                        'category' => 'all',
                        'series' => 'all',
                        'show_title' => '1',
                        'show_pagination' => '1',
                        'show_group' => '0',
                        'show_preacher' => '0',
                        'show_passage' => '0',
                        'show_preview' => '0',
                        'show_inline_video' => '0',
                        'show_inline_audio' => '0',
                        'show_date' => '1',
                        'show_images' => '1',
                        'show_series' => '0',
                        'show_category' => '0',
                        'show_media_links' => '1',
                        'show_meta_headings' => '1',
                        'detail_url' => urldecode(
                            '%7B%7Bplaceholder%20content=\'e3sgYnJpenlfZGNfdXJsX3Bvc3QgZW50aXR5SWQ9Ii9jb2xsZWN0aW9uX2l0ZW1zLzE2ODAyIiB9fQ == \'%7D%7D'
                        ),
                        'detail_page_button_text' => 'Button',
                        'sticky_space' => '0',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider extractedPlaceholderContentObjectsProvider
     *
     * @param $content
     * @param $expectedCount
     * @param $expectedPlaceholderNames
     * @param $expectedPlaceholderAttributes
     */
    public function testExtractedPlaceholderContentObjects(
        $content,
        $expectedCount,
        $expectedPlaceholderNames,
        $expectedPlaceholderAttributes
    )
    {

        $registry = new Registry();

        foreach ($expectedPlaceholderNames as $i => $expectedPlaceholderName) {
            $placeholderProphecy = $this->prophesize('BrizyPlaceholdersTests\Sample\TestPlaceholder');
            $placeholderProphecy->support(Argument::exact($expectedPlaceholderName))->willReturn(true);
            $placeholderProphecy->getValue(Argument::any())->willReturn($expectedPlaceholderName);
            $placeholderProphecy->getUid()->willReturn('1111' . $i);
            $placeholderProphecy->getPlaceholder()->willReturn("{{{$expectedPlaceholderName}}}");
            $registry->registerPlaceholderName($expectedPlaceholderName, function () use ($placeholderProphecy) {
                return $placeholderProphecy->reveal();
            });
        }

        $extractor = new Extractor($registry);

        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount($expectedCount, $contentPlaceholders, 'It should extract ' . $expectedCount . ' placeholders');

        foreach ($contentPlaceholders as $i => $contentPlaceholder) {
            $this->assertStringNotContainsString(
                "{{{$contentPlaceholder->getName()}}}}",
                $returnedContent,
                'It should return the content with the placeholder replaced'
            );

            $this->assertTrue(
                in_array($contentPlaceholder->getName(), $expectedPlaceholderNames),
                'The expected name has not been extracted'
            );

            $attrKeys = array_keys($contentPlaceholder->getAttributes());
            $attrValues = array_values($contentPlaceholder->getAttributes());

            if (count($expectedPlaceholderAttributes) == 0) {
                continue;
            }

            $expectedAttrKeys = array_keys($expectedPlaceholderAttributes[$i]);
            $expectedAttrValues = array_values($expectedPlaceholderAttributes[$i]);

            $haystack = array_diff($expectedAttrValues, $attrValues);
            $this->assertCount(
                0,
                $haystack,
                'The content placeholder should have the expected attribute values'
            );
            $haystack1 = array_diff($attrKeys, $expectedAttrKeys);
            $this->assertCount(
                0,
                $haystack1,
                'The content placeholder should have the expected attribute name'
            );
        }
    }

    public function testExtractWithoutRegisteredPlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "Some content with a {{ aplaceholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(0, $contentPlaceholders, 'It should return 1 placeholder');
        $this->assertCount(0, $instancePlaceholders, 'It should return 1 placeholder');
        $this->assertEquals($content, $returnedContent, 'It should return the same content');
    }


    public function testExtract()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            return new TestPlaceholder('aplaceholder');
        });
        $extractor = new Extractor($registry);

        $content = "Some content with a {{ aplaceholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return 1 placeholders');
        $this->assertCount(1, $instancePlaceholders, 'It should return 1 placeholders');
        $this->assertStringNotContainsString(
            "{{ aplaceholder}}",
            $returnedContent,
            'It should return the content with the placeholder replaced'
        );
    }

    public function testExtractPlaceholdersWithContent()
    {

        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            return new TestPlaceholder('aplaceholder');
        });
        $replacer = new Replacer($registry);
        $registry->registerPlaceholderName('placeholder_loop', function () use ($replacer) {
            return new LoopPlaceholder($replacer);
        });
        $extractor = new Extractor($registry);

        $content = "Some content with a {{placeholder_loop}}{{aplaceholder}}{{end_placeholder_loop}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return 1 placeholders');
        $this->assertCount(1, $instancePlaceholders, 'It should return 1 placeholders');
        $this->assertStringNotContainsString(
            "{{aplaceholder}}",
            $returnedContent,
            'It should return the content with the placeholder replaced'
        );

    }

    public function placeholdersWithAttributesProvider()
    {
        return [
            ["Some content with a {{aplaceholder attr='1'}}.", 1],
            ["Some content with a {{aplaceholder-name attr='1'}}.", 1],
            ["Some content with a {{aplaceholder_with-name attr='1'}}.", 1],
            ["Some content with a {{aplaceholder_with-name}}.", 1],
            ["Some content with a {{aplaceholder attr=\"1\"}}.", 1],
            ["Some content {{aplaceholder attr='1'}}  with a {{aplaceholder attr='1'}}.", 1],
            ["Some content {{aplaceholder attr=\"1\"}}  with a {{aplaceholder attr=\"1\"}}.", 1],
            ["<img src=\"{{aplaceholder attr='1'}} 1x {{aplaceholder attr='1'}} 2x\"/>", 1],
            [
                '<source srcset="{{aplaceholder cW=&apos;555&apos; cH=&apos;548&apos;}} 1x, {{aplaceholder cW=&apos;1110&apos; cH=&apos;1096&apos;}} 2x" media="(min-width: 992px)">',
                2,
            ],
            [
                '<source srcset="{{aplaceholder cW=&#x27;555&#x27; cH=&#x27;548&#x27;}} 1x, {{aplaceholder cW=&#x27;1110&#x27; cH=&#x27;1096&#x27;}} 2x" media="(min-width: 992px)">',
                2,
            ],
            [
                '<source srcset="{{aplaceholder cW="555" cH=&#x27;548&#x27;}} 1x, {{aplaceholder cW=&#x27;1110&#x27; cH=&#x27;1096&#x27;}} 2x" media="(min-width: 992px)">',
                2,
            ],
            [
                '{{aplaceholder type=&#x27;posts&#x27; collection_type=&#x27;/collection_types/5557&#x27; count=&#x27;3&#x27; order_by=&#x27;id&#x27; order=&#x27;DESC&#x27; offset=&#x27;0&#x27;}}
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
                brz-text__editor">READ MORE</span></a></div></div></div></div></div></div>{{end_aplaceholder}}',
                1,
            ],
        ];
    }

    /**
     * @dataProvider placeholdersWithAttributesProvider
     */
    public function testExtractPlaceholdersWithAttributes($content, $count)
    {
        $registry = new Registry();
        $factory = function () {
            return new TestPlaceholder('aplaceholder');
        };
        $registry->registerPlaceholderName('aplaceholder', $factory);
        $registry->registerPlaceholderName('aplaceholder-name', $factory);
        $registry->registerPlaceholderName('aplaceholder_with-name', $factory);
        $extractor = new Extractor($registry);

        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount($count, $contentPlaceholders, 'It should return ' . $count . ' placeholders');
        $this->assertCount($count, $instancePlaceholders, 'It should return ' . $count . ' placeholders');

    }

    public function testExtractWithRepeatingPlaceholders()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            return new TestPlaceholder('aplaceholder');
        });
        $replacer = new Replacer($registry);
        $registry->registerPlaceholderName('placeholder_loop', function () use ($replacer) {
            return new LoopPlaceholder($replacer);
        });
        $extractor = new Extractor($registry);

        $content = "Some content with a {{aplaceholder}} {{aplaceholder}} {{aplaceholder}}.";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return 1 placeholders');
        $this->assertCount(1, $instancePlaceholders, 'It should return 1 placeholders');
        $this->assertStringNotContainsString(
            "{{aplaceholder}}",
            $returnedContent,
            'It should return the content with the placeholder replaced'
        );
    }

    public function testStripPlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "Some content with a {{aplaceholder}}.";
        $strippedContent = $extractor->stripPlaceholders($content);
        $this->assertStringNotContainsString(
            '{{aplaceholder}}',
            $strippedContent,
            'It should not contain any placeholders'
        );


        $content = "Some content.";
        $strippedContent = $extractor->stripPlaceholders($content);
        $this->assertEquals($content, $strippedContent, 'It should not modify the content');
    }

    public function testRecursivePlaceholders()
    {

        $registry = new Registry();
        $registry->registerPlaceholderName('aplaceholder', function () {
            return new TestPlaceholder('aplaceholder');
        });
        $extractor = new Extractor($registry);
        $content = "{{ aplaceholder }}content1 {{placeholder_loop}} {{ aplaceholder }}content inner{{ end_placeholder }} {{end_placeholder_loop}} content2";

        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return one placeholder');
        $this->assertCount(1, $instancePlaceholders, 'It should return one instance placeholder');

        $t = 0;
    }

    public function testExtractiIgnoringRegistry()
    {

        $registry = new Registry();
        $extractor = new Extractor($registry);
        $content = "{{ placeholder1 }}content1  {{ middle_placeholder }}content inner{{ end_placeholder1 }}  
                        {{placeholder_loop}} xxxxx {{end_placeholder_loop}} content2";

        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(2, $contentPlaceholders, 'It should return one placeholder');

        $t = 0;
    }

    public function testExtractIgnoringRegistryWIthCallback()
    {

        $registry = new Registry();
        $extractor = new Extractor($registry);
        $content = "content {{ placeholder1 }}content1 {{ middle_placeholder }}content inner{{ end_placeholder1 }} {{placeholder_loop}} xxxxx {{end_placeholder_loop}} content2";

        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry(
            $content,
            function (ContentPlaceholder $p) {
                return $p->getName();
            }
        );

        $this->assertCount(2, $contentPlaceholders, 'It should return two placeholder');
        $this->assertEquals(
            "content placeholder1 placeholder_loop content2",
            $returnedContent,
            'It should return correct content'
        );
    }

    public function testExtractRecursivePlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);
        $content = 'start{{placeholder_a}}content1{{placeholder_a}}1{{placeholder_a attr="123"}}test{{end_placeholder_a}}2{{end_placeholder_a}}content2{{end_placeholder_a}}endstart{{placeholder_a}}content1{{placeholder_a}}1{{placeholder_a attr="123"}}test{{end_placeholder_a}}2{{end_placeholder_a}}content2{{end_placeholder_a}}end';

        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(1, $contentPlaceholders, 'It should return two placeholder');
        foreach ($contentPlaceholders as $placeholder) {
            $this->assertEquals(
                'content1{{placeholder_a}}1{{placeholder_a attr="123"}}test{{end_placeholder_a}}2{{end_placeholder_a}}content2',
                $placeholder->getContent(),
                'It should return the containing content of the first placeholder'
            );
        }
    }

    public function testExtractRecursivePlaceholders2()
    {
        $content = 'start{{placeholder1  attr1="123"  attr2="456"}}
                            content1
                            {{placeholder1}}
                         {{end_placeholder1}}end';
        $registry = new Registry();
        $factory = function () {
            return new TestPlaceholder('aplaceholder');
        };
        $registry->registerPlaceholderName('aplaceholder', $factory);
        $registry->registerPlaceholderName('placeholder1', $factory);
        $extractor = new Extractor($registry);
        list($contentPlaceholders, $placeholderInstances, $content) = $extractor->extract($content);

        $this->assertCount(2, $contentPlaceholders, 'It should return two placeholder');
        $this->assertStringNotContainsString(
            "placeholder",
            $content,
            'It should return the content with the placeholder replaced'
        );
        $this->assertStringNotContainsString(
            "end_placeholder",
            $content,
            'It should return the content with the end_placeholder replaced'
        );
    }

    public function testExtractRecursivePlaceholders3()
    {
        $content = 'start{{placeholder_a  attr1="123"  attr2="456"}}content1{{placeholder_a}}1{{placeholder_a attr="123"}}test{{end_placeholder_a}}2{{end_placeholder_a}}content2{{end_placeholder_a}}end';
        $registry = new Registry();
        $registry->registerPlaceholderName('placeholder_a', function () {
            ;
            return new TestPlaceholder('placeholder');
        });
        $extractor = new Extractor($registry);
        list($contentPlaceholders, $placeholderInstances, $content) = $extractor->extract($content);
        $this->assertCount(1, $contentPlaceholders, 'It should return two placeholder');
        $this->assertStringNotContainsString(
            "placeholder",
            $content,
            'It should return the content with the placeholder replaced'
        );
        $this->assertStringNotContainsString(
            "end_placeholder",
            $content,
            'It should return the content with the end_placeholder replaced'
        );
    }


    public function testExtractFromHtml()
    {
        $content = file_get_contents('/opt/project/tests/data/user_case7.html');
        $registry = new Registry();
        $registry->registerPlaceholderName('placeholder', function () {
            ;
            return new TestPlaceholder('placeholder');
        });
        $extractor = new Extractor($registry);
        list($contentPlaceholders, $content) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(14, $contentPlaceholders, 'It should return two placeholder');
    }

    public function testExtractFromBigHtml()
    {
        $content = file_get_contents('/opt/project/tests/data/user_case10.html');
        $registry = new Registry();
        $extractor = new Extractor($registry);
        list($contentPlaceholders, $content) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(22, $contentPlaceholders, 'It should return 22 placeholder');
    }

    public function testExtractFromBigHtml2()
    {
        $content = file_get_contents('/opt/project/tests/data/user_case11.html');
        $registry = new Registry();
        $extractor = new Extractor($registry);
        list($contentPlaceholders, $content) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(147, $contentPlaceholders, 'It should return 144 placeholder');
    }

    public function testExtractFromBigHtml3()
    {
        $content = file_get_contents('/opt/project/tests/data/user_case12.html');
        $registry = new Registry();
        $extractor = new Extractor($registry);
        list($contentPlaceholders, $content) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(13, $contentPlaceholders, 'It should return 13 placeholder');
    }

    /**
     * Test that adjacent placeholders without space between them are extracted correctly.
     */
    public function testExtractAdjacentPlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "{{placeholder_a}}{{placeholder_b}}";
        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(2, $contentPlaceholders, 'It should extract 2 adjacent placeholders');
        $this->assertEquals('placeholder_a', $contentPlaceholders[0]->getName());
        $this->assertEquals('placeholder_b', $contentPlaceholders[1]->getName());
    }

    /**
     * Test placeholders at exact content boundaries (start and end).
     */
    public function testExtractPlaceholderAtBoundaries()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('start', function () {
            return new TestPlaceholder('start');
        });
        $registry->registerPlaceholderName('end', function () {
            return new TestPlaceholder('end');
        });
        $extractor = new Extractor($registry);

        // Placeholder at start
        $content = "{{start}} some text";
        list($contentPlaceholders, , $returnedContent) = $extractor->extract($content);
        $this->assertCount(1, $contentPlaceholders, 'It should extract placeholder at start');

        // Placeholder at end
        $content = "some text {{end}}";
        list($contentPlaceholders, , $returnedContent) = $extractor->extract($content);
        $this->assertCount(1, $contentPlaceholders, 'It should extract placeholder at end');

        // Only placeholder (no surrounding text)
        $content = "{{start}}";
        list($contentPlaceholders, , $returnedContent) = $extractor->extract($content);
        $this->assertCount(1, $contentPlaceholders, 'It should extract lone placeholder');
    }

    /**
     * Test extraction with newlines in attribute values.
     * Note: The lexer doesn't support newlines within attribute values.
     */
    public function testExtractWithNewlinesInAttributes()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        // URL-encoded newlines work
        $content = "{{my_test_item attr=\"value%0Awith%0Anewlines\"}}";
        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(1, $contentPlaceholders, 'It should extract placeholder with URL-encoded newlines in attribute');
        // Verify the attribute value is decoded
        $attrs = $contentPlaceholders[0]->getAttributes();
        $this->assertEquals("value\nwith\nnewlines", $attrs['attr'], 'URL-encoded newlines should be decoded');
    }

    /**
     * Test extraction with spaces (whitespace) around placeholder name.
     */
    public function testExtractWithWhitespaceAroundName()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        // Spaces around name are supported
        $content = "{{  my_test_item  }}";
        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(1, $contentPlaceholders, 'It should extract placeholder with spaces around name');
        $this->assertEquals('my_test_item', $contentPlaceholders[0]->getName());
    }

    /**
     * Test stripPlaceholders with loop content.
     */
    public function testStripPlaceholdersWithLoopContent()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "Some {{loop_placeholder}}inner content{{end_loop_placeholder}} text";
        $strippedContent = $extractor->stripPlaceholders($content);

        $this->assertStringNotContainsString('{{loop_placeholder}}', $strippedContent, 'Loop placeholder should be stripped');
        $this->assertStringNotContainsString('{{end_loop_placeholder}}', $strippedContent, 'End tag should be stripped');
    }

    /**
     * Test extraction when end tag has wrong name (mismatch).
     */
    public function testExtractWrongEndTagName()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        // This should handle the case where end tag doesn't match open tag
        $content = "{{a}}content{{end_b}}";
        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        // The extractor should still parse what it can
        $this->assertIsArray($contentPlaceholders);
    }

    /**
     * Test extractIgnoringRegistry with multiple callbacks.
     */
    public function testExtractIgnoringRegistryMultiplePlaceholders()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $content = "{{first}} middle {{second}} end";
        $replacedNames = [];

        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry(
            $content,
            function (ContentPlaceholder $p) use (&$replacedNames) {
                $replacedNames[] = $p->getName();
                return 'REPLACED_' . $p->getName();
            }
        );

        $this->assertCount(2, $contentPlaceholders, 'Should extract 2 placeholders');
        $this->assertContains('first', $replacedNames, 'Should have extracted first placeholder');
        $this->assertContains('second', $replacedNames, 'Should have extracted second placeholder');
        $this->assertEquals('REPLACED_first middle REPLACED_second end', $returnedContent, 'Content should have replacements');
    }

    /**
     * Test that placeholder with numeric-only name works.
     */
    public function testExtractPlaceholderWithNumericName()
    {
        $registry = new Registry();
        $registry->registerPlaceholderName('123', function () {
            return new TestPlaceholder('123');
        });
        $extractor = new Extractor($registry);

        $content = "{{123}}";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'Should extract numeric placeholder name');
    }

    /**
     * Test extraction with very long placeholder name.
     */
    public function testExtractVeryLongPlaceholderName()
    {
        $registry = new Registry();
        $longName = str_repeat('a', 200);
        $registry->registerPlaceholderName($longName, function () use ($longName) {
            return new TestPlaceholder($longName);
        });
        $extractor = new Extractor($registry);

        $content = "{{{$longName}}}";
        list($contentPlaceholders, $instancePlaceholders, $returnedContent) = $extractor->extract($content);

        $this->assertCount(1, $contentPlaceholders, 'Should extract long placeholder name');
        $this->assertEquals($longName, $contentPlaceholders[0]->getName(), 'Name should match');
    }

    /**
     * Test extraction with many attributes.
     */
    public function testExtractManyAttributes()
    {
        $registry = new Registry();
        $extractor = new Extractor($registry);

        $attrs = [];
        for ($i = 0; $i < 20; $i++) {
            $attrs[] = "attr{$i}=\"value{$i}\"";
        }
        $content = "{{my_test_item " . implode(' ', $attrs) . "}}";

        list($contentPlaceholders, $returnedContent) = $extractor->extractIgnoringRegistry($content);

        $this->assertCount(1, $contentPlaceholders, 'Should extract placeholder with many attributes');
        $this->assertCount(20, $contentPlaceholders[0]->getAttributes(), 'Should have 20 attributes');
    }


}