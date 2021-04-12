<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Layouts\Rows;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Fields\Select;

class UserRoleLayout extends Rows
{
    /**
     * Views.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            Select::make('user.roles.')
                ->fromModel(Role::class, 'name')
                ->multiple()
                ->title(__('Name role'))
                ->help('Specify which groups this account should belong to'),
        ];
    }
}
