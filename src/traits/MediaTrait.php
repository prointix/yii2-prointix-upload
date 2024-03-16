<?php

namespace prointix\uploads\traits;

use yii\web\UploadedFile;

trait MediaTrait
{
    /**
     * @return \prointix\uploads\UploadInterface
     */
    public function getUploadComponent()
    {
        return \Yii::$app->upload;
    }

    /**
     * Save the uploaded file to the server
     * 
     * @param \yii\web\UploadedFile $file The uploaded file
     * @param string $attribute The attribute to save the file path
     * @param string $folder The folder to save the file. ex: `/folders/others`
     * 
     * @return string|null The file path or null if the file is not uploaded
     */
    public function saveUploadedFile(UploadedFile $file, string $attribute, string $folder = '/')
    {
        if ($this->hasErrors() && !$file instanceof UploadedFile) {
            return null;
        }

        $upload = $this->getUploadComponent();
        $key = $upload->getGenerateKey($file, $folder);
        $result = $upload->store($file, $key);

        if ($result) {
            // Validate successful upload to S3
            if ($upload->isS3()) {
                if ($this->isSuccessResponseStatus($result)) {
                    $this->{$attribute} = $key;
                    return $key;
                }

                $this->addError($attribute, 'Failed to upload file to S3');
                return null;
            }

            $this->{$attribute} = $key;
            return $key;
        }

        $this->addError($attribute, 'Failed to upload file');
        return null;
    }

    /**
     * Remove the file from the server
     * 
     * @param string $attribute The attribute to remove the file path
     * 
     * @return bool TRUE on successful file removal
     */
    public function removeFile(string $attribute)
    {
        if (empty($this->{$attribute})) {
            // No file to remove
            return true;
        }

        $upload = $this->getUploadComponent();
        $result = $upload->delete($this->{$attribute});

        if ($result) {
            // Validate successful delete from S3
            if ($upload->isS3()) {
                if ($this->isSuccessResponseStatus($result)) {
                    return true;
                }

                // Failed to delete file from S3
                return false;
            }

            return true;
        }

        // Failed to delete file
        return false;
    }

    /**
     * Get the file URL
     * 
     * @param string $attribute The attribute to get the file path
     * 
     * @return string|null The file URL or null if the file path is empty
     */
    public function getFileUrl(string $attribute)
    {
        if (empty($this->{$attribute})) {
            return null;
        }

        $upload = $this->getUploadComponent();
        return $upload->getUrl($this->{$attribute});
    }

    /**
     * Check for valid status code from the AWS S3 response.
     * Success responses will be considered status codes between 200 and 204.
     * Override function for custom validations.
     * 
     * @param \Aws\ResultInterface $response AWS S3 response containing the status code
     * 
     * @return bool TRUE on success status.
     */
    protected function isSuccessResponseStatus($response)
    {
        return !empty($response->get('@metadata')['statusCode']) &&
            $response->get('@metadata')['statusCode'] >= 200 &&
            $response->get('@metadata')['statusCode'] <= 204;
    }
}
