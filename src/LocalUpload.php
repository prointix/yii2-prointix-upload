<?php

namespace prointix\uploads;

use prointix\uploads\UploadInterface;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * Class LocalUpload is the class for uploading files to the local server
 * @package prointix\uploads
 * @property string $baseDir
 * @author Rachhen
 * @version 0.0.1
 * 
 * @example
 * ```php
 * 'upload' => [
 *     'class' => 'prointix\uploads\LocalUpload',
 *     'baseUploadDir' => '/uploads', // optional
 * ],
 * 
 * # Using the component
 * // @var \prointix\uploads\UploadInterface 
 * $upload = Yii::$app->upload;
 * 
 * # Store a file
 * $file = UploadedFile::getInstance($model, 'imageFile');
 * $key = $upload->getGenerateKey($file, "test/");
 * $result = $upload->store($file, $key);
 * 
 * # Store multiple files
 * $files = UploadedFile::getInstances($model, 'imageFiles');
 * $result = $upload->stores($files, "/test");
 * 
 * # Get the URL of the file
 * $url = $upload->getUrl($key);
 * 
 * # Delete a file
 * $result = $upload->delete($key);
 * 
 * # Delete multiple files
 * $result = $upload->deletes([$key1, $key2]);
 * ```
 */
class LocalUpload extends BaseUpload implements UploadInterface
{
    /** @var string $baseDir the base directory that your want to uploads in */
    public $baseUploadDir = "/uploads";

    /** 
     * @var string $webroot the webroot directory 
     * If you want to change the webroot directory you can set it in the configuration file
     * 'upload' => [
     *       ///...
     *      'webroot' => '@webroot', // optional
     * ],
     * */
    public string $webroot = '@webroot';

    public function init()
    {
        parent::init();
        $this->webroot = \Yii::getAlias($this->webroot);
        $this->baseUploadDir = $this->getAbsolutePath($this->baseUploadDir, true);
    }

    /**
     * Returns the URL of the file
     * @param string $key The uploaded file path
     * @return string
     */
    public function getUrl(string $key): string
    {
        return $this->fromBaseUploadDir($key);
    }

    /**
     * Uploads a file
     * @param UploadedFile $file The instance of `UploadedFile`
     * @param string $key The path to store the file
     * @return string|null It returns the uploaded file path or null if the file is not uploaded
     */
    public function store(UploadedFile $file, string $key, $args = []): string|null
    {
        if ($file->error !== UPLOAD_ERR_OK) {
            return null;
        }

        // dd($this->getPath($key));

        if ($file->saveAs($this->getPath($key,), false)) {
            return $key;
        }

        return null;
    }

    /**
     * Uploads multiple files 
     * @param UploadedFile[] $files The array of `UploadedFile`
     * @param string $folder The folder to store the files. ex: `/folder/others`
     * @param array $args The arguments to be passed to the store method. if you use local upload not need to pass any arguments
     * @return string[]
     */
    public function stores(array $files, string $folder, array $args = []): array
    {
        $uploadedFiles = [];
        foreach ($files as $file) {
            $key = $this->getGenerateKey($file, $folder);
            if ($uploadedKey = $this->store($file, $key)) {
                $uploadedFiles[] = $uploadedKey;
            }
        }

        return $uploadedFiles;
    }

    /**
     * Deletes a file
     * @param string $key The path of the file to be deleted
     * @return string|null
     */
    public function delete(string $key): string|null
    {
        $file = $this->fromDir($key);
        if (file_exists($file) && unlink($file)) {
            return $key;
        }

        return null;
    }

    /**
     * Deletes multiple files
     * @param array $keys
     * @param array $args
     * @return string[] The array of deleted keys
     */
    public function deletes(array $keys): array
    {
        $deletedPaths = [];
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $deletedPaths[] = $key;
            }
        }

        return $deletedPaths;
    }

    /**
     * Use to check if the upload is S3 or not
     */
    public function isS3(): bool
    {
        return false;
    }

    /**
     * get the full path from the base upload directory
     * @param string $key
     * @return string
     */
    private function fromBaseUploadDir(string $key)
    {
        return $this->baseUploadDir . $this->getAbsolutePath($key, true);
    }

    /**
     * get the full path
     * @param string $key
     * @return string
     */
    private function getPath(string $key)
    {
        $fullPath = $this->fromDir($key);
        if (!file_exists($fullPath)) {
            FileHelper::createDirectory(dirname($fullPath));
        }

        return $fullPath;
    }

    private function fromDir(string $key)
    {
        return $this->getDir() . $this->getAbsolutePath($key, true);
    }

    /**
     * get the base directory
     * @return string
     */
    private function getDir()
    {
        return $this->webroot . $this->baseUploadDir;
    }
}
