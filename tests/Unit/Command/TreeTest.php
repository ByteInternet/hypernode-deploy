<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Command;

use Deployer\Selector\Selector;
use Hypernode\Deploy\Command\Tree;
use PHPUnit\Framework\TestCase;

class TreeTest extends TestCase
{
    /**
     * @dataProvider selectorProvider
     */
    public function testSelectorMatchesStage(?string $selector, string $stage, bool $expected): void
    {
        $parsedSelector = $selector === null ? null : Selector::parse($selector);

        $this->assertSame($expected, Tree::selectorMatchesStage($parsedSelector, $stage));
    }

    /**
     * @return array<string, array{?string, string, bool}>
     */
    public static function selectorProvider(): array
    {
        return [
            'no selector applies to every stage' => [null, 'production', true],
            'matching stage' => ['stage=production', 'production', true],
            'non-matching stage' => ['stage=production', 'staging', false],
            'matching stage with roles' => [
                'stage=production&roles=web,stage=production&roles=db',
                'production',
                true,
            ],
            'non-matching stage with roles' => ['stage=production&roles=web', 'staging', false],
            'roles only applies to every stage' => ['roles=web', 'staging', true],
            'negated stage matches other stage' => ['stage!=production', 'staging', true],
            'negated stage excludes stage' => ['stage!=production', 'production', false],
            'any of multiple stages matches' => ['stage=production,stage=staging', 'staging', true],
            'none of multiple stages matches' => ['stage=production,stage=staging', 'dev', false],
        ];
    }
}
