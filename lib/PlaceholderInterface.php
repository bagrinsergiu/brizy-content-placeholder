<?php

namespace BrizyPlaceholders;

interface PlaceholderInterface
{
    /**
     * Returns true if the placeholder can return a value for the given placeholder name
     *
     * @param $placeholderName
     *
     * @return mixed
     */
    public function support($placeholderName);


    /**
     * Return the string value that will replace the placeholder name in content
     *
     * @param ContextInterface $context
     * @param ContentPlaceholder $placeholder
     *
     * @return mixed
     */
    public function getValue(ContextInterface $context, ContentPlaceholder $placeholder);


    /**
     * It should return an unique identifier of the placeholder
     *
     * @return mixed
     */
    public function getUid();
}
