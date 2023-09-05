<?php
namespace BrizyPlaceholdersTests\Sample;

use BrizyPlaceholders\ContentPlaceholder;
use BrizyPlaceholders\ContextInterface;
use BrizyPlaceholders\PlaceholderInterface;

class TestPlaceholder implements PlaceholderInterface
{
    /**
     * @var null
     */
    private $supportedPlaceholder;

    public function __construct($supportedPlaceholder = 'placeholder')
    {

        $this->supportedPlaceholder = $supportedPlaceholder;
    }

    public function getConfigStructure()
    {
        return [
            'id' => $this->getUid(),
            'label' => $this->getLabel(),
            'name' => $this->getPlaceholder(),
            'placeholder' => "{{" . $this->getPlaceholder() . "}}",
            'display' => '',
            'attr' => (object)[]
        ];
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
        return strpos($placeholderName, $this->supportedPlaceholder) === 0 && $placeholderName!=='placeholder_loop';
    }


    /**
     * Return the string value that will replace the placeholder name in content
     *
     * @param ContextInterface $context
     * @param ContentPlaceholder  $placeholder
     *
     * @return mixed
     */
    public function getValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        return 'placeholder_value';
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

    public function getLabel()
    {
        // TODO: Implement getLabel() method.
    }

    public function setLabel($label)
    {
        // TODO: Implement setLabel() method.
    }

    public function getPlaceholder()
    {
        // TODO: Implement getPlaceholder() method.
    }

    public function setPlaceholder($placeholder)
    {
        // TODO: Implement setPlaceholder() method.
    }

    public function getAttributes()
    {
        // TODO: Implement getAttributes() method.
    }


    /**
     * It should return an unique identifier of the placeholder
     *
     * @return mixed
     */
    public function getUid()
    {
        return md5(serialize($this));
    }

    public function getVaryAttributes() {
        return [];
    }
}
