<?php

namespace App\Helpers;

use Throwable;
use ZanySoft\Zip\Zip;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ArchiveHelpers
{
    /**
     * @param $filePath
     * @param $zipFile
     * @return false|string|null
     */
    public static function getFileContents($filePath, $zipFile)
    {
        try {
            $zip = Zip::open(storage_path('app/' . $zipFile));
        } catch (Throwable $e) {
            return null;
        }

        try {
            if (!$zip->has($filePath)) {
                return null;
            }
        } catch (Throwable $e) {
            return null;
        }

        $tmpPath = storage_path('app/tmp/') . Str::random();

        try {
            $zip->extract($tmpPath, [$filePath]);
        } catch (Throwable $e) {
            return null;
        }

        if (!File::exists($tmpPath . "/$filePath")) {
            return null;
        }

        $fileContents = file_get_contents($tmpPath . "/$filePath");

        File::deleteDirectory($tmpPath);

        return $fileContents;
    }
}
