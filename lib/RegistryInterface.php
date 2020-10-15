<?php
namespace BrizyPlaceholders;

interface RegistryInterface
{
    /**
     * Register a placeholder class
     *
     * @param PlaceholderInterface $instance
     * @param $label
     * @param $placeholderName
     * @param $groupName
     *
     * @return mixed
     */
    public function registerPlaceholder(PlaceholderInterface $instance, $label, $placeholderName, $groupName);

    /**
     * Return all placeholders
     *
     * @return PlaceholderInterface[]
     */
    public function getAllPlaceholders();

    /**
     * @return array
     */
    public function getGroupedPlaceholders();

    /**
     * @param $groupName
     *
     * @return PlaceholderInterface[]
     */
    public function getPlaceholdersByGroup($groupName);

    /**
     * It will return first placeholder that supports the $name;
     *
     * @param $name
     *
     * @return mixed
     */
    public function getPlaceholderSupportingName($name);
}
