<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\ContentPlaceholder;
use PHPUnit\Framework\TestCase;

class ContentPlaceholderTest extends TestCase
{
    public function test__construct()
    {
        $attributes = ['attr' => 1];
        $placeholder = new ContentPlaceholder('name', 'placeholder', $attributes, 'content');

        $this->assertEquals('name', $placeholder->getName(), 'It should return the correct name');
        $this->assertEquals('placeholder', $placeholder->getPlaceholder(), 'It should return the correct placeholder');
        $this->assertEquals('content', $placeholder->getContent(), 'It should return the correct content');
        $this->assertSame($attributes, $placeholder->getAttributes(), 'It should return the correct attributes');
    }

    public function dataProvider_Build()
    {
        return [
            [
                'placeholder' => new ContentPlaceholder('name', 'placeholder', ['attr' => 1]),
                'expected' => '{{name attr="1"}}',
            ],
            [
                'placeholder' => new ContentPlaceholder('name', 'placeholder', ['attr' => 1], 'content'),
                'expected' => '{{name attr="1"}}content{{end_name}}',
            ],
            [
                'placeholder' => new ContentPlaceholder('name', 'placeholder', ['attr' => '%7B%7B+brizy_dc_url_post++id%3D%22%2Fcollection_items%2F16780%22+%7D%7D']),
                'expected' => '{{name attr="%257B%257B%2Bbrizy_dc_url_post%2B%2Bid%253D%2522%252Fcollection_items%252F16780%2522%2B%257D%257D"}}',
            ],
            [
                'placeholder' => new ContentPlaceholder('name', 'placeholder', ['attr' => '{{ brizy_dc_url_post  id="/collection_items/16780" }}']),
                'expected' => '{{name attr="%7B%7B+brizy_dc_url_post++id%3D%22%2Fcollection_items%2F16780%22+%7D%7D"}}',
            ],
            [
                'placeholder' => new ContentPlaceholder('name', 'placeholder', ['attr' => 'aaa"aaa']),
                'expected' => '{{name attr="aaa%22aaa"}}',
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_Build
     * @return void
     */
    public function test_build($placeholder, $expected)
    {
        $built = $placeholder->buildPlaceholder();
        $this->assertEquals(
            $expected,
            $built,
            'It should be: '.$expected
        );
    }

    /**
     * Test constructor with null attributes.
     */
    public function testConstructorWithNullAttributes()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', null);

        $this->assertEquals('name', $placeholder->getName());
        $this->assertEquals('placeholder', $placeholder->getPlaceholder());
        $this->assertNull($placeholder->getAttributes());
        $this->assertNull($placeholder->getContent());
    }

    /**
     * Test constructor with empty attributes array.
     */
    public function testConstructorWithEmptyAttributes()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', []);

        $this->assertEquals([], $placeholder->getAttributes());
    }

    /**
     * Test that random_id placeholder name generates unique UIDs.
     */
    public function testConstructorWithRandomIdName()
    {
        $placeholder1 = new ContentPlaceholder('random_id', 'placeholder', []);
        $placeholder2 = new ContentPlaceholder('random_id', 'placeholder', []);

        // random_id placeholders should have different UIDs even with same inputs
        $this->assertNotEquals(
            $placeholder1->getUid(),
            $placeholder2->getUid(),
            'random_id placeholders should have unique UIDs'
        );
    }

    /**
     * Test getAttribute throws exception when required attribute is missing.
     */
    public function testGetAttributeNonExistentRequired()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['existing' => 'value']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The is not attribute 'missing' set.");

        $placeholder->getAttribute('missing', true);
    }

    /**
     * Test getAttribute returns null for non-existent attribute when not required.
     */
    public function testGetAttributeNonExistentNotRequired()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['existing' => 'value']);

        $result = $placeholder->getAttribute('missing', false);

        $this->assertNull($result, 'Should return null for non-existent attribute');
    }

    /**
     * Test getAttribute with null attributes array.
     */
    public function testGetAttributeWithNullAttributesArray()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', null);

        $result = $placeholder->getAttribute('any', false);

        $this->assertNull($result, 'Should return null when attributes array is null');
    }

    /**
     * Test buildPlaceholder with empty content (no end tag).
     */
    public function testBuildPlaceholderWithEmptyContent()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['attr' => '1'], '');

        $built = $placeholder->buildPlaceholder();

        // Empty string content should not produce end tag (trim('') is falsy)
        $this->assertEquals('{{name attr="1"}}', $built, 'Empty content should not produce end tag');
    }

    /**
     * Test buildPlaceholder with whitespace-only content.
     */
    public function testBuildPlaceholderWithWhitespaceContent()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['attr' => '1'], '   ');

        $built = $placeholder->buildPlaceholder();

        // Whitespace-only content when trimmed is empty, so no end tag
        $this->assertEquals('{{name attr="1"}}', $built, 'Whitespace-only content should not produce end tag');
    }

    /**
     * Test UID consistency - same inputs should produce same UID.
     */
    public function testUidConsistency()
    {
        $placeholder1 = new ContentPlaceholder('name', 'placeholder', ['attr' => 'value']);
        $placeholder2 = new ContentPlaceholder('name', 'placeholder', ['attr' => 'value']);

        $this->assertEquals(
            $placeholder1->getUid(),
            $placeholder2->getUid(),
            'Same inputs should produce same UID'
        );
    }

    /**
     * Test UID uniqueness - different inputs should produce different UID.
     */
    public function testUidUniqueness()
    {
        $placeholder1 = new ContentPlaceholder('name', 'placeholder1', ['attr' => 'value1']);
        $placeholder2 = new ContentPlaceholder('name', 'placeholder2', ['attr' => 'value2']);

        $this->assertNotEquals(
            $placeholder1->getUid(),
            $placeholder2->getUid(),
            'Different inputs should produce different UIDs'
        );
    }

    /**
     * Test getId method.
     */
    public function testGetId()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['id' => '123']);

        $this->assertEquals('123', $placeholder->getId(), 'Should return id attribute');
        $this->assertEquals('123', $placeholder->getId(true), 'Should return id attribute when required');
    }

    /**
     * Test getId throws when required and missing.
     */
    public function testGetIdRequiredThrows()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', []);

        $this->expectException(\Exception::class);
        $placeholder->getId(true);
    }

    /**
     * Test getEntityId delegates to getId.
     */
    public function testGetEntityId()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['id' => '456']);

        $this->assertEquals('456', $placeholder->getEntityId());
    }

    /**
     * Test getEntityType returns typeId attribute.
     */
    public function testGetEntityType()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', ['typeId' => 'post']);

        $this->assertEquals('post', $placeholder->getEntityType());
    }

    /**
     * Test setters return $this for fluent interface.
     */
    public function testSettersReturnThis()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', []);

        $this->assertSame($placeholder, $placeholder->setUid('new_uid'));
        $this->assertSame($placeholder, $placeholder->setName('new_name'));
        $this->assertSame($placeholder, $placeholder->setPlaceholder('new_placeholder'));
        $this->assertSame($placeholder, $placeholder->setAttributes(['new' => 'attr']));
        $this->assertSame($placeholder, $placeholder->setContent('new_content'));
    }

    /**
     * Test buildPlaceholder with no attributes.
     */
    public function testBuildPlaceholderNoAttributes()
    {
        $placeholder = new ContentPlaceholder('name', 'placeholder', []);

        $built = $placeholder->buildPlaceholder();

        $this->assertEquals('{{name}}', $built, 'Should build placeholder without attributes');
    }
}
