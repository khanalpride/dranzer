<?php

namespace App\Services\Mutation;

use App\Models\Mutation;
use App\Models\Project;

/**
 * Class MutationService
 * @package App\Services\Mutation
 */
class MutationService
{
    /**
     * @param $path
     * @param $name
     * @param $value
     * @param $projectId
     * @param null $mutationId
     * @param bool $bulk
     * @return mixed
     */
    public static function mutate($path, $name, $value, $projectId, $mutationId = null, $bulk = false)
    {
        $project = Project::auth()->uuid($projectId)->first();

        if (!$project || $project->downloaded) {
            return false;
        }

        $mutation = Mutation::auth()
            ->project($projectId);

        if ($bulk) {
            $mutations = $value;
            $created = [];

            $paths = collect($mutations)->pluck('path')->toArray();

            Mutation::whereIn('path', $paths)->get()->each->delete();

            foreach ($mutations as $m) {
                $mut = Mutation::create([
                    'name' => $m['name'],
                    'path' => $m['path'],
                    'value' => $m['value'],
                    'project_id' => $projectId,
                ]);

                if (!$mut instanceof Mutation) {
                    Mutation::whereIn('path', $created)->get()->each->delete();
                    return false;
                }

                $created[] = $m['path'];
            }

            return true;
        }

        if ($mutationId) {
            $mutation = $mutation->where('uuid', $mutationId);
        }
        else {
            $mutation = $mutation->where('path', $path);
        }

        $mutation = $mutation->first();

        if ($mutation) {
            $mutation->name = $name;
            $mutation->path = $path;
            $mutation->value = $value;
            $mutation->save();
        }
        else {
            $mutation = Mutation::create([
                'name' => $name,
                'path' => $path,
                'value' => $value,
                'project_id' => $projectId,
            ]);

            if (!$mutation instanceof Mutation) {
                return false;
            }
        }

        return $mutation ?? null;
    }

    /**
     * @param $path
     * @param $projectId
     * @return mixed|null
     */
    public static function getMutation($path, $projectId)
    {
        return Mutation::auth()
            ->project($projectId)
            ->where('path', $path)
            ->first();
    }

    /**
     * @param $path
     * @param $projectId
     * @return mixed|null
     */
    public static function getMutationsLike($path, $projectId)
    {
        return Mutation::auth()
            ->project($projectId)
            ->where('path', 'like', "$path%")
            ->get();
    }

    /**
     * @param $requestedMutations
     * @param $projectId
     * @return array
     */
    public static function getMutations($projectId, $requestedMutations = []): array
    {
        $paths = collect($requestedMutations)->pluck('path')->toArray();

        $mutations = Mutation::auth()
            ->project($projectId);

        if (count($requestedMutations)) {
            $mutations = $mutations->whereIn('path', $paths);
        }

        $mutations = $mutations->get();

        $mapped = [];

        foreach ($requestedMutations as $mutation) {
            $mapped[$mutation['path']] = [
                'name' => $mutation['name']
            ];
        }

        foreach ($mutations as $mutation) {
            $mapped[$mutation->path]['uuid'] = $mutation->uuid;
            $mapped[$mutation->path]['name'] = $mutation->name;
            $mapped[$mutation->path]['path'] = $mutation->path;
            $mapped[$mutation->path]['value'] = $mutation->value;
        }

        return $mapped;
    }
}
