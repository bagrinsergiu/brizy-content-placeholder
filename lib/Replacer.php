<?php

namespace BrizyPlaceholders;

use Psr\Log\LoggerInterface;

/**
 * Class Replacer
 */
class Replacer
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
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var Extractor|null
     */
    private $extractor;

    /**
     * Brizy_Content_PlaceholderReplacer constructor.
     *
     * @param $registry
     */
    public function __construct($registry, LoggerInterface $logger = null)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Get or create the Extractor instance (cached for reuse).
     */
    private function getExtractor(): Extractor
    {
        if ($this->extractor === null) {
            $this->extractor = new Extractor($this->registry, $this->logger);
        }
        return $this->extractor;
    }

    /**
     * @param $content
     * @param ContextInterface $context
     *
     *$subContext @return string|string[]
     */
    public function replacePlaceholders($content, ContextInterface $context)
    {
        $extractor = $this->getExtractor();
        list($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor) = $extractor->extract($content);

        $context->afterExtract($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor);

        if ($contentPlaceholders && $instancePlaceholders) {
            $content = $this->replaceWithExtractedData($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor, $context);
        }
        return $content;
    }

    /**
     * @param ContentPlaceholder[] $contentPlaceholders
     * @param PlaceholderInterface[] $instancePlaceholders
     * @param string $contentAfterExtractor
     */
    public function replaceWithExtractedData(array $contentPlaceholders, array $instancePlaceholders, $contentAfterExtractor, ContextInterface $context)
    {
        $replacements = [];  // uid => value associative array for strtr


        $values = [];
        foreach ($contentPlaceholders as $index => $contentPlaceholder) {
            try {
                $instancePlaceholder = $instancePlaceholders[$index] ?? null;
                $iUid = $instancePlaceholder->getUid();
//                if(isset($values[$iUid])) {
//                     $replacements[$contentPlaceholder->getUid()] = $values[$iUid];
//                     continue;
//                } else
                // Compute value first, only add to map on success
                if ($instancePlaceholder) {
                    $value = $instancePlaceholder->getValue($context, $contentPlaceholder);

                    if ($instancePlaceholder->shouldFallbackValue($value, $context, $contentPlaceholder)) {
                        $replacementValue = $instancePlaceholder->getFallbackValue($context, $contentPlaceholder);
                    } else {
                        $replacementValue = $value;
                    }
                } else {
                    $replacementValue = '';
                }

                $replacements[$contentPlaceholder->getUid()] = $values[$iUid] = $replacementValue;

            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error($e->getMessage(), [
                        'placeholder' => $contentPlaceholder->getName(),
                        'attributes' => $contentPlaceholder->getAttributes()
                    ]);
                }
                // Skip this placeholder entirely on error
            }
        }

        // strtr with associative array is faster than str_replace:
        // - Single pass through content (vs multiple passes)
        // - Simultaneous matching (no cascading replacements)
        return strtr($contentAfterExtractor, $replacements);
    }

}
