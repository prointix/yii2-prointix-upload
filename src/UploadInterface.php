<?php

namespace prointix\uploads;

use yii\web\UploadedFile;

/**
 * UploadInterface is the interface for all upload classes to implement.
 * @package prointix\uploads\interfaces
 * 
 */
interface UploadInterface
{
    /**
     * Returns the URL of the file
     * @param string $key The uploaded file path
     * @return string
     */
    public function getUrl(string $key): string;

    /**
     * Uploads a file
     * @param UploadedFile $file The instance of `UploadedFile`
     * @param string $key The path to store the file
     * @param array $args The arguments to be passed to the store method. if you use local upload not need to pass any arguments
     * @return \Aws\Result|string|null It returns the uploaded file path or null if the file is not uploaded
     */
    public function store(UploadedFile $file, string $key, array $args = []): \Aws\Result|string|null;

    /**
     * Uploads multiple files
     * @param UploadedFile[] $files The array of `UploadedFile`
     * @param string $folder The path to store the files
     * @param array $args The arguments to be passed to the store method. if you use local upload not need to pass any arguments
     * @return \Aws\Result[]|string[]
     */
    public function stores(array $files, string $folder, array $args = []): array;

    /**
     * Deletes a file
     * @param string $key The path of the file to be deleted
     * @return \Aws\Result|string|null
     */
    public function delete(string $key): \Aws\Result|string|null;

    /**
     * Deletes multiple files
     * @param array $keys The array of paths to be deleted
     * @return \Aws\Result[]|string[] The array of deleted keys
     */
    public function deletes(array $keys): array;

    /**
     * Generates a key for the file
     * @param UploadedFile $file The instance of `UploadedFile`
     * @param string $folder The folders to be added to the key. ex: `/folders/others`
     * @return string
     */
    public function getGenerateKey(UploadedFile $file, string $folder = '/'): string;

    /**
     * Use to check the upload is S3 or not
     * @return bool
     */
    public function isS3(): bool;
}
