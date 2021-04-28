<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\PlaceholderInterface;
use BrizyPlaceholders\Registry;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{

    public function test__construct()
    {
        $registry = new Registry();
        $this->assertIsArray($registry->getPlaceholders(), 'It should return an array');
    }

    public function testRegisterPlaceholder()
    {
        $registry    = new Registry();
        $placeholder1 = $this->createMock(PlaceholderInterface::class);
        $placeholder1->expects($this->any())
                    ->method('support')
                    ->with('placeholder1')
                    ->willReturn(true);

        $placeholder2 = $this->createMock(PlaceholderInterface::class);
        $placeholder2->expects($this->any())
                    ->method('support')
                    ->with('placeholder2')
                    ->willReturn(true);

        $registry->registerPlaceholder($placeholder1);
        $registry->registerPlaceholder($placeholder2);

        $all = $registry->getPlaceholders();

        $this->assertCount(2, $all, 'It should return 4 placeholders');
    }


    public function testGetPlaceholderSupportingName()
    {
        $registry     = new Registry();
        $placeholder1 = $this->createMock(PlaceholderInterface::class);
        $placeholder1->expects($this->any())
            ->method('support')
            ->with('placeholder1')
            ->willReturn(true);

        $placeholder2 = $this->createMock(PlaceholderInterface::class);
        $placeholder2->expects($this->any())
            ->method('support')
            ->with('placeholder1')
            ->willReturn(false);

        $registry->registerPlaceholder($placeholder2);
        $registry->registerPlaceholder($placeholder1);

        $aplaceholder = $registry->getPlaceholderSupportingName('placeholder1');

        $this->assertInstanceOf(PlaceholderInterface::class, $aplaceholder, 'It should return an array');
        $this->assertEquals(
            $placeholder1,
            $aplaceholder,
            'It should return the correct placeholder instance'
        );
    }
}
