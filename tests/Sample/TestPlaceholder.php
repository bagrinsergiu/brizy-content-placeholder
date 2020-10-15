<?php
namespace BrizyPlaceholdersTests\Sample;

use BrizyPlaceholders\ContextInterface;
use BrizyPlaceholders\PlaceholderInterface;

class TestPlaceholder implements PlaceholderInterface
{
    /**
     * Returns true if the placeholder can return a value for the given placeholder name
     *
     * @param $placeholderName
     *
     * @return mixed
     */
    public function support($placeholderName)
    {
        return strpos($placeholderName, 'placeholder') === 0;
    }


    /**
     * Return the string value that will replace the placeholder name in content
     *
     * @param ContextInterface $context
     * @param $placeholder
     *
     * @return mixed
     */
    public function getValue(ContextInterface $context, $placeholder)
    {
        return 'placeholder_value';
    }


    /**
     * It should return an unique identifier of the placeholder
     *
     * @return mixed
     */
    public function getUid()
    {
        return md5(microtime());
    }
}
