<?php

namespace App\Services;

use App\Models\ProjectSetting;

/**
 * Class ProjectSettingsService
 * @package App\Services
 */
class ProjectSettingsService
{
    /**
     * @param $settingName
     * @param $value
     * @param $projectId
     * @return bool
     */
    public static function saveSetting($settingName, $value, $projectId): bool
    {
        $setting = ProjectSetting::auth()->project($projectId)->where('name', $settingName)->first();

        if ($setting) {
            $setting->value = $value;
            $setting->save();
        }
        else {
            $setting = ProjectSetting::create([
                'name' => $settingName,
                'value' => $value,
                'projectId' => $projectId
            ]);

            if (!$setting instanceof ProjectSetting) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $settingName
     * @param $projectId
     * @return null
     */
    public static function getSetting($settingName, $projectId)
    {
        $setting = ProjectSetting::auth()->project($projectId)->where('name', $settingName)->first();

        return $setting->value ?? null;
    }
}
