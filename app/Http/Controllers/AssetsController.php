<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Asset;
use ZanySoft\Zip\Zip;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * Class AssetsController
 * @package App\Http\Controllers
 */
class AssetsController extends Controller
{
    /**
     * @param $filename
     * @param $originalFilename
     * @param $key
     * @param $module
     * @param $projectId
     * @return void
     */
    private function createNewAsset($filename, $originalFilename, $key, $module, $projectId): void
    {
        Asset::create([
            'filename'          => $filename,
            'original_filename' => $originalFilename,
            'key'               => $key,
            'module'            => $module,
            'projectId'         => $projectId,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAssetInfo(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $module = $request->input('module');
        $projectId = $request->input('projectId');

        if (!$key || !$module || !$projectId) {
            return response()->json(['message' => 'Missing required data.'], 429);
        }

        $asset = Asset::select(['original_filename'])->project($projectId)->where('key', (string) $key)->where('module', $module)->first();

        if (!$asset) {
            return response()->json(['message' => 'Asset not found.'], 404);
        }

        return response()->json(['asset' => $asset]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getFileContentsInsideZipArchive(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $module = $request->input('module');
        $projectId = $request->input('projectId');

        $filePath = $request->input('filePath');

        if (!$key || !$module || !$projectId || !$filePath) {
            return response()->json(['message' => 'Missing required data.'], 429);
        }

        $asset = Asset::project($projectId)->where('key', (string) $key)->where('module', $module)->first();

        if (!$asset) {
            return response()->json(['message' => 'Asset not found.'], 404);
        }

        try {
            $zip = Zip::open(storage_path('app/' . $asset->filename));
        } catch (Exception $e) {
            return response()->json(['message' => 'There was a problem reading the contents of the request file.'], 500);
        }

        try {
            if (!$zip->has($filePath)) {
                return response()->json(['message' => 'The requested file was not found in the zip archive.'], 404);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'There was a problem reading the contents of the request file.'], 500);
        }

        $tmpPath = storage_path('app/tmp/') . Str::random();

        try {
            $zip->extract($tmpPath, [$filePath]);
        } catch (Exception $e) {
            return response()->json(['message' => 'There was a problem reading the contents of the request file.'], 500);
        }

        if (File::exists($tmpPath . "/$filePath")) {
            $contents = file_get_contents($tmpPath . "/$filePath");
            File::deleteDirectory($tmpPath);
            return response()->json(['contents' => $contents]);
        }

        return response()->json(['message' => 'There was a problem reading the contents of the request file.'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadAsset(Request $request): JsonResponse
    {
        $file = $request->file('file');

        if (!$file || !$file instanceof UploadedFile) {
            return response()->json(['message' => 'Not a valid asset.'], 429);
        }

        $key = $request->input('key');
        $module = $request->input('module');
        $projectId = $request->input('projectId');
        $mode = $request->input('mode') ?? 'add';

        if (!$key || !$module || !$projectId) {
            return response()->json(['message' => 'Missing required data.'], 429);
        }

        $filename = $file->storePublicly('assets');

        $originalFilename = $file->getClientOriginalName();

        if ($mode !== 'add') {
            $asset = Asset::project($projectId)->where('key', (string) $key)->where('module', $module)->first();
            if ($asset) {
                $assetPath = storage_path('app/' . $asset->filename);
                File::delete($assetPath);

                $asset->update([
                    'filename'          => $filename,
                    'original_filename' => $originalFilename,
                ]);
            } else {
                $this->createNewAsset($filename, $originalFilename, $key, $module, $projectId);
            }
        } else {
            $this->createNewAsset($filename, $originalFilename, $key, $module, $projectId);
        }

        return response()->json([], 204);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAsset(Request $request): JsonResponse
    {
        $key = $request->input('key');
        $module = $request->input('module');
        $projectId = $request->input('projectId');

        if (!$key || !$module || !$projectId) {
            return response()->json(['message' => 'Missing required data.'], 429);
        }

        $asset = Asset::project($projectId)->where('key', (string) $key)->where('module', $module)->first();

        if (!$asset) {
            return response()->json(['message' => 'Asset not found.'], 404);
        }

        try {
            $assetPath = storage_path('app/' . $asset->filename);

            if (File::exists($assetPath)) {
                File::delete($assetPath);
            }

            $asset->delete();
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the requested asset.'], 500);
        }

        return response()->json([]);
    }
}
