<?php

namespace App\Builders\PHP\Laravel\Framework\Modules\Admin\Orchid\Helpers;

use Illuminate\Support\Str;
use App\Builders\PHP\Laravel\Framework\Database\Blueprint;
use App\Builders\PHP\Laravel\Framework\Database\TableColumn;

class OrchidRelationHelpers
{
    /**
     *
     * @param $props
     * @param array $blueprints
     * @return array|null
     */
    public static function getHasManyRelationMeta($props, array $blueprints): ?array
    {
        $modelId = $props['modelId'] ?? null;
        $displayColumnId = $props['displayColumnId'] ?? null;

        if (!$modelId || !$displayColumnId) {
            return null;
        }

        $blueprint = collect($blueprints)->first(fn (Blueprint $blueprint) => $blueprint->getId() === $modelId);

        if (!$blueprint) {
            return null;
        }

        $displayColumn = collect($blueprint->getColumns())->first(fn (TableColumn $c) => $c->getId() === $displayColumnId);

        if (!$displayColumn) {
            return null;
        }

        $modelName = Str::studly($blueprint->getName());
        $displayColumnName = $displayColumn->getName();

        return [
            'modelName'         => $modelName,
            'displayColumnName' => $displayColumnName,
        ];
    }
}
