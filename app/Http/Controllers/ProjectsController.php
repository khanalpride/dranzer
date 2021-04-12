<?php

/**
 * @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 * @noinspection NullPointerExceptionInspection
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpPossiblePolymorphicInvocationInspection
 */

namespace App\Http\Controllers;

use Exception;
use RuntimeException;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SettingsService;
use Illuminate\Support\Facades\File;
use App\Builders\Helpers\MutationHelpers;
use App\Services\Mutation\MutationService;
use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\RenameProjectRequest;
use App\Builders\PHP\Laravel\ProjectBuilder;

/**
 * Class ProjectsController
 * @package App\Http\Controllers
 */
class ProjectsController extends Controller
{
    public function buildProject(Request $request): JsonResponse
    {
        $projectId = $request->input('projectId');

        $project = Project::auth()
            ->uuid($projectId)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $projectsDirectory = env('PROJECTS_DIR');

        if (!$projectsDirectory) {
            throw new RuntimeException('PROJECTS_DIR env variable is not configured properly.');
        }

        $projectsDirectory = rtrim($projectsDirectory, '/');

        File::ensureDirectoryExists($projectsDirectory);

        if (!File::exists($projectsDirectory)) {
            throw new RuntimeException('Projects directory does not exist or could not be created.');
        }

        $outputPath = "$projectsDirectory/$project->name";

        $buildDirName = (new ProjectBuilder)->build($projectId);

        if (!$buildDirName) {
            return response()->json(['message' => 'There was a problem building the project.'], 500);
        }

        $generatedDir = storage_path('app/generated');

        File::copyDirectory("$generatedDir/$buildDirName", $outputPath);

        File::deleteDirectory("$generatedDir/$buildDirName");

        return response()->json([]);
    }

    /**
     * @param CreateProjectRequest $request
     * @return JsonResponse
     */
    public function createProject(CreateProjectRequest $request): JsonResponse
    {
        $name = $request->input('name');
        $type = $request->input('type');

        $project = Project::auth()
            ->name($name)
            ->first();

        if ($project) {
            return response()->json(
                ['message' => 'A project with the same name already exists!'],
                422
            );
        }

        $project = Project::create(
            [
                'name'    => $name,
                'type'    => $type,
                'user_id' => auth()->user()->getAuthIdentifier(),
            ]
        );

        if (!$project instanceof Project) {
            return response()->json(
                [
                    'message' =>
                        'There was an unexpected problem creating a new project!',
                ],
                500
            );
        }

        $blueprintId = 'UserBlueprint-' . Str::random();

        $userModelMutations = MutationHelpers::getDefaultUserModelMutations($blueprintId);

        MutationService::mutate("database/blueprints/$blueprintId", 'User Blueprint', $userModelMutations, $project->uuid);

        return response()->json(['project' => $project]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getProjects(Request $request): JsonResponse
    {
        $limit = $request->input('limit');
        $skip = $request->input('skip');

        $limit = is_numeric($limit) ? $limit : 0;
        $skip = is_numeric($skip) ? $skip : 0;

        $order = SettingsService::getSetting('projectsOrder');

        $projects = $order === 'desc' ? Project::desc() : new Project();

        /** @noinspection StaticInvocationViaThisInspection */
        $projects = $projects
            ->auth()
            ->skip($skip)
            ->limit($limit)
            ->get();

        $totalProjects = Project::auth()->count();

        return response()->json(
            [
                'projects' => $projects,
                'order'    => $order ?? 'desc',
                'total'    => $totalProjects,
            ]
        );
    }

    /**
     * @param $uuid
     * @return JsonResponse
     */
    public function getProject($uuid): JsonResponse
    {
        $project = Project::auth()
            ->uuid($uuid)
            ->first();

        return response()->json(['project' => $project]);
    }

    /** @noinspection PhpComposerExtensionStubsInspection */

    /**
     * @param $uuid
     * @return JsonResponse
     * @throws Exception
     */
    public function deleteProject($uuid): JsonResponse
    {
        $project = Project::auth()
            ->uuid($uuid)
            ->first();

        if ($project) {
            $project->mutations->each->delete();
            $project->delete();
            return response()->json([], 204);
        }

        return response()->json(
            ['message' => 'The requested project was not found for deletion.'],
            404
        );
    }

    /**
     * @param RenameProjectRequest $request
     * @return JsonResponse
     */
    public function renameProject(RenameProjectRequest $request): JsonResponse
    {
        $projectId = $request->input('projectId');
        $updatedName = $request->input('name');

        $project = Project::auth()
            ->uuid($projectId)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $project->name = $updatedName;
        $project->save();

        return response()->json([]);
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function cloneProject(Request $request): JsonResponse
    {
        $projectId = $request->input('projectId');

        $project = Project::auth()
            ->uuid($projectId)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $mutations = $project->mutations()->get();

        $clonedCount = Project::where('cloned_from', $project->uuid)->count();

        $newProjectName = $project->name;

        $isNumericalEnding = is_numeric(substr($newProjectName, strlen($newProjectName) - 1));

        if ($isNumericalEnding) {
            $newProjectName = substr($newProjectName, 0, -1) . (substr(
                        $newProjectName, strlen($newProjectName) - 1
                    ) + 1);
        } else {
            if (!Str::contains($newProjectName, 'clone')) {
                $newProjectName = trim($newProjectName, '_') . '_clone';

                $duplicateProjectNameCount = Project::where('name', 'like', $newProjectName)->count();

                if ($duplicateProjectNameCount > 0) {
                    $newProjectName .= '_' . ($duplicateProjectNameCount + 1);
                }
            }

            if ($clonedCount > 0) {
                $newProjectName .= trim($newProjectName, '_') . '_' . ($clonedCount + 1);
            }
        }

        $clonedProject = Project::create(
            [
                'name'        => $newProjectName,
                'type'        => 'scaffolding',
                'cloned_from' => $project->uuid,
                'user_id'     => auth()->user()->getAuthIdentifier(),
            ]
        );

        foreach ($mutations as $mutation) {
            $clonedMutation = $mutation->replicate();
            $clonedMutation->project_id = $clonedProject->uuid;
            $clonedMutation->save();
        }

        return response()->json(['cloned' => $clonedProject]);
    }
}
