<?php

namespace BrizyPlaceholders;

abstract class AbstractPlaceholder implements PlaceholderInterface, \Serializable, \JsonSerializable
{
    use AbstractPlaceholderTrait;
}
