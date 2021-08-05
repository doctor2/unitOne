<?php

namespace Tests\Unit\Juni;

use App\Juni\Exception\InvalidTemplateException;
use App\Juni\Exception\ResultTemplateMismatchException;
use App\Juni\JuniReverseTemplating;
use PHPUnit\Framework\TestCase;

class JuniReverseTemplatingTest extends TestCase
{
    public function testResultTemplateMismatch(): void
    {
        $this->expectException(ResultTemplateMismatchException::class);
        $this->expectExceptionMessage('Result not matches original template.');

        $juniReverseTemplating = new JuniReverseTemplating();

        $juniReverseTemplating->parseVariables('Hello, my name is {{name}}.', 'Hello, my lastname is Juni.');
    }

    /**
     * @dataProvider getInvalidTemplates
     */
    public function testInvalidTemplate(string $rawTemplate, string $processedTemplate): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->expectExceptionMessage('Invalid template.');

        $juniReverseTemplating = new JuniReverseTemplating();

        $juniReverseTemplating->parseVariables($rawTemplate, $processedTemplate);
    }

    /**
     * @dataProvider getTemplates
     */
    public function testGetVariables(string $rawTemplate, string $processedTemplate, array $expectedVariables): void
    {
        $juniReverseTemplating = new JuniReverseTemplating();

        $actualVariables = $juniReverseTemplating->parseVariables($rawTemplate, $processedTemplate);

        $this->assertEquals($expectedVariables, $actualVariables);
    }

    public function getTemplates(): array
    {
        return [
            'success' => [
                'Hello, my name is {{name}}.',
                'Hello, my name is Juni.',
                ['name' => 'Juni'],
            ],
            'success_when_empty' => [
                'Hello, my name is {{name}}.',
                'Hello, my name is .',
                ['name' => ''],
            ],
            'success_without_escaped_symbols' => [
                'Hello, my name is {name}.',
                'Hello, my name is <robot>.',
                ['name' => '<robot>'],
            ],
            'success_with_escaped_symbols' => [
                'Hello, my name is {{name}}.',
                'Hello, my name is &lt;robot&gt;.',
                ['name' => '<robot>'],
            ],
            'success_with_multiple_tags' => [
                'Hello, my name is {{name}}.{hi}, my name is {{nameSecond}}.',
                'Hello, my name is &lt;robot&gt;.Hello, my name is Robert.',
                ['name' => '<robot>', 'hi' => 'Hello', 'nameSecond' => 'Robert'],
            ],
        ];
    }

    public function getInvalidTemplates(): array
    {
        return [
            'invalid_by_number_of_tag' => [
                'Hello, my name is {name{}.',
                'Hello, my name is Juni.'
            ],
            'invalid_by_tag_position' => [
                'Hello, my name is }{name{}.',
                'Hello, my name is Juni.'
            ],
            'invalid_by_tag_position_1' => [
                'Hello, my name is }name}.',
                'Hello, my name is Juni.'
            ],
            'invalid_by_tag_position_2' => [
                'Hello, my name is }name{.',
                'Hello, my name is Juni.'
            ],
        ];
    }
}
