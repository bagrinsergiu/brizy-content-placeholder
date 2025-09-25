<?php

namespace BrizyPlaceholdersTests\BrizyPlaceholders;

use BrizyPlaceholders\PlaceholderInterface;
use BrizyPlaceholders\Registry;
use BrizyPlaceholders\Replacer;
use BrizyPlaceholdersTests\Sample\LoopPlaceholder;
use BrizyPlaceholdersTests\Sample\TestPlaceholder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RegistryTest extends TestCase
{
    use ProphecyTrait;


    public function testRegisterPlaceholder()
    {
        $registry    = new Registry();
        $registry->registerPlaceholderClass(TestPlaceholder::class, function() {
            return new TestPlaceholder('placeholder');
        });
        $registry->registerPlaceholderClass(LoopPlaceholder::class, function() {
            return new LoopPlaceholder($this->prophesize(Replacer::class)->reveal());
        });
        $this->assertInstanceOf(TestPlaceholder::class, $registry->getPlaceholderSupportingName('placeholder'), 'It should return an instance of TestPlaceholder' );
        $this->assertInstanceOf(LoopPlaceholder::class, $registry->getPlaceholderSupportingName('placeholder_loop'), 'It should return an instance of TestPlaceholder' );
    }


}
