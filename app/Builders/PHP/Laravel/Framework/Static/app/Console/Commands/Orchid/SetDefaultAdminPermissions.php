<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Orchid\Support\Facades\Dashboard;

class SetDefaultAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets default admin permissions.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $firstUser = User::first();

        if (!$firstUser) {
            $this->info('No permissions assigned as there are no users.');
            return 0;
        }

        $primaryColumn = $firstUser->getKeyName();

        $ids = User::all()
            ->pluck($primaryColumn)
            ->filter(function ($c) {
                return $c !== null;
            });

        foreach ($ids as $id) {
            Dashboard::modelClass(User::class)
                ->findOrFail($id)
                ->forceFill([
                    'permissions' => Dashboard::getAllowAllPermission(),
                ])
                ->save();
        }

        $this->info(
            'Assigned default permissions to ' . count($ids) . (count($ids) > 1 ? ' users.' : 'user.')
        );

        return 0;
    }
}
