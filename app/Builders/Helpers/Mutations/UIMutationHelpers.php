<?php

namespace App\Builders\Helpers\Mutations;

class UIMutationHelpers
{
    /**
     * @return bool
     */
    public static function installAdmin(): bool
    {
        $uiConfig = app('mutations')->for('ui');

        return $uiConfig['installAdmin'];
    }

    /**
     * @return bool
     */
    public static function installOrchid(): bool
    {
        $installAdmin = static::installAdmin();

        if (!$installAdmin) {
            return false;
        }

        $uiConfig = app('mutations')->for('ui');

        $adminPanelType = $uiConfig['adminPanelType'];

        return $adminPanelType === 'Orchid';
    }
}
