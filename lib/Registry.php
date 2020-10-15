<?php

namespace BrizyPlaceholders;

class Registry implements RegistryInterface
{

    public $placeholders;

    public function __construct()
    {
        $this->placeholders = [];
    }

    /**
     * @param PlaceholderInterface $instance
     * @param string $label
     * @param string $placeholderName
     * @param string $groupName
     *
     * @return mixed|void
     */
    public function registerPlaceholder(PlaceholderInterface $instance, $label, $placeholderName, $groupName)
    {
        $this->placeholders[$groupName][] = [
            'label'       => $label,
            'placeholder' => $placeholderName,
            'instance'    => $instance,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAllPlaceholders()
    {
        $all = [];
        foreach ($this->placeholders as $groupKey => $group) {
            foreach ($group as $aplaceholder) {
                $all[] = $aplaceholder;
            }
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function getGroupedPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @inheritDoc
     */
    public function getPlaceholdersByGroup($groupName)
    {
        if (isset($this->placeholders[$groupName])) {
            return $this->placeholders[$groupName];
        }
    }

    /**
     * @inheritDoc
     */
    public function getPlaceholderSupportingName($name)
    {
        foreach ($this->placeholders as $groupKey => $group) {
            foreach ($group as $aplaceholder) {
                if ($aplaceholder['instance']->support($name)) {
                    return $aplaceholder['instance'];
                }
            }
        }
    }
}
