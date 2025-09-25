<?php

namespace BrizyPlaceholders;

/**
 * Class Registry
 * @package BrizyPlaceholders
 */
class Registry implements RegistryInterface
{
    /**
     * @var array <string, callable> List of placeholder class names and their factories
     */
    private $placeholderClasses = [];

    /**
     * @deprecated
     * @param PlaceholderInterface $instance
     * @param string $label
     * @param string $placeholderName
     * @param string $groupName
     *
     * @return mixed|void
     */
    public function registerPlaceholder(PlaceholderInterface $instance)
    {
        $this->registerPlaceholderClass(get_class($instance), function () use ($instance) {
            return $instance;
        });
    }

    public function registerPlaceholderClass(string $placeholderClass, callable $factory)
    {
        $this->placeholderClasses[$placeholderClass] = $factory;
    }

    /**
     * @return PlaceholderInterface|null
     * @inheritDoc
     */
    public function getPlaceholderSupportingName($name)
    {
        foreach ($this->placeholderClasses as $class => $factory) {
            /**
             * @var PlaceholderInterface $class
             */
            if ($class::support($name)) {
                return $factory();
            }
        }

        return null;
    }
}
