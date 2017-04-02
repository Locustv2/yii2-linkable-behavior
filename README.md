# Linkable Behavior for Yii2 Components

This extension help creating urls easier in yii2. This behavior provides support for components that have a page to display its contents. The page can be an action in a Module or simply in a Controller. It will be easier to get links related to this record without having to write Url Route over and over again.

[![Latest Stable Version](https://poser.pugx.org/locustv2/yii2-linkable-behavior/v/stable)](https://packagist.org/packages/locustv2/yii2-linkable-behavior)
[![Total Downloads](https://poser.pugx.org/locustv2/yii2-linkable-behavior/downloads)](https://packagist.org/packages/locustv2/yii2-linkable-behavior)
[![Latest Unstable Version](https://poser.pugx.org/locustv2/yii2-linkable-behavior/v/unstable)](https://packagist.org/packages/locustv2/yii2-linkable-behavior)
[![License](https://poser.pugx.org/locustv2/yii2-linkable-behavior/license)](https://packagist.org/packages/locustv2/yii2-linkable-behavior)


## Installation

The preferred way to install the library is through [composer](https://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist locustv2/yii2-linkable-behavior
```

or add
```json
{
    "require": {
        "locustv2/yii2-linkable-behavior": "~1.0.0"
    }
}
```
to your `composer.json` file.

## Usage

Add the behavior to your ActiveRecord that can he hotlinked:

```php
namespace app\models;

use yii\db\ActiveRecord;
use locustv2\behaviors\LinkableBehavior;

class User extends ActiveRecord
{
  //...

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => LinkableBehavior::className(),
                'route' => '/user',
                'defaultAction' => 'view',
                'hotlinkTextAttr' => 'username',
                'defaultParams' => function ($record) {
                    return [
                        'id' => $record->id,
                    ];
                },
            ]
        ]);
    }
}
```

```php
namespace app\models;

use yii\db\ActiveRecord;
use locustv2\behaviors\LinkableBehavior;

class Photo extends ActiveRecord
{
  //...

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => LinkableBehavior::className(),
                'route' => '/photo',
                'defaultAction' => 'view',
                'linkableParams' => function ($record) {
                    return [
                        'photoid' => $record->id,
                    ];
                },
                'useAbsoluteUrl' => true,
                'defaultParams' => function ($record) {
                    return [
                        'id' => $record->id,
                        'slug' => $record->slug
                    ];
                },
            ]
        ]);
    }
}
```

With that code in place, you can now use 4 available methods in your `User` and `Photo` ActiveRecord:
 - `getUrlRoute($action = null, array $params = [])`
 - `getUrlRouteTo(Component $component, $action = null)`
 - `getHotlink($action = null, array $params = [], array $options = [])`
 - `getHotlinkTo(Component $component, $action = null, array $params = [], array $options = [])`


### Examples (assuming that you use [pretty urls](http://www.yiiframework.com/doc-2.0/guide-runtime-routing.html#using-pretty-urls))

#### `getUrlRoute($action = null, array $params = [])`
```php
use yii\helpers\Url;
use app\models\User;

$user = User::findOne(['id' => 123]);

// /user/view?id=123
echo Url::to($user->urlRoute);

// /user/update?id=123
echo Url::to($user->getUrlRoute('update'));

// http://www.yoursite.com/user/profile?id=123&ref=facebook
echo Url::to($user->getUrlRoute('profile', ['ref' => 'facebook']), true);
```

#### `getUrlRouteTo(Component $component, $action = null)`
```php
use yii\helpers\Url;
use app\models\User;

$user = User::findOne(['id' => 123]);
$photo = $user->getPhotos()->one();

// /user/photo/view?id=123&photoid=456&slug=my-first-photo
echo Url::to($user->getUrlRouteTo($photo));

// /photo/user/view?id=456&slug=my-first-photo&uid=123
echo Url::to($photo->getUrlRouteTo($user));

// /user/photo/update?id=123&photoid=456&slug=my-first-photo
echo Url::to($user->getUrlRouteTo($photo, 'update'));
```

#### `getHotlink($action = null, array $params = [], array $options = [])`
```php
use yii\helpers\Url;
use app\models\User;

$user = User::findOne(['id' => 123]);

// <a href="/user/view?id=123">Locustv2</a>
echo $user->hotLink;

// <a href="/user/update?id=123">Locustv2</a>
echo $user->getHotlink('update');

// <a class="text-bold" href="/user/profile?id=123&ref=facebook">Locustv2</a>
echo $user->getHotlink('profile', ['ref' => 'facebook'], ['class' => 'text-bold']);
```
If you want to use absolute urls, you should set `LinkableBehavior::$useAbsoluteUrl` to `true`.
If you want to disable hotlinks, you should set `LinkableBehavior::$disableHotlink` to `true`. `<span/>` will be used instead of `<a/>`

#### `getHotlinkTo(Component $component, $action = null, array $params = [], array $options = [])`
```php
use yii\helpers\Url;
use app\models\User;

$user = User::findOne(['id' => 123]);
$photo = $user->getPhotos()->one();

// <a href="http://www.yoursite.com/user/photo/view?id=123&photoid=456&slug=my-first-photo">Locustv2</a>
echo $user->getHotlinkTo($photo);

// <a href="http://www.yoursite.com/photo/user/view?id=456&slug=my-first-photo&uid=123">http://www.yoursite.com/photo/user/view?id=456&slug=my-first-photo&uid=123</a>
echo $photo->getHotlinkTo($user);

// <a class="font-bold" href="http://www.yoursite.com/user/photo/update?id=123&photoid=456&slug=my-first-photo&ref=homepage">Locustv2</a>
echo Url::to($user->getHotlinkTo($photo, 'update', ['ref' => homepage], ['class' => 'font-bold']));

```


## To do
 - Add unit tests


## Contributing
Feel free to send pull requests.


## License
For license information check the [LICENSE](LICENSE.md)-file.
