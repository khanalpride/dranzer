<?php

namespace App\Services;

use App\Models\Asset;

/**
 * Class AssetService
 * @package App\Services
 */
class AssetService
{
    /**
     * @param $key
     * @param $projectId
     * @param string $module
     * @return mixed
     */
    public static function getAsset($key, $projectId, $module = 'layout')
    {
        return Asset::project($projectId)->where('key', $key)->where('module', $module)->first();
    }
}
