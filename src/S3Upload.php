<?php

namespace prointix\uploads;

use Aws\CommandPool;
use Aws\S3\S3Client;
use prointix\uploads\UploadInterface;
use yii\base\InvalidConfigException;
use yii\web\UploadedFile;

/**
 * Class S3Upload is the class for uploading files to the Amazon S3
 * @package prointix\uploads
 * @property string $baseDir
 * @property string $defaultBucket
 * @property string $defaultAcl
 * @property int|string|\DateTime $defaultPresignedExpiration
 * @property string $defaultBaseUrl
 * @property array $clientConfig
 * 
 * @author Rachhen
 * @version 0.0.1
 * 
 * @example
 * ```php
 * 'upload' => [
 *       'class' => 'prointix\uploads\S3Upload',
 *       'clientConfig' => [
 *          'region' => 'ap-southeast-1',
 *          'endpoint' => 'http://127.0.0.1:9000', // optional
 *          'credentials' => [
 *              'key' => 'xxxxxxxxxxxxx',
 *              'secret' => 'xxxxxxxxxxxxxxxxxxxxxx',
 *          ],
 *      ],
 *      'defaultBucket' => 'test',
 *      'defaultBaseUrl' => 'http://127.0.0.1:9000', // optional
 *  ],
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
class S3Upload extends BaseUpload implements UploadInterface
{
    /** @var string */
    public $defaultBucket = '';

    /** @var string */
    public $defaultAcl = 'public-read';

    /** @var string */
    public $defaultBaseUrl = '';

    /** @var array S3Client config */
    public $clientConfig = ['version' => 'latest'];

    /** @var S3Client */
    private $s3;

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->clientConfig['region'])) {
            throw new InvalidConfigException('Region is not set.');
        }

        if (empty($this->defaultBucket)) {
            throw new InvalidConfigException('Default bucket name is not set.');
        }
    }

    /**
     * Returns the URL of the file
     * @param string $key The uploaded file path
     * @return string
     */
    public function getUrl(string $key): string
    {
        if (empty($this->defaultBaseUrl)) {
            $client = $this->getClient();
            return $client->getObjectUrl($this->defaultBucket, $key);
        }

        return $this->defaultBaseUrl . '/' . $key;
    }

    /**
     * Uploads a file to S3
     * @param UploadedFile $file
     * @param string $key
     * @param array $args
     * @return \Aws\Result
     */
    public function store(UploadedFile $file, string $key, array $args = []): \Aws\Result
    {
        $client = $this->getClient();

        // Create a command to upload the object
        $command = $this->buildPutCommand($file, $key, $args);

        // Execute the command
        return $client->execute($command);
    }

    /**
     * Uploads multiple files to S3
     * @param UploadedFile[] $files The array of `UploadedFile`
     * @param string $folder The folder to store the files
     * @param array $args The arguments to be passed to the store method
     * @return \Aws\Result[]
     */
    public function stores(array $files, string $folder, array $args = []): array
    {
        $client = $this->getClient();

        /** @var \Aws\CommandInterface[] $commands */
        $commands = [];
        foreach ($files as $file) {
            $key = $this->getGenerateKey($file, $folder);
            $commands[] = $this->buildPutCommand($file, $key, $args);
        }

        return CommandPool::batch($client, $commands);
    }

    /**
     * Deletes a file from S3
     * @param string $key
     * @param array $args
     * @return \Aws\Result
     * @throws \Aws\Exception\AwsException
     */
    public function delete(string $key): \Aws\Result
    {
        $client = $this->getClient();

        // Create a command to delete the object
        $command = $this->buildDeleteCommand($key);

        // Execute the command
        return $client->execute($command);
    }

    /**
     * Deletes multiple files from S3
     * @param string[] $keys
     * @return \Aws\Result[] The array of deleted results
     */
    public function deletes(array $keys): array
    {
        $client = $this->getClient();

        /** @var \Aws\CommandInterface[] $commands */
        $commands = [];
        foreach ($keys as $key) {
            $commands[] = $this->buildDeleteCommand($key);
        }

        return CommandPool::batch($client, $commands);
    }

    /**
     * Use to check if the upload is S3 or not
     */
    public function isS3(): bool
    {
        return true;
    }

    /**
     * Initializes (if needed) and fetches the AWS S3 instance
     * @return \Aws\S3\S3Client
     */
    private function getClient(): S3Client
    {
        if (empty($this->s3) || !$this->s3 instanceof S3Client) {
            $this->s3 = new S3Client($this->clientConfig);
        }

        return $this->s3;
    }

    /**
     * build the put command for the file
     * @param UploadedFile $file The instance of `UploadedFile`
     * @param string $key The path of the file
     * @param array $args  The additional arguments
     * @return \Aws\CommandInterface
     */
    private function buildPutCommand(UploadedFile $file, string $key, array $args = []): \Aws\CommandInterface
    {
        $client = $this->getClient();
        return $client->getCommand('PutObject', array_merge([
            'Bucket' => $this->defaultBucket,
            'SourceFile' => $file->tempName,
            "ContentType" => $file->type,
            "ACL" => $this->defaultAcl,
            "Key" => $key,
        ], $args));
    }

    /**
     * build the delete command for the file
     * @param string $key The path of the file
     * @param array $args The additional arguments
     * @return \Aws\CommandInterface
     */
    private function buildDeleteCommand(string $key): \Aws\CommandInterface
    {
        $client = $this->getClient();
        return $client->getCommand('DeleteObject', [
            'Bucket' => $this->defaultBucket,
            'Key'    => $key
        ]);
    }
}
