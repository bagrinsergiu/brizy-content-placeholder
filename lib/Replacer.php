<?php

namespace BrizyPlaceholders;

use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Runtime;



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
        Runtime::enableCoroutine(true);
        Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);
    }

    /**
     * @param $content
     * @param ContextInterface $context
     *
     *$subContext @return string|string[]
     */
    public function replacePlaceholdersAsync($content, ContextInterface $context)
    {
        $extractor = new Extractor($this->registry);
        list($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor) = $extractor->extract($content);

        $context->afterExtract($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor);

        if ($contentPlaceholders && $instancePlaceholders) {
            $content = $this->replaceSequenciallyWithExtractedData($contentPlaceholders, $instancePlaceholders, $contentAfterExtractor, $context);
        }
        return $content;
    }
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

            $wg = new Coroutine\WaitGroup();
            foreach ($contentPlaceholders as $index => $contentPlaceholder) {
                try {

                    $uid = $contentPlaceholder->getUid();
                    /**
                     * @var PlaceholderInterface $instancePlaceholder ;
                     */
                    $instancePlaceholder = $instancePlaceholders[$index];

                    if ($instancePlaceholder) {
                        $wg->add();
                        Coroutine::create(function () use ($wg, $uid, $instancePlaceholder, $context, $contentPlaceholder, &$values) {
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
        };

        if (Coroutine::getCid() > 0) {
            $closure();
        } else {
            Coroutine\run($closure);
        }

        //return  str_replace(array_keys($values), array_values($values), $contentAfterExtractor);
        if(count($values))
        {
            $contentAfterExtractor = strtr($contentAfterExtractor,$values);
        }
        return $contentAfterExtractor;
    }

    public function replaceSequenciallyWithExtractedData(array $contentPlaceholders, array $instancePlaceholders, $contentAfterExtractor, ContextInterface $context)
    {
        $values = array();
        foreach ($contentPlaceholders as $index => $contentPlaceholder) {
            try {

                $uid = $contentPlaceholder->getUid();
                /**
                 * @var PlaceholderInterface $instancePlaceholder ;
                 */
                $instancePlaceholder = $instancePlaceholders[$index];

                if ($instancePlaceholder) {
                    $value = (string)$instancePlaceholder->getValue($context, $contentPlaceholder);
                    if ($instancePlaceholder->shouldFallbackValue($value, $context, $contentPlaceholder)) {
                        $values[$uid] = $instancePlaceholder->getFallbackValue($context, $contentPlaceholder);
                    } else {
                        $values[$uid] = $value;
                    }
                }

            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error($e->getMessage(), ['placeholder' => $contentPlaceholder->getName(), 'attributes' => $contentPlaceholder->getAttributes()]);
                }
                continue;
            }
        }

        //return  str_replace(array_keys($values), array_values($values), $contentAfterExtractor);
        if(count($values))
        {
            $contentAfterExtractor = strtr($contentAfterExtractor,$values);
        }
        return $contentAfterExtractor;
    }
}
