<?php
namespace BrizyPlaceholders;

abstract class AbstractPlaceholder implements PlaceholderInterface
{
    /**
     * It should return an unique identifier of the placeholder
     *
     * @return mixed
     */
    public function getUid()
    {
        return md5(microtime().mt_rand(0,10000));
    }
}
