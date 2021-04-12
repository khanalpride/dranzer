<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetProjectSettingRequest;
use App\Http\Requests\GetSettingRequest;
use App\Http\Requests\SaveProjectSettingRequest;
use App\Http\Requests\SaveSettingRequest;
use App\Services\ProjectSettingsService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

/**
 * Class SettingsController
 * @package App\Http\Controllers
 */
class SettingsController extends Controller
{
    /**
     * @param SaveSettingRequest $request
     * @return JsonResponse
     */
    public function saveSetting(SaveSettingRequest $request): JsonResponse
    {
        $name = $request->input('name');
        $value = $request->input('value');

        $saved = SettingsService::saveSetting($name, $value);

        if ($saved) {
            return response()->json([]);
        }

        return response()->json(['message' => 'There was a problem storing the requested setting.'], 500);
    }

    /**
     * @param GetSettingRequest $request
     * @return JsonResponse
     */
    public function getSetting(GetSettingRequest $request): JsonResponse
    {
        $name = $request->input('name');

        return response()->json(['value' => SettingsService::getSetting($name)]);
    }

    /**
     * @param SaveProjectSettingRequest $request
     * @return JsonResponse
     */
    public function saveProjectSetting(SaveProjectSettingRequest $request): JsonResponse
    {
        $name = $request->input('name');
        $value = $request->input('value');
        $projectId = $request->input('projectId');

        $saved = ProjectSettingsService::saveSetting($name, $value, $projectId);

        if ($saved) {
            return response()->json([]);
        }

        return response()->json(['message' => 'There was a problem storing the requested setting.'], 500);
    }


    /**
     * @param GetProjectSettingRequest $request
     * @return JsonResponse
     */
    public function getProjectSetting(GetProjectSettingRequest $request): JsonResponse
    {
        $name = $request->input('name');
        $projectId = $request->input('projectId');

        return response()->json(['value' => ProjectSettingsService::getSetting($name, $projectId)]);
    }
}
