<?php

namespace prointix\uploads;

use yii\base\Component;
use yii\helpers\Inflector;
use yii\web\UploadedFile;

/**
 * Class BaseUpload is the base class for all upload classes
 * @package prointix\uploads
 * @property string $baseDir
 * 
 * @author Rachhen rachhen.it@outlook.com
 * @version 0.0.1
 */
abstract class BaseUpload extends Component
{
    /**
     * Generates a key for the file
     * @param UploadedFile $file The instance of `UploadedFile`
     * @param string $folder The folders to be added to the key. ex: `/folders/others`
     * @return string
     */
    public function getGenerateKey(UploadedFile $file, string $folder = '/'): string
    {
        $id = uniqid();
        $info = pathinfo($file->name);
        $ext = strtolower($info['extension']);
        $filename = Inflector::slug($info['filename']);

        $dirPrefix = $folder != '/' ? DIRECTORY_SEPARATOR : '';
        $newFileName =  $dirPrefix . "{$filename}-{$id}.{$ext}";

        return  $this->getAbsolutePath($folder) . $newFileName;
    }

    /**
     * Returns the absolute path
     * @param string $path The path to be converted
     * @param bool $isDirPrefix The prefix to be added to the path
     * @return string
     */
    public function getAbsolutePath(string $path, $isDirPrefix = false): string
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        if ($isDirPrefix) {
            return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $absolutes);
        }

        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}
