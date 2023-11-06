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
}
