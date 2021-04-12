<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    /**
     * @param $settingName
     * @param $value
     * @return bool
     */
    public static function saveSetting($settingName, $value): bool
    {
        $setting = Setting::auth()->where('name', $settingName)->first();

        if ($setting) {
            $setting->value = $value;
            $setting->save();
        }
        else {
            $setting = Setting::create([
                'name' => $settingName,
                'value' => $value,
            ]);

            if (!$setting instanceof Setting) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $settingName
     * @return null
     */
    public static function getSetting($settingName)
    {
        $setting = Setting::auth()->where('name', $settingName)->first();

        return $setting->value ?? null;
    }
}
