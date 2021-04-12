<?php

namespace App\Builders\Helpers;

class SortingHelpers
{
    /**
     * @param $useStmts
     *
     * @return array
     */
    public static function sortUseStmts($useStmts): array
    {
        $useStmts = collect($useStmts)->sort(function ($a, $b) {
            $aLen = strlen($a['stmt']) + strlen(isset($a['as']) ? ' as ' . $a['as'] : '');
            $bLen = strlen($b['stmt']) + strlen(isset($b['as']) ? ' as ' . $b['as'] : '');
            return $aLen - $bLen;
        })->toArray();

        $uniqueSortStmts = collect();

        foreach ($useStmts as $use) {
            $stmt = $use['stmt'];
            $as = $use['as'] ?? null;

            if ($uniqueSortStmts->first(fn ($s) => $s['stmt'] === $stmt)) {
                continue;
            }

            $uniqueSortStmts->push([
                'stmt' => $stmt,
                'as' => $as,
            ]);
        }

        return $uniqueSortStmts->toArray();
    }
}