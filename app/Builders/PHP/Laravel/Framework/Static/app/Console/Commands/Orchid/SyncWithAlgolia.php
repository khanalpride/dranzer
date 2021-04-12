<?php

namespace App\Console\Commands;

use ReflectionClass;
use ReflectionException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class SyncWithAlgolia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all models with algolia.';

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
     * @throws ReflectionException
     */
    public function handle(): int
    {
        $models = File::allFiles(app_path('Models'));

        foreach ($models as $model) {
            if ($model->isDir()) {
                continue;
            }

            $modelClassPath = 'App\Models\\' . $model->getFilenameWithoutExtension();

            $classMeta = new ReflectionClass($modelClassPath);

            if (!in_array('Laravel\Scout\Searchable', $classMeta->getTraitNames())) {
                continue;
            }

            $this->info('Scheduling ' . $model->getFilenameWithoutExtension());

            Artisan::call('scout:import', [
                'model' => $modelClassPath,
            ]);
        }

        $this->line('');
        $this->comment('Dispatched all sync jobs!');

        return 0;
    }
}
