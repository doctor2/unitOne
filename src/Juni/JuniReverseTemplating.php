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

    public function parseVariables(string $rawTemplate, string $processedTemplate): array
    {
        $this->validateTemplate($rawTemplate);

        $regexTemplate = $this->formRegexTemplate($rawTemplate);

        if (!preg_match_all($regexTemplate, $processedTemplate, $matchesValues)) {
            throw new ResultTemplateMismatchException();
        }

        preg_match_all($regexTemplate, $rawTemplate, $matchesNames);

        return array_merge(
            $this->getRawVariables($matchesValues, $matchesNames),
            $this->getVariablesWithHtmlEscaped($matchesValues, $matchesNames)
        );
    }

    private function getRawVariables(array $matchesValues, array $matchesNames): array
    {
        $variables = [];

        foreach ($matchesValues as $key => $value) {
            if (strpos($key, self::RAW_VARIABLE_NAME) === false) {
                continue;
            }

            $name = trim($matchesNames[$key][0], $this->tag);
            $variables[$name] = $value[0];
        }

        return $variables;
    }

    private function getVariablesWithHtmlEscaped(array $matchesValues, array $matchesNames): array
    {
        $variables = [];

        foreach ($matchesValues as $key => $value) {
            if (strpos($key, self::HTML_ESCAPED_VARIABLE_NAME) === false) {
                continue;
            }

            $name = trim($matchesNames[$key][0], $this->tagWithHtmlEscaped);
            $variables[$name] = htmlspecialchars_decode($value[0]);
        }

        return $variables;
    }

    private function formRegexTemplate(string $rawTemplate): string
    {
        $regexFromTemplate = $this->formRegexTemplateCallback(preg_quote($rawTemplate), self::HTML_ESCAPED_VARIABLE_NAME, $this->tagWithHtmlEscaped);

        $regexFromTemplate = $this->formRegexTemplateCallback($regexFromTemplate, self::RAW_VARIABLE_NAME, $this->tag);

        return '/' . $regexFromTemplate . '/';
    }

    private function formRegexTemplateCallback(string $regexFromTemplate, string $regexVariable, string $tag): string
    {
        $count = 0;

        return preg_replace_callback(
            $this->formCallbackRegex($tag),
            static function () use ($regexVariable, &$count) {
                $count++;

                return sprintf('(?<%s%s>.*)', $regexVariable, $count);
            },
            $regexFromTemplate
        );
    }

    /**
     * Формируется regex вида /\\\{\w+\\\}/
     */
    private function formCallbackRegex(string $tag): string
    {
        $formedTag = substr_replace($tag, '%s', strlen($tag) / 2, 0);

        return '/' . sprintf(preg_quote(preg_quote($formedTag)), '\w+') . '/';
    }

    private function validateTemplate(string $rawTemplate): void
    {
        preg_match_all(sprintf('/[%s]/', preg_quote($this->tag . $this->tagWithHtmlEscaped)), $rawTemplate, $matches);

        $tagsFromTemplate = reset($matches);

        if (count($tagsFromTemplate) % 2 === 1) {
            throw new InvalidTemplateException();
        }

        $this->validateRightTagPosition($tagsFromTemplate);
    }

    private function validateRightTagPosition(array $tagsFromTemplate): void
    {
        $variable = '';
        $tags = [$this->tagWithHtmlEscaped, $this->tag];

        foreach ($tagsFromTemplate as $tag) {
            $variable .= $tag;

            if (strpos($this->tag, $variable) === false && strpos($this->tagWithHtmlEscaped, $variable) === false) {
                throw new InvalidTemplateException();
            }

            if (strlen($variable) % 2 === 0 && in_array($variable, $tags)) {
                $variable = '';
            }
        }
    }
}
