<?php

namespace BrizyPlaceholders;

use OpenSwoole\Core\Coroutine\WaitGroup;
use Psr\Log\LoggerInterface;

/**
 * Class Replacer
 */
final class Replacer
{
    private static $inCoRoutine = false;

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
     * @param $content
     * @param ContextInterface $context
     *
     *$subContext @return string|string[]
     */
    public function replacePlaceholders($content, ContextInterface $context)
    {
        $extractor = new Extractor($this->registry);
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
        $values = array();
        $closure = function () use ($context, $contentPlaceholders, $instancePlaceholders, &$values) {

            $wg = new WaitGroup();
            foreach ($contentPlaceholders as $index => $contentPlaceholder) {
                try {

                    $uid = $contentPlaceholder->getUid();
                    /**
                     * @var PlaceholderInterface $instancePlaceholder ;
                     */
                    $instancePlaceholder = $instancePlaceholders[$index];
                    if ($instancePlaceholder) {
                        go(function () use ($wg, $uid, $instancePlaceholder, $context, $contentPlaceholder,  &$values) {
                            $wg->add();
                            $value = $instancePlaceholder->getValue($context, $contentPlaceholder);

                            if ($instancePlaceholder->shouldFallbackValue($value, $context, $contentPlaceholder)) {
                                $values[$uid] = $instancePlaceholder->getFallbackValue($context, $contentPlaceholder);
                            } else {
                                $values[$uid] = $value;
                            }
                            $wg->done();
                        });
                    }

                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error($e->getMessage(), ['placeholder' => $contentPlaceholder->getName(), 'attributes' => $contentPlaceholder->getAttributes()]);
                    }
                    continue;
                }
            }
            $wg->wait(1000);

            Replacer::$inCoRoutine = false;
        };
        if (self::$inCoRoutine) {
            $closure();
        } else {
            self::$inCoRoutine = true;
            \co::run($closure);
        }
        $content = str_replace(array_keys($values), array_values($values), $contentAfterExtractor);
        return $content;
    }
}
