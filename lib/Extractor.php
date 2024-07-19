<?php

namespace BrizyPlaceholders;

use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\Composite;

/**
 * Class Extractor
 */
final class Extractor implements ExtractorInterface
{
    const ATTRIBUTE_REGEX = "/((?<attr_name>\w+)(?<array>\[(?<array_key>\w+)?\])?)\s*=\s*(?<quote>'|\"|\&quot;|\&apos;|\&#x27;)(?<attr_value>.*?)(\g{quote})(!?\s|$)/mi";
    const SIMPLE_PLACEHOLDERS = [];

    /**
     * @var RegistryInterface
     */
    private $registry;


    /**
     * Extractor constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->SIMPLE_PLACEHOLDERS = ['placeholder', 'nav_item_url', 'nav_item_title','brizy_dc_image_alt','editor_menu_active_item'];
    }

    public function stripPlaceholders($content)
    {
        list($contentPlaceholders, $returnedContent) = $this->extractIgnoringRegistry($content);

        foreach ($contentPlaceholders as $i => $placeholder) {
            $placeholderString = $placeholder->getPlaceholder();
            $pos = strpos($content, $placeholderString);

            $length = strlen($placeholderString);

            if ($pos !== false) {
                $content = substr_replace($content, '', $pos, $length);
            }
        }

        return $content;

    }

    public function extract($content)
    {
        $tokens = $this->extractTokens($content);
        $placeholders = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $name = $token->getName();
            switch ($name) {
                case 'T_PLACEHOLDER':
                    list($placeholder, $i) = $this->extractPlaceholder($tokens, $i);
                    $placeholders[] = $placeholder;
                    break;
            }
        }
        $contentPlaceholders = [];
        $placeholderInstances = [];
        foreach ($placeholders as $i => $placeholder) {
            $instance = $this->registry->getPlaceholderSupportingName($placeholder['name']);
            // ignore unknown placeholders
            if (!$instance) {
                continue;
            }
            $placeholderInstances[$i] = $instance;
            $contentPlaceholders[$i] = new ContentPlaceholder(
                $placeholder['name'],
                $placeholder['original'],
                $this->getPlaceholderAttributes($placeholder['attributes']),
                $placeholder['content'] ?? ''
            );

            $pos = strpos($content, $contentPlaceholders[$i]->getPlaceholder());

            $length = strlen($contentPlaceholders[$i]->getPlaceholder());

            if ($pos !== false) {
                $content = substr_replace($content, $contentPlaceholders[$i]->getUid(), $pos, $length);
            }
        }

        return array($contentPlaceholders, $placeholderInstances, $content);
    }

    public function extractIgnoringRegistry($content, $callback = null)
    {
        if (is_null($callback) && !is_callable($callback)) {
            $callback = function (ContentPlaceholder $placeholder) {
                return $placeholder->getUid();
            };
        }

        $tokens = $this->extractTokens($content);
        $placeholders = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $name = $token->getName();
            switch ($name) {
                case 'T_PLACEHOLDER':
                    list($placeholder, $i) = $this->extractPlaceholder($tokens, $i);
                    $placeholders[] = $placeholder;
                    break;
            }
        }
        $contentPlaceholders = [];
        foreach ($placeholders as $i => $placeholder) {
            $contentPlaceholders[$i] = new ContentPlaceholder(
                $placeholder['name'],
                $placeholder['original'],
                $this->getPlaceholderAttributes($placeholder['attributes']),
                $placeholder['content'] ?? ""
            );

            $pos = strpos($content, $contentPlaceholders[$i]->getPlaceholder());

            $length = strlen($contentPlaceholders[$i]->getPlaceholder());

            if ($pos !== false) {
                $content = substr_replace($content, $callback($contentPlaceholders[$i]), $pos, $length);
            }
        }

        return array($contentPlaceholders, $content);
    }

    private function extractPlaceholder(array $tokens, $start = 0)
    {
        $continueIndex = $start;
        $token = $tokens[$start];

        $placeholder = $this->getPlaceholderFromToken($token);

        if (in_array($placeholder['name'], $this->SIMPLE_PLACEHOLDERS)) {
            return [$placeholder, $continueIndex];
        }

        // check if the placeholder has an end_placeholder
        $placeholderContent = '';
        $count = count($tokens);
        echo "Extract placeholder starting with {$start} having count {$count}\n";
        for ($i = $start + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            $name = $token->getName();
            switch ($name) {
                case 'T_PLACEHOLDER':
                    $pName = $this->getPlaceholderTokenValue($token);
                    if ($pName == $placeholder['name']) {
                        list($aPlaceholder, $i) = $this->extractPlaceholder($tokens, $i);
                        $placeholderContent .= $aPlaceholder['original'];
                    } else {
                        $placeholderContent .= $token->getValue();
                    }
                    break;
                case 'T_END_PLACEHOLDER':
                    $pName = $this->getPlaceholderTokenValue($token);
                    if ($pName == "end_{$placeholder['name']}") {
                        $placeholder['content'] = $placeholderContent;
                        $placeholder['original'] .= $placeholderContent.$token->getValue();

                        return [$placeholder, $i];
                    } else {
                        $placeholderContent .= $token->getValue();
                    }
                    break;
                default:
                    $placeholderContent .= $token->getValue();
                    break;
            }
        }

        return [$placeholder, $continueIndex];
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
        $lexer = new Lexer([
            'T_END_PLACEHOLDER' => '{{\s*(?<placeholderName>end_.*?)\s*}}',
            'T_PLACEHOLDER' => "{{\s*(?<placeholderName>.[^\s]+?)(?:\s(?<placeholderAttrs>.[^}}]+?))?\s*}}",
            'T_TEXT' => '(?:(?!{{.*?}}).)*',
        ]);

        /**
         * @var \Generator $tokens ;
         */
        $tokens = $lexer->lex($content);

        return iterator_to_array($tokens);
    }

    private function getPlaceholderAttrTokenValue(Composite $token)
    {
        if ($t = $token->offsetGet(2)) {
            return $t->getValue();
        }

        return null;
    }

    private function getPlaceholderTokenValue(Composite $token)
    {
        return $token->offsetGet(0)->getValue();
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
