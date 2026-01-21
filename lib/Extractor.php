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
    private $SIMPLE_PLACEHOLDERS = [];

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
        @ini_set('pcre.backtrack_limit', 9000000);
        $this->registry = $registry;
        $this->logger = $logger;
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
                    list($placeholder, $i) = $this->extractPlaceholder($tokens, $i, $content);
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

            $tmpPlaceholder = new ContentPlaceholder(
                $placeholder['name'],
                $placeholder['original'],
                $placeholder['attributes'] ? $this->getPlaceholderAttributes($placeholder['attributes']) : [],
                $placeholder['content'] ?? ''
            );
            $pHash = $tmpPlaceholder->getUid();

            $placeholderInstances[$pHash] = $instance;
            $contentPlaceholders[$pHash] = $tmpPlaceholder;

            $pos = strpos($content, $contentPlaceholders[$pHash]->getPlaceholder());

            $length = strlen($contentPlaceholders[$pHash]->getPlaceholder());

            if ($pos !== false) {
                $content = substr_replace($content, $pHash, $pos, $length);
            }
        }

        // transform placeholder wrappers to real placeholders
        list($_contentPlaceholders,$_placeholderInstances) = $this->transformPlaceholderWrappersToRealRegisteredPlaceholders($contentPlaceholders,$placeholderInstances);

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

        $placeholders = [];
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $name = $token->getName();
            switch ($name) {
                case 'T_PLACEHOLDER':
                    list($placeholder, $i) = $this->extractPlaceholder($tokens, $i, $content);
                    $placeholders[] = $placeholder;
                    break;
            }
        }
        $contentPlaceholders = [];
        foreach ($placeholders as $i => $placeholder) {
            $tP = new ContentPlaceholder(
                $placeholder['name'],
                $placeholder['original'],
                $placeholder['attributes'] ? $this->getPlaceholderAttributes($placeholder['attributes']) : [],
                $placeholder['content'] ?? ""
            );

            $pHash = $tP->getUid();
            $contentPlaceholders[$pHash] = $tP;

            $pos = strpos($content, $contentPlaceholders[$pHash]->getPlaceholder());

            $length = strlen($contentPlaceholders[$pHash]->getPlaceholder());

            if ($pos !== false) {
                $replace = $callback($tP);
                $content = substr_replace($content, $replace, $pos, $length);
            }
        }

        // transform placeholder wrappers to real placeholders
        $_contentPlaceholders = $this->transformPlaceholderWrappersToRealPlaceholders($contentPlaceholders);

        return array($_contentPlaceholders, $content);
    }

    private function extractPlaceholder(array $tokens, $start = 0, $content = '')
    {
        $continueIndex = $start;
        $token = $tokens[$start];
        $count = count($tokens);
        $placeholder = $this->getPlaceholderFromToken($token);
        if (strpos($content, "end_{$placeholder['name']}") === false) {
            return [$placeholder, $continueIndex];
        }

        // check if the placeholder has an end_placeholder
        $placeholderContent = '';


        for ($i = $start + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            $name = $token->getName();
            switch ($name) {
                case 'T_PLACEHOLDER':
                    $pName = $this->getPlaceholderTokenValue($token);
                    if ($pName == $placeholder['name']) {
                        // here we have recursive placeholders
                        list($aPlaceholder, $i) = $this->extractPlaceholder($tokens, $i, $content);
                        $placeholderContent .= $aPlaceholder['original'];
                    } else {
                        $placeholderContent .= $token->getValue();
                    }
                    //$continueIndex = $i;
                    break;
                case 'T_END_PLACEHOLDER':
                    $pName = $this->getPlaceholderTokenValue($token);
                    if ($pName == "end_{$placeholder['name']}") {
                        $placeholder['content'] = $placeholderContent;
                        $placeholder['original'] .= $placeholderContent . $token->getValue();

                        return [$placeholder, $i];
                    } else {
                        $placeholderContent .= $token->getValue();
                    }
                    // $continueIndex = $i;
                    break;
                default:
                    $placeholderContent .= $token->getValue();
                    //$continueIndex = $i;
                    break;
            }
        }

        return [$placeholder, $continueIndex];
    }

    /**
     * @param array $contentPlaceholders
     * @param $callback
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
                } else {
                    $t = 0;
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
                } else {
                    $t = 0;
                }
            } else {
                $_contentPlaceholders[] = $placeholder;
                $_placeholderInstances[] = $placeholderInstances[$i];
            }
        }
        return [$_contentPlaceholders, $_placeholderInstances];
    }

    private function searchForPlaceholder($tokens, $placeholderName, $start = 0)
    {
        $count = count($tokens);
        for ($i = $start; $i < $count; $i++) {
            $token = $tokens[$i];
            $name = $token->getName();
            switch ($name) {
                case 'T_END_PLACEHOLDER':
                case 'T_PLACEHOLDER':
                    $pName = $this->getPlaceholderTokenValue($token);
                    if ($pName == $placeholderName) {
                        return $i;
                    }
                    break;
            }
        }

        return false;
    }

    private function collectContent($tokens, $start, $end)
    {
        $placeholderContent = '';
        for ($i = $start; $i < $end; $i++) {
            $token = $tokens[$i];
            $placeholderContent .= $token->getValue();
        }

        return $placeholderContent;
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
            'T_TEXT' => '(?<=}}).*?(?={{)|.*?(?={{)|(?<=}}).*|.*',
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
