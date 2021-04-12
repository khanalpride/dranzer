<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use Orchid\Screen\Action;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;

class PlatformScreen extends Screen
{
    /**
     * Display header name.
     *
     * @var string
     */
    public $name = 'Dashboard';

    /**
     * Display header description.
     *
     * @var string
     */
    public $description = 'Welcome to your Orchid admin panel.';

    /**
     * Query data.
     *
     * @return array
     */
    public function query(): array
    {
        return [];
    }

    /**
     * Button commands.
     *
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            Link::make('Website')
                ->href('http://orchid.software')
                ->icon('globe-alt'),

            Link::make('Documentation')
                ->href('https://orchid.software/en/docs')
                ->icon('docs'),

            Link::make('GitHub')
                ->href('https://github.com/orchidsoftware/platform')
                ->icon('social-github'),
        ];
    }

    /**
     * Views.
     *
     * @return Layout[]
     *
     */
    public function layout(): array
    {
        return [Layout::view('admin.partials.dashboard')];
    }
}
