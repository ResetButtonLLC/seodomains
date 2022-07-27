<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StorageHelper
{
    public static function deleteFilesWithExtension(string $path, string $extension) :void
    {
        $files = File::allFiles($path);
        $zipfiles = array_filter($files, fn($f) => ends_with($f,'.'.$extension));
        File::delete($zipfiles);
    }

    public static function getFirstFileWithExtension(string $path, string $extension) : string
    {
        $files = File::allFiles($path);
        $zipfiles = array_filter($files, fn($f) => ends_with($f,'.'.$extension));
        return head($zipfiles);
    }

    public static function extractZipToCsv(string $zipfile, string $csvfile) : void
    {
        $zip = new \ZipArchive;
        $zip->open($zipfile);
        $zip->extractTo(dirname($zipfile).'/tmp');
        $zip->close();

        $csvFile = self::getFirstFileWithExtension(dirname($zipfile).'/tmp','csv');

        File::move($csvFile,dirname($zipfile).'/'.$csvfile.'.csv');
        File::deleteDirectory(dirname($zipfile).'/tmp');
    }


}