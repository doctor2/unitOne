<?php

namespace App\Juni;

use App\Juni\Exception\InvalidTemplateException;
use App\Juni\Exception\ResultTemplateMismatchException;

class JuniReverseTemplating
{
    private const RAW_VARIABLE_NAME = 'rawVariable';
    private const HTML_ESCAPED_VARIABLE_NAME = 'variableWithHtmlEscaped';

    private $tag;
    private $tagWithHtmlEscaped;

    public function __construct(string $tag = '{}', string $tagWithHtmlEscaped = '{{}}')
    {
        $this->tag = $tag;
        $this->tagWithHtmlEscaped = $tagWithHtmlEscaped;
    }

    /**
     * @return string[]
     */
    public function parseVariables(string $rawTemplate, string $processedTemplate): array
    {
        $this->validateTemplate($rawTemplate);

        $regexFromTemplate = $this->formRegexFromTemplate($rawTemplate);

        if (!preg_match_all($regexFromTemplate, $processedTemplate, $matchesValues)) {
            throw new ResultTemplateMismatchException();
        }

        preg_match_all($regexFromTemplate, $rawTemplate, $matchesNames);

        return array_merge(
            $this->getVariablesByMatchesNamesAndValues($matchesValues, $matchesNames, self::RAW_VARIABLE_NAME, $this->tag),
            $this->htmlDecodeVariables(
                $this->getVariablesByMatchesNamesAndValues($matchesValues, $matchesNames, self::HTML_ESCAPED_VARIABLE_NAME, $this->tagWithHtmlEscaped)
            ),
        );
    }

    /**
     * @param mixed[] $matchesValues
     * @param mixed[] $matchesNames
     *
     * @return string[]
     */
    private function getVariablesByMatchesNamesAndValues(array $matchesValues, array $matchesNames, string $variableName, string $tag): array
    {
        $variables = [];

        for ($i = 1; $i < count($matchesValues); $i++) {
            $key = $variableName . $i;

            if (!isset($matchesValues[$key])) {
                break;
            }

            $name = trim($matchesNames[$key][0], $tag);
            $variables[$name] = $matchesValues[$key][0];
        }

        return $variables;
    }

    /**
     * @param string[] $variables
     *
     * @return string[]
     */
    private function htmlDecodeVariables(array $variables): array
    {
        return array_map(function ($value) {
            return htmlspecialchars_decode($value);
        }, $variables);
    }

    private function formRegexFromTemplate(string $rawTemplate): string
    {
        $longTag = [
            'name' => self::RAW_VARIABLE_NAME,
            'tag' => $this->tag,
        ];
        $shortTag = [
            'name' => self::HTML_ESCAPED_VARIABLE_NAME,
            'tag' => $this->tagWithHtmlEscaped,
        ];

        if (strlen($this->tagWithHtmlEscaped) > strlen($this->tag)) {
            [$longTag, $shortTag] = [$shortTag, $longTag];
        }

        $regexFromTemplate = $this->replaceTemplateTagToRegexWithVariable(preg_quote($rawTemplate), $longTag['name'], $longTag['tag']);

        $regexFromTemplate = $this->replaceTemplateTagToRegexWithVariable($regexFromTemplate, $shortTag['name'], $shortTag['tag']);

        return '/' . $regexFromTemplate . '/';
    }

    /**
     * @phan-suppress PhanTypeMismatchArgumentInternal
     */
    private function replaceTemplateTagToRegexWithVariable(string $regexFromTemplate, string $regexVariable, string $tag): string
    {
        $count = 0;

        return preg_replace_callback(
            $this->formCallbackRegex($tag),
            static function () use ($regexVariable, &$count): string {
                $count++;

                return sprintf('(?<%s%d>.*)', $regexVariable, $count);
            },
            $regexFromTemplate
        );
    }

    /**
     * ?????????????????????? regex ???????? /\\\{\w+\\\}/
     */
    private function formCallbackRegex(string $tag): string
    {
        $tagWithSlash = '\\' . implode('\\', str_split($tag));

        return '/' . $this->insertInTheMiddleOfTheLine(preg_quote($tagWithSlash), '\w+') . '/';
    }

    /**
     * @phan-suppress PhanPartialTypeMismatchReturn
     */
    private function insertInTheMiddleOfTheLine(string $text, string $insertText): string
    {
        return substr_replace($text, $insertText, (int) (strlen($text) / 2), 0);
    }

    private function validateTemplate(string $rawTemplate): void
    {
        preg_match_all(sprintf('/[%s]/', preg_quote($this->tag . $this->tagWithHtmlEscaped)), $rawTemplate, $matches);

        $tagsFromTemplate = reset($matches);

        if ($tagsFromTemplate === false) {
            return;
        }

        if (count($tagsFromTemplate) % 2 === 1) {
            throw new InvalidTemplateException();
        }

        $this->validateRightTagPosition($tagsFromTemplate);
    }

    /**
     * @param string[] $tagsFromTemplate
     */
    private function validateRightTagPosition(array $tagsFromTemplate): void
    {
        $variable = '';
        $tags = [$this->tagWithHtmlEscaped, $this->tag];

        foreach ($tagsFromTemplate as $tag) {
            $variable .= $tag;

            if (strpos($this->tag, $variable) !== 0 && strpos($this->tagWithHtmlEscaped, $variable) !== 0) {
                throw new InvalidTemplateException();
            }

            if (strlen($variable) % 2 === 0 && in_array($variable, $tags, true)) {
                $variable = '';
            }
        }
    }
}
