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
        $this->assertIsArray($registry->getGroupedPlaceholders(), 'It should return an array');
        $this->assertEmpty($registry->getGroupedPlaceholders(), 'It should return an empty array');
    }

    public function testRegisterPlaceholder()
    {
        $registry    = new Registry();
        $placeholder = $this->createMock(PlaceholderInterface::class);
        $placeholder->expects($this->any())
                    ->method('support')
                    ->with('placeholder1')
                    ->willReturn(true);

        $registry->registerPlaceholder($placeholder, 'Label1', 'placeholder1', 'group1');
        $registry->registerPlaceholder($placeholder, 'Label2', 'placeholder2', 'group1');
        $registry->registerPlaceholder($placeholder, 'Label3', 'placeholder4', 'group2');
        $registry->registerPlaceholder($placeholder, 'Label4', 'placeholder5', 'group2');

        $all = $registry->getAllPlaceholders();

        $this->assertCount(4, $all, 'It should return 4 placeholders');

        $grouped = $registry->getGroupedPlaceholders();

        $this->assertCount(2, $grouped, 'It should contain only two grpups');
        $this->assertArrayHasKey('group1', $grouped, 'It should contain group1');
        $this->assertArrayHasKey('group2', $grouped, 'It should contain group2');
    }


    public function testGetPlaceholderSupportingName()
    {
        $registry     = new Registry();
        $placeholder1 = $this->createMock(PlaceholderInterface::class);
        $placeholder1->expects($this->any())
                     ->method('support')
                     ->willReturn(true);
        $placeholder2 = $this->createMock(PlaceholderInterface::class);
        $placeholder2->expects($this->any())
                     ->method('support')
                     ->willReturn(false);

        $registry->registerPlaceholder($placeholder2, 'Label1', 'placeholder1', 'group1');
        $registry->registerPlaceholder($placeholder1, 'Label2', 'placeholder2', 'group2');
        $registry->registerPlaceholder($placeholder2, 'Label3', 'placeholder3', 'group1');

        $aplaceholder = $registry->getPlaceholderSupportingName('placeholder1');

        $this->assertInstanceOf(PlaceholderInterface::class, $aplaceholder, 'It should return an array');
        $this->assertEquals(
            $placeholder1,
            $aplaceholder,
            'It should return the correct placeholder instance'
        );
    }
}
