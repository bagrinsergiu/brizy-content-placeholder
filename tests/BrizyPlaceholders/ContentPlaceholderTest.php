<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\ContentPlaceholder;
use PHPUnit\Framework\TestCase;

class ContentPlaceholderTest extends TestCase
{
    public function test__construct()
    {
        $attributes  = ['attr' => 1];
        $placeholder = new ContentPlaceholder('name', 'placeholder', $attributes, 'content');

        $this->assertEquals('name', $placeholder->getName(), 'It should return the correct name');
        $this->assertEquals('placeholder', $placeholder->getPlaceholder(), 'It should return the correct placeholder');
        $this->assertEquals('content', $placeholder->getContent(), 'It should return the correct content');
        $this->assertSame($attributes, $placeholder->getAttributes(), 'It should return the correct attributes');
    }
}
