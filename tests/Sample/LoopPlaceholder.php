<?php

namespace BrizyPlaceholdersTests\Sample;

use BrizyPlaceholders\ContentPlaceholder;
use BrizyPlaceholders\ContextInterface;
use BrizyPlaceholders\EmptyContext;
use BrizyPlaceholders\PlaceholderInterface;
use BrizyPlaceholders\Replacer;

class LoopPlaceholder implements PlaceholderInterface
{

    /**
     * @var Replacer
     */
    private $replacer;

    public function __construct(Replacer $replacer)
    {
        $this->replacer = $replacer;
    }

    /**
     * Returns true if the placeholder can return a value for the given placeholder name
     *
     * @param $placeholderName
     *
     * @return mixed
     */
    public function support($placeholderName)
    {
        return strpos($placeholderName, 'placeholder_loop') === 0;
    }

    /**
     * Return the string value that will replace the placeholder name in content
     *
     * @param ContextInterface $context
     * @param ContentPlaceholder $placeholder
     *
     * @return mixed
     */
    public function getValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        $content = $placeholder->getContent();

        $returnContent = '';
        for ($i = 0; $i < 5; $i++) {
            // here you can create a custom context special for this loop an add in it some data
            /// that will be used in the placeholders from $content
            $returnContent .= $this->replacer->replacePlaceholders($content, new EmptyContext());
        }

        return $returnContent;
    }

    public function shouldFallbackValue($value, ContextInterface $context, ContentPlaceholder $placeholder)
    {
        return empty($value);
    }

    public function getFallbackValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        $attributes = $placeholder->getAttributes();
        return isset($attributes[PlaceholderInterface::FALLBACK_KEY]) ? $attributes[PlaceholderInterface::FALLBACK_KEY] : '';
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
