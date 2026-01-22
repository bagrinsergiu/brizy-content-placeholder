<?php

namespace BrizyPlaceholders;

use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Lexer\Token\Token;
use Psr\Log\LoggerInterface;

/**
 * Class Extractor
 */
final class Extractor implements ExtractorInterface
{
    const ATTRIBUTE_REGEX = "/((?<attr_name>\w+)(?<array>\[(?<array_key>\w+)?\])?)\s*=\s*(?<quote>'|\"|\&quot;|\&apos;|\&#x27;)(?<attr_value>.*?)(\g{quote})(!?\s|$)/mi";
    private static $pcreConfigured = false;
    private static $lexer = null;

    /**
     * @var RegistryInterface
     */
    private $registry;
    /**
     * @var LoggerInterface|null
     */
    private $logger;


    /**
     * Extractor constructor.
     *
     * @param RegistryInterface $registry
     * @param null $logger
     */
    public function __construct($registry, $logger = null)
    {
        if (!self::$pcreConfigured) {
            @ini_set('pcre.backtrack_limit', 9000000);
            self::$pcreConfigured = true;
        }
        $this->registry = $registry;
        $this->logger = $logger;
    }

    public function stripPlaceholders($content)
    {
        list($contentPlaceholders, $returnedContent) = $this->extractIgnoringRegistry(
            $content,
            function (ContentPlaceholder $placeholder) {
                return '';  // Replace all placeholders with empty string
            }
        );

        return $returnedContent;
    }

    public function extract($content)
    {
        $tokens = $this->extractTokens($content);
        $placeholders = $this->extractPlaceholdersFromTokens($tokens);

        $contentPlaceholders = [];
        $placeholderInstances = [];
        $searchArray = [];      // Collect all search strings for batched replacement
        $replaceArray = [];     // Collect all replacement UIDs for batched replacement

        foreach ($placeholders as $i => $placeholder) {
            $tmpPlaceholder = new ContentPlaceholder(
                $placeholder['name'],
                $placeholder['original'],
                $placeholder['attributes'] ? $this->getPlaceholderAttributes($placeholder['attributes']) : [],
                $placeholder['content'] ?? ''
            );
            $pHash = $tmpPlaceholder->getUid();

            $instance = $this->registry->getPlaceholderSupportingName($placeholder['name']);
            // ignore unknown placeholders
            if (!$instance) {
                continue;
            }

            $placeholderInstances[$pHash] = $instance;
            $contentPlaceholders[$pHash] = $tmpPlaceholder;

            // Build arrays for batched replacement (O(n) instead of O(n×m))
            $searchArray[] = $tmpPlaceholder->getPlaceholder();
            $replaceArray[] = $pHash;
        }

        // Single str_replace call - significantly faster than loop with strpos + substr_replace
        if (!empty($searchArray)) {
            $content = str_replace($searchArray, $replaceArray, $content);
        }

        // transform placeholder wrappers to real placeholders
        list($_contentPlaceholders, $_placeholderInstances) = $this->transformPlaceholderWrappersToRealRegisteredPlaceholders($contentPlaceholders, $placeholderInstances);

        return array($_contentPlaceholders, $_placeholderInstances, $content);
    }

    public function extractIgnoringRegistry($content, $callback = null)
    {
        if (is_null($callback) && !is_callable($callback)) {
            $callback = function (ContentPlaceholder $placeholder) {
                return $placeholder->getUid();
            };
        }

        $tokens = $this->extractTokens($content);
        $placeholders = $this->extractPlaceholdersFromTokens($tokens);

        $contentPlaceholders = [];
        $searchArray = [];      // Collect all search strings for batched replacement
        $replaceArray = [];     // Collect all replacement strings for batched replacement

        foreach ($placeholders as $i => $placeholder) {
            $tP = new ContentPlaceholder(
                $placeholder['name'],
                $placeholder['original'],
                $placeholder['attributes'] ? $this->getPlaceholderAttributes($placeholder['attributes']) : [],
                $placeholder['content'] ?? ""
            );

            $pHash = $tP->getUid();
            $contentPlaceholders[$pHash] = $tP;

            // Build arrays for batched replacement (O(n) instead of O(n×m))
            $searchArray[] = $tP->getPlaceholder();
            $replaceArray[] = $callback($tP);
        }

        // Single str_replace call - significantly faster than loop with strpos + substr_replace
        if (!empty($searchArray)) {
            $content = str_replace($searchArray, $replaceArray, $content);
        }

        // transform placeholder wrappers to real placeholders
        $_contentPlaceholders = $this->transformPlaceholderWrappersToRealPlaceholders($contentPlaceholders);

        return array($_contentPlaceholders, $content);
    }

    /**
     * @param array $contentPlaceholders
     * @return array
     */
    protected function transformPlaceholderWrappersToRealPlaceholders(array $contentPlaceholders): array
    {
        $_contentPlaceholders = array();
        foreach ($contentPlaceholders as $i => $placeholder) {
            if ($placeholder->getName() == 'placeholder') {
                $attribute = $placeholder->getAttribute('content');
                if (!$attribute) continue;
                $base64_decode = base64_decode($attribute);
                list($ps, $hash) = $this->extractIgnoringRegistry($base64_decode);
                $ps = array_pop($ps);
                if (isset($ps)) {
                    $attrAray = $placeholder->getAttributes();
                    unset($attrAray['content']);
                    $ps->setAttributes(array_merge($ps->getAttributes(), $attrAray));
                    $ps->setPlaceholder($ps->buildPlaceholder(true));
                    $_contentPlaceholders[] = $ps;
                }
            } else {
                $_contentPlaceholders[] = $placeholder;
            }
        }
        return $_contentPlaceholders;
    }

    /**
     * @param array $contentPlaceholders
     * @param $callback
     * @return array
     */
    protected function transformPlaceholderWrappersToRealRegisteredPlaceholders(array $contentPlaceholders, array $placeholderInstances): array
    {
        $_contentPlaceholders = [];
        $_placeholderInstances = [];
        foreach ($contentPlaceholders as $i => $placeholder) {
            if ($placeholder->getName() == 'placeholder') {
                $attribute = $placeholder->getAttribute('content');
                if (!$attribute) continue;
                $base64_decode = base64_decode($attribute);
                list($ps, $psi, $content) = $this->extract($base64_decode);
                $ps = array_pop($ps);
                $psi = array_pop($psi);
                if (isset($ps)) {
                    $attrAray = $placeholder->getAttributes();
                    unset($attrAray['content']);
                    $ps->setAttributes(array_merge($ps->getAttributes(), $attrAray));
                    $ps->setPlaceholder($ps->buildPlaceholder(true));
                    $_contentPlaceholders[] = $ps;
                    $_placeholderInstances[] = $psi;
                }
            } else {
                $_contentPlaceholders[] = $placeholder;
                $_placeholderInstances[] = $placeholderInstances[$i];
            }
        }
        return [$_contentPlaceholders, $_placeholderInstances];
    }

    private function getPlaceholderFromToken($token)
    {
        $placeholder = [];
        $placeholder['name'] = $this->getPlaceholderTokenValue($token);
        $placeholder['original'] = $token->getValue();
        $placeholder['attributes'] = $this->getPlaceholderAttrTokenValue($token);

        return $placeholder;
    }

    private function extractTokens($content)
    {
        if (self::$lexer === null) {
            self::$lexer = new Lexer([
                'T_END_PLACEHOLDER' => '{{\s*(?<placeholderName>end_.*?)\s*}}',
                'T_PLACEHOLDER' => "{{\s*(?<placeholderName>.[^\s]+?)(?:\s(?<placeholderAttrs>.[^}}]+?))?\s*}}",
                'T_TEXT' => '(?<=}}).*?(?={{)|.*?(?={{)|(?<=}}).*|.*',
            ]);
        }

        return iterator_to_array(self::$lexer->lex($content));
    }

    /**
     * Pre-computes which placeholder token indices have matching end tags.
     * Single O(n) pass instead of O(n) per placeholder lookup.
     *
     * @param array $tokens The lexer tokens
     * @return array Set of token indices (as keys) that have matching end tags
     */
    private function findPlaceholdersWithEndTags(array $tokens): array
    {
        $hasEndTag = [];    // Token indices that have matching end tags
        $openStacks = [];   // name => [indices] - stack of open placeholder indices by name

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            $type = $token->getName();

            if ($type === 'T_PLACEHOLDER') {
                $name = $this->getPlaceholderTokenValue($token);
                if (!isset($openStacks[$name])) {
                    $openStacks[$name] = [];
                }
                $openStacks[$name][] = $i;
            } elseif ($type === 'T_END_PLACEHOLDER') {
                $endName = $this->getPlaceholderTokenValue($token);
                $baseName = substr($endName, 4); // Remove "end_" prefix

                if (!empty($openStacks[$baseName])) {
                    // Match with the most recent (innermost) placeholder of this name
                    $matchedIndex = array_pop($openStacks[$baseName]);
                    $hasEndTag[$matchedIndex] = true;
                }
            }
        }

        return $hasEndTag;
    }

    /**
     * Extracts all placeholders from tokens using a single-pass stack-based algorithm.
     * Uses arrays for content building (O(n) with implode) instead of string concatenation (O(n^2)).
     *
     * @param array $tokens The lexer tokens
     * @return array Array of extracted placeholder data
     */
    private function extractPlaceholdersFromTokens(array $tokens): array
    {
        // Pre-compute which placeholders have end tags in O(n)
        $hasEndTag = $this->findPlaceholdersWithEndTags($tokens);

        $placeholders = [];
        $stack = [];  // Stack of currently open placeholders
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];
            $tokenType = $token->getName();

            switch ($tokenType) {
                case 'T_PLACEHOLDER':
                    $placeholder = $this->getPlaceholderFromToken($token);

                    // O(1) lookup instead of O(n) scan
                    if (isset($hasEndTag[$i])) {
                        // Push onto stack with content array for efficient building
                        $stack[] = [
                            'placeholder' => $placeholder,
                            'contentParts' => [],  // Array for O(n) content building
                        ];
                    } else {
                        // No end tag - treat as simple placeholder
                        if (!empty($stack)) {
                            // Add to parent's content
                            $stack[count($stack) - 1]['contentParts'][] = $token->getValue();
                        } else {
                            // Top-level simple placeholder
                            $placeholders[] = $placeholder;
                        }
                    }
                    break;

                case 'T_END_PLACEHOLDER':
                    $endName = $this->getPlaceholderTokenValue($token);
                    // Extract the base name (remove "end_" prefix)
                    $baseName = substr($endName, 4);

                    // Find matching placeholder on stack (search from top)
                    $matchIndex = -1;
                    for ($j = count($stack) - 1; $j >= 0; $j--) {
                        if ($stack[$j]['placeholder']['name'] === $baseName) {
                            $matchIndex = $j;
                            break;
                        }
                    }

                    if ($matchIndex >= 0) {
                        // Pop the matched placeholder and any unclosed ones above it
                        $matched = $stack[$matchIndex];
                        $content = implode('', $matched['contentParts']);

                        // Build final placeholder data
                        $finalPlaceholder = $matched['placeholder'];
                        $finalPlaceholder['content'] = $content;
                        $finalPlaceholder['original'] .= $content . $token->getValue();

                        // Remove matched and everything above from stack
                        array_splice($stack, $matchIndex);

                        if (!empty($stack)) {
                            // Add completed placeholder to parent's content
                            $stack[count($stack) - 1]['contentParts'][] = $finalPlaceholder['original'];
                        } else {
                            // Top-level placeholder complete
                            $placeholders[] = $finalPlaceholder;
                        }
                    } else {
                        // Unmatched end tag - treat as text
                        if (!empty($stack)) {
                            $stack[count($stack) - 1]['contentParts'][] = $token->getValue();
                        }
                    }
                    break;

                default:
                    // T_TEXT or other tokens
                    if (!empty($stack)) {
                        // Add to current placeholder's content
                        $stack[count($stack) - 1]['contentParts'][] = $token->getValue();
                    }
                    break;
            }
        }

        // Handle any unclosed placeholders remaining on stack
        // Pop them as simple placeholders (without content)
        while (!empty($stack)) {
            $unclosed = array_pop($stack);
            if (!empty($stack)) {
                // Add to parent's content as-is
                $stack[count($stack) - 1]['contentParts'][] = $unclosed['placeholder']['original'];
            } else {
                // Top-level unclosed placeholder
                $placeholders[] = $unclosed['placeholder'];
            }
        }

        return $placeholders;
    }

    private function getPlaceholderAttrTokenValue(Composite $token)
    {
        if ($t = $token->offsetGet(2)) {
            return $t->getValue();
        }

        return null;
    }

    private function getPlaceholderTokenValue($token)
    {
        if ($token instanceof Composite) {
            return $token->offsetGet(0)->getValue();
        }
        if ($token instanceof Token) {
            return $token->getValue();
        }

        return null;
    }

    private function getPlaceholderAttributes($attributeString)
    {
        $attrString = trim($attributeString);
        $attrMatches = array();
        $attributes = array();
        preg_match_all(self::ATTRIBUTE_REGEX, $attrString, $attrMatches);

        if (isset($attrMatches[0]) && is_array($attrMatches[0])) {
            foreach ($attrMatches[0] as $i => $attStr) {
                $attrName = $attrMatches['attr_name'][$i];
                $attrValue = stripslashes(urldecode($attrMatches['attr_value'][$i]));
                $isArray = $attrMatches['array'][$i] != '';
                $arrayKey = $attrMatches['array_key'][$i];
                // check if the attribute is an array
                if ($isArray) {
                    if ($arrayKey) {
                        $attributes[$attrName][$arrayKey] = $attrValue;
                    } else {
                        $attributes[$attrName][] = $attrValue;
                    }
                } else {
                    $attributes[$attrName] = $attrValue;
                }
            }
        }

        return $attributes;
    }

}

