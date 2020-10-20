<?php

namespace BrizyPlaceholders;

/**
 * Class Replacer
 */
final class Replacer
{
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * Brizy_Content_PlaceholderReplacer constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param $content
     * @param ContextInterface $context
     *
     * @return string|string[]
     */
    public function replacePlaceholders($content, ContextInterface $context)
    {
        $toReplace           = array();
        $toReplaceWithValues = array();

        $extractor = new Extractor($this->registry);
        list($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor) = $extractor->extract($content);

        if ($contentPlaceholders && $instancePlaceholders) {
            foreach ($contentPlaceholders as $index =>$contentPlaceholder) {
                try {
                    $toReplace[] = $contentPlaceholder->getUid();
                    $instancePlaceholder = $instancePlaceholders[$index];
                    if ($instancePlaceholder) {
                        $toReplaceWithValues[] = $instancePlaceholder->getValue($context, $contentPlaceholder);
                    } else {
                        $toReplaceWithValues[] = '';
                    }

                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        $content = str_replace($toReplace, $toReplaceWithValues, $contentAfterExtractor);

        return $content;
    }

}
