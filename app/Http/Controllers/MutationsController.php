<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Mutation;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Mutation\MutationService;

/**
 * Class MutationsController
 * @package App\Http\Controllers
 */
class MutationsController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function mutate(Request $request): JsonResponse
    {
        $mutationId = $request->input('mutationId');
        $path = $request->input('path');
        $name = $request->input('name');
        $value = $request->input('value');
        $projectId = $request->input('projectId');
        $bulk = $request->input('bulk');
        $returnMutation = $request->input('returnMutation');

        $mutated = MutationService::mutate(
            $path,
            $name,
            $value,
            $projectId,
            $mutationId,
            $bulk
        );

        if ($mutated === false) {
            return response()->json([], 500);
        }

        if ($bulk) {
            return response()->json([]);
        }

        $response = ['uuid' => $mutated->uuid ?? null];

        if ($returnMutation === true) {
            $response = ['mutation' => $mutated];
        }

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMutation(Request $request): JsonResponse
    {
        $path = $request->input('path');
        $projectId = $request->input('projectId');
        $like = $request->input('like') === true;

        if ($like) {
            return $this->getMutationsLike($request);
        }

        $mutation = MutationService::getMutation($path, $projectId);

        if (!$mutation) {
            return response()->json(['value' => null]);
        }

        return response()->json(
            [
                'uuid' => $mutation->uuid,
                'value' => $mutation->value,
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMutations(Request $request): JsonResponse
    {
        $pendingMutations = $request->input('mutations');
        $projectId = $request->input('projectId');

        $mutations = MutationService::getMutations(
            $projectId,
            $pendingMutations
        );

        return response()->json(['mutations' => $mutations]);
    }

    /**
     * @param $projectId
     * @param $path
     * @return JsonResponse
     */
    public function deleteMutation($projectId, $path): JsonResponse
    {
        $like = Str::endsWith($path, '*');

        $mutation = Mutation::auth()
            ->project($projectId);

        if ($like) {
            $path = substr($path, 0, -1);
            $mutation = $mutation->where('path', 'like', "$path%");
        } else {
            $mutation = $mutation->where('path', $path);
        }

        $mutation = $mutation->get();

        if (!count($mutation)) {
            return response()->json(['message' => 'Mutation not found.']);
        }

        try {
            $mutation->each->delete();
        } catch (Throwable $e) {
            return response()->json(['message' => 'Server error.'], 500);
        }

        return response()->json([], 201);
    }

    /**
     * @param $projectId
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDeleteMutations($projectId, Request $request): JsonResponse
    {
        $paths = $request->input('paths') ?? [];

        if (!count($paths)) {
            return response()->json(['message' => 'Cannot delete nothing.'], 429);
        }

        $mutations = Mutation::auth()
            ->project($projectId);

        $mutations = $mutations->whereIn('path', $paths);

        $mutations = $mutations->get();

        if (!count($mutations)) {
            return response()->json(['message' => 'Mutations not found.'], 404);
        }

        try {
            $mutations->each->delete();
        } catch (Throwable $e) {
            return response()->json(['message' => 'Server error.'], 500);
        }

        return response()->json([], 201);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMutationsLike(Request $request): JsonResponse
    {
        $path = $request->input('path');
        $projectId = $request->input('projectId');

        $mutations = MutationService::getMutationsLike($path, $projectId);

        return response()->json(['value' => $mutations]);
    }

    /**
     * @return JsonResponse
     */
    public function getPaginatedMutations(): JsonResponse
    {
        return response()->json(['mutations' => Mutation::orderBy('updated_at', 'desc')->paginate()]);
    }

    /**
     * @return JsonResponse
     */
    public function getMutationsForDebugging(): JsonResponse
    {
        $mutations = Mutation::orderBy('updated_at', 'desc')->paginate();

        return response()->json(['mutations' => $mutations]);
    }

    /**
     * @param $path
     * @return JsonResponse
     */
    public function deleteMutationDBG($path): JsonResponse
    {
        $like = Str::endsWith($path, '*');

        $mutation = Mutation::auth();

        if ($like) {
            $path = substr($path, 0, -1);
            $mutation = $mutation->where('path', 'like', "$path%");
        } else {
            $mutation = $mutation->where('path', $path);
        }

        $mutation = $mutation->first();

        if (!$mutation) {
            return response()->json(['message' => 'Mutation not found.'], 404);
        }

        try {
            $mutation->delete();
        } catch (Throwable $e) {
            return response()->json(['message' => 'Server error.'], 500);
        }

        return response()->json([], 201);
    }
}
