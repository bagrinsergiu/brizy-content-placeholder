<?php

namespace BrizyPlaceholders;

class PlaceholderDependency
{
    private $type;
    private $identifier;
    private $metadata;

    public function __construct($type, $identifier, array $metadata = [])
    {
        $this->type = $type;
        $this->identifier = $identifier;
        $this->metadata = $metadata;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}