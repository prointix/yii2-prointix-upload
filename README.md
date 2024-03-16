# Prointix upload

This extension use for PROINTIX project

## Installation

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist prointix/yii2-prointix-upload "*"
```

or add

```
"prointix/yii2-prointix-upload": "*"
```

to the require section of your `composer.json` file.

Add the component to `config/web.php`. You can switch between S3 and local. The usage is the same.

```php
'components' => [
    // ...
    'upload' => [
        'class' => 'prointix\uploads\S3Upload',
        'clientConfig' => [
            'region' => 'ap-southeast-1',
            'endpoint' => 'http://127.0.0.1:9000', // optional
            'credentials' => [
                'key' => 'xxxxxxxxxxxxx',
                'secret' => 'xxxxxxxxxxxxxxxxxxxxxx',
            ],
        ],
       'defaultBucket' => 'test',
       'defaultBaseUrl' => 'http://127.0.0.1:9000', // optional
    ],

    // or with local server
    'upload' => [
        'class' => 'prointix\uploads\LocalUpload',
        'baseUploadDir' => '/uploads', // optional. default folder: `/uploads`
    ],

    // ...
],
```

## Usage

Once the extension is installed, simply use it in your code by :

#### Basic usage

```php
/** @var \prointix\uploads\UploadInterface */
$upload = Yii::$app->upload;

// Store a file
$file = UploadedFile::getInstance($model, 'imageFile');

$key = $upload->getGenerateKey($file, "test/");
// It don't required to use `getGenerateKey` function. It just helper.
// You can pass you own path. Ex: test/folders/test.png
$result = $upload->store($file, $key);

// Store multiple files
// The key will be auto generate. but you can custom folder.
$files = UploadedFile::getInstances($model, 'imageFiles');
$result = $upload->stores($files, "/test");

// Get the URL of the file
$url = $upload->getUrl($key);

// Delete a file
$result = $upload->delete($key);

// Delete multiple files
// Pass array of uploaded keys. Ex: ["folders/others/image-1.png", "folders/others/image-2.png"]
$result = $upload->deletes([$key1, $key2]);
```

#### Using Traits

Attach the Trait to the model with some media attribute that will be saved in S3 or Local:

In your model:

```php
class YourModel extends \yii\db\ActiveRecord
{
    use \prointix\uploads\traits\MediaTrait;

    // ...
}

```

In your controller:

```php
$model = new YourModel();

if ($model->load($this->request->post())){
    $image = \yii\web\UploadedFile::getInstance($model, 'my_file_attribute');

    // Save image as my_image.png on S3 or Local at `/folders/others` path
    // $model->image will hold "folders/others/{filename}-{uniqid()}.{ext}" after this call finish with success
    $model->saveUploadedFile($image, 'image', '/folder/others');

    // Get the URL of the file
    $model->getFileUrl('image');

    // Remove the file with named saved on the image attribute
    // Continuing the example, here "folders/images/my_image.png" will be deleted from S3 or Local base on your config
    $model->removeFile('image');
}
```
