<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;

/**
 * Class FrontendMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class FrontendMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $install = MutationHelpers::first('^config\/tailwind\/enabled', $mutations) ?? false;

        $twScreenMutations = MutationHelpers::first('^tailwind\/screens', $mutations)
            ?? $this->getDefaultScreenMutations();

        // Colors
        $twDefaultColorsMutations = collect(MutationHelpers::first('^tailwind\/colors\/default', $mutations) ?? [])
            ->filter(fn ($c) => $c['enabled']);

        $twCustomColorsMutations = MutationHelpers::first('^tailwind\/colors\/custom', $mutations) ?? [];

        $twColorsConfigMutations = MutationHelpers::first('^tailwind\/colors\/config', $mutations)
            ?? [
                'useDefaultColors' => true,
                'transparent'      => false,
                'current'          => false,
                'addDefault'       => true,
            ];

        // Spacing
        $twDefaultSpacingMutations = collect(MutationHelpers::first('^tailwind\/spacing\/default', $mutations) ?? [])
            ->filter(fn ($c) => $c['enabled']);
        $twCustomSpacingMutations = MutationHelpers::first('^tailwind\/spacing\/custom', $mutations) ?? [];

        $tailwindMutations = [
            'install'      => $install,
            'screen'       => $twScreenMutations,
            'colors'       => [
                'default' => $twDefaultColorsMutations,
                'custom'  => $twCustomColorsMutations
            ],
            'spacing'      => [
                'default' => $twDefaultSpacingMutations,
                'custom'  => $twCustomSpacingMutations
            ],
            'colorsConfig' => $twColorsConfigMutations,
        ];

        $navPartials = MutationHelpers::first('^ui\/custom\/partials\/navigation', $mutations)
            ?? [
                'config' => [
                    'cleanup'         => true,
                    'listStyle'       => 'ul',
                    'listClass'       => 'navbar-nav',
                    'listItemClass'   => 'nav-item',
                    'listAnchorClass' => 'nav-link',
                ],
                'items'  => []
            ];

        $alertPartials = collect(MutationHelpers::first('^ui\/custom\/partials\/alert', $mutations) ?? [])
            ->filter(static fn ($partial) => ($partial['name'] ?? null))
            ->toArray();

        $vueSetup = MutationHelpers::first('^frontend\/vue', $mutations)
            ?? [
                'config' => ['install' => false]
            ];

        $customPartials = collect(MutationHelpers::filter('^ui\/custom\/partials\/custom\/', $mutations) ?? [])
            ->map(fn ($p) => $p['value']);

        $views = collect(MutationHelpers::filter('^ui\/views', $mutations))->map(fn ($v) => $v['value']);

        $parsed = [
            'tailwind'       => $tailwindMutations,
            'vue'            => $vueSetup,
            'navPartials'    => $navPartials,
            'alertPartials'  => $alertPartials,
            'customPartials' => $customPartials,
            'views'          => $views,
        ];

        return collect($parsed);
    }

    /**
     * @return array[]
     */
    private function getDefaultScreenMutations(): array
    {
        return [
            [
                'id'          => Str::random(),
                'alias'       => 'sm',
                'breakpoints' => [
                    'min'           => true,
                    'max'           => false,
                    'minBreakpoint' => '640px',
                    'maxBreakpoint' => null,
                ]
            ],
            [
                'id'          => Str::random(),
                'alias'       => 'md',
                'breakpoints' => [
                    'min'           => true,
                    'max'           => false,
                    'minBreakpoint' => '768px',
                    'maxBreakpoint' => null,
                ]
            ],
            [
                'id'          => Str::random(),
                'alias'       => 'lg',
                'breakpoints' => [
                    'min'           => true,
                    'max'           => false,
                    'minBreakpoint' => '1024px',
                    'maxBreakpoint' => null,
                ]
            ],
            [
                'id'          => Str::random(),
                'alias'       => 'xl',
                'breakpoints' => [
                    'min'           => true,
                    'max'           => false,
                    'minBreakpoint' => '1280px',
                    'maxBreakpoint' => null,
                ]
            ],
            [
                'id'          => Str::random(),
                'alias'       => '2xl',
                'breakpoints' => [
                    'min'           => true,
                    'max'           => false,
                    'minBreakpoint' => '1536px',
                    'maxBreakpoint' => null,
                ]
            ],
        ];
    }
}
