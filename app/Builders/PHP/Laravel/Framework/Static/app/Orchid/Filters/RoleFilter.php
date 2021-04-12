<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Orchid\Screen\Field;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;
use Orchid\Platform\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class RoleFilter extends Filter
{
    /**
     * @var array
     */
    public $parameters = [
        'role',
    ];

    /**
     * @return string
     */
    public function name(): string
    {
        return __('Roles');
    }

    /**
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        return $builder->whereHas('roles', function (Builder $query) {
            $query->where('slug', $this->request->get('role'));
        });
    }

    /**
     * @return Field[]
     */
    public function display(): array
    {
        return [
            Select::make('role')
                ->fromModel(Role::class, 'name', 'slug')
                ->empty()
                ->value($this->request->get('role'))
                ->title(__('Roles')),
        ];
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->name().': '.Role::where('slug', $this->request->get('role'))->first()->name;
    }
}
