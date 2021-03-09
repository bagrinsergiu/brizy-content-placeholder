<?php

namespace BrizyPlaceholders;

abstract class AbstractPlaceholder implements PlaceholderInterface
{
    const FALLBACK_KEY = '_fallback';

    /**
     * It should return an unique identifier of the placeholder
     *
     * @return mixed
     */
    public function getUid()
    {
        return md5(microtime() . mt_rand(0, 10000));
    }

    public function shouldFallbackValue($value, ContextInterface $context, ContentPlaceholder $placeholder)
    {
        return empty($value);
    }

    public function getFallbackValue(ContextInterface $context, ContentPlaceholder $placeholder)
    {
        $attributes = $placeholder->getAttributes();
        return isset($attributes[self::FALLBACK_KEY]) ? $attributes[self::FALLBACK_KEY] : '';
    }

}
