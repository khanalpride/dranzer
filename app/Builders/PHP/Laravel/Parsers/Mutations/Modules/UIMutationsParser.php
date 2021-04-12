<?php

namespace App\Builders\PHP\Laravel\Parsers\Mutations\Modules;

use Illuminate\Support\Collection;
use App\Builders\Helpers\MutationHelpers;
use App\Builders\PHP\Laravel\Parsers\Contracts\MutationParser;
use App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\OrchidModule;

/**
 * Class UIMutationsParser
 * @package App\Builders\PHP\Laravel\Parsers\Mutations\Modules
 */
class UIMutationsParser implements MutationParser
{
    /**
     * @param array $mutations
     * @param string $projectId
     * @return Collection
     */
    public function parse(array $mutations, string $projectId): Collection
    {
        $layouts = collect(MutationHelpers::filter('^ui\/layouts\/', $mutations))
            ->map(static fn ($layout) => $layout['value'])
            ->filter(static fn ($layout) => $layout['id'] ?? null);

        $adminLayout = collect($layouts)->first(fn ($layout) => $layout['type'] === 'admin');

        $customLayout = $layouts
            ->filter(static fn ($layout) => $layout['type'] === 'blade')
            ->filter(static fn ($layout) => ($layout['id'] ?? null))
            ->map(static function ($layout) use ($mutations) {
                $layoutId = $layout['id'];

                $layout['styling'] = MutationHelpers::first("^template\/styling\/$layoutId", $mutations);
                $layout['paths'] = MutationHelpers::first("assets\/mix\/paths\/$layoutId", $mutations);

                $layout['stylesheets'] = MutationHelpers::first("^assets\/mix\/config\/pp\/groups\/stylesheets\/$layoutId", $mutations) ?? [];
                $layout['scripts'] = MutationHelpers::first("^assets\/mix\/config\/pp\/groups\/scripts\/$layoutId", $mutations) ?? [];

                $layout['templateImages'] = MutationHelpers::first("^assets\/template\/images\/$layoutId", $mutations) ?? [];
                $layout['templateVideos'] = MutationHelpers::first("^assets\/template\/videos\/$layoutId", $mutations) ?? [];
                $layout['templateFonts'] = MutationHelpers::first("^assets\/template\/fonts\/$layoutId", $mutations) ?? [];
                $layout['templateScripts'] = MutationHelpers::first("^assets\/template\/scripts\/$layoutId", $mutations) ?? [];
                $layout['templateStylesheets'] = MutationHelpers::first("^assets\/template\/stylesheets\/$layoutId", $mutations) ?? [];
                $layout['copyableAssets'] = MutationHelpers::first("^assets\/mix\/copy\/$layoutId", $mutations) ?? [];

                $layout['contentWrapper'] = MutationHelpers::first("^ui\/custom\/content-wrapper\/$layoutId", $mutations);
                $layout['baseViewFile'] = MutationHelpers::first("^ui\/settings\/layouts\/theme\/main\/files\/index\/$layoutId", $mutations);
                $layout['partials'] = MutationHelpers::filter("^ui\/partials\/$layoutId", $mutations);
                $layout['views'] = collect(MutationHelpers::filter("^ui\/views\/$layoutId", $mutations))
                    ->map(fn ($v) => $v['value'])
                    ->toArray();

                return $layout;
            })
            ->first();

        $orchidAdminModules = collect(MutationHelpers::first('^config\/ui\/admin\/modules$', $mutations) ?? [])
            ->filter(fn ($mod) => $mod['selected'])
            ->sort(fn ($a, $b) => $a['index'] - $b['index'])
            ->map(static function ($mod) use ($mutations) {
                $moduleId = $mod['id'];
                $moduleConfig = MutationHelpers::first("^ui\/admin\/modules\/$moduleId", $mutations);
                return new OrchidModule($moduleConfig);
            })
            ->filter(static fn (OrchidModule $mod) => $mod->isValid())
            ->toArray();

        $orchidSidebarSections = MutationHelpers::first('^config\/ui\/admin\/sections$', $mutations) ?? [];

        return collect(
            [
                'layouts'        => $layouts,
                'customLayout'   => $customLayout,
                'installAdmin'   => $adminLayout !== null,
                // TODO: Refactor when more admin panel types are added.
                'adminPanelType' => 'Orchid',
                'orchid'         => [
                    'modules'         => $orchidAdminModules,
                    'sidebarSections' => $orchidSidebarSections,
                ]
            ]
        );
    }
}
