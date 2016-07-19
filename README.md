[![Build Status](https://travis-ci.org/leroy-merlin-br/mongolid-laravel.svg?branch=master)](https://travis-ci.org/leroy-merlin-br/mongolid-laravel)
[![Latest Stable Version](https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/v/stable.png)](https://packagist.org/packages/leroy-merlin-br/mongolid-laravel)
[![Monthly Downloads](https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/d/monthly.png)](https://packagist.org/packages/leroy-merlin-br/mongolid-laravel)
[![Latest Unstable Version](https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/v/unstable.png)](https://packagist.org/packages/leroy-merlin-br/mongolid-laravel)
[![License](https://poser.pugx.org/leroy-merlin-br/mongolid-laravel/license.png)](https://packagist.org/packages/leroy-merlin-br/mongolid-laravel)

![MongoLid](https://dl.dropboxusercontent.com/u/12506137/libs_bundles/mongolid_banner.png)

# MongoLid (Laravel Package)

- [Introduction](#introduction)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Authentication](#authentication)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Additional Information](#additional_information)

## Introduction

MongoLid ODM (Object Document Mapper) provides a beautiful, simple implementation for working with MongoDB. Each database collection can have a corresponding "Model" which is used to interact with that collection.

> Note: The ODM implementation is within the [(non laravel) mongolid repository](https://github.com/leroy-merlin-br/mongolid).

## Installation

 - For Laravel 5.1

```shell
composer require "leroy-merlin-br/mongolid-laravel=~2.0"
```

In your `config/app.php` add `'MongolidLaravel\MongolidServiceProvider'` to the end of the `$providers` array

```php
'providers' => [
    Illuminate\Translation\TranslationServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,
    ...
    MongolidLaravel\MongolidServiceProvider::class,
],
```

(**Optional**) At the end of `config/app.php` add `'MongoLid'    => 'MongolidLaravel\MongoLidModel'` to the `$aliases` array

```php
'aliases' => [
    'App'         => Illuminate\Support\Facades\App::class,
    'Artisan'     => Illuminate\Support\Facades\Artisan::class,
    ...
    'MongoLid'    => MongolidLaravel\MongoLidModel::class,
],
```

And least, be sure to configure a database connection in `config/database.php`:

Paste the settings bellow at the end of your `config/database.php`, before the last `);`:

**Notice:** It must be **outside** of `connections` array.
 
```php
/*
|--------------------------------------------------------------------------
| MongoDB Databases
|--------------------------------------------------------------------------
|
*/
'mongodb' => [
    'default' => [
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT_NUMBER', 27017),
        'database' => env('DB_DATABASE', 'my_database'),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
    ],
],
```

> **Note:** If you don't specify the key above in your `config/database.php`.
The MongoLid will automatically try to connect to 127.0.0.1:27017 and use a database named 'mongolid'.

You may optionally provide a `connectionString` key to set a fully-assembled connection string (useful for configuring fun things like read preference, replica sets, etc.) this will override all other connection options.

## Basic Usage

To get started, create an MongoLid model. Models typically live in the `app/models` directory, but you are free to place them anywhere that can be auto-loaded according to your `composer.json` file.

**Defining a MongoLid Model**

```php
<?php
namespace App;

use MongolidLaravel\MongolidModel;

class User extends MongolidModel
{
    
}
```

In a nutshell, that's it!

### For further reading about models, CRUD operations, relationships and more, check the [Mongolid Documentation](http://leroy-merlin-br.github.io/mongolid/).

## Authentication

MongoLid Laravel comes with a Laravel auth driver.
In order to use it, simply change the `'driver'` value in your `config/auth.php` to `mongoLid`
and make sure that the class specified in `model` is a MongoLid model that implements the `Authenticatable` contract:

```php

...

'driver' => 'mongoLid',

...

'model' => App\User::class,

...

```

The `User` model should implement the `Authenticatable` contract:
```php
<?php
namespace App;

use Illuminate\Contracts\Auth\Authenticatable;
use MongolidLaravel\MongolidModel;

class User extends MongolidModel implements Authenticatable
{
    /**
     * The database collection used by the model.
     *
     * @var string
     */
    protected $collection = 'users';

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->_id;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }
    

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
```

Now, to log a user into your application, you may use the `auth()->attempt()` method.
You can use [any method regarding authentication](https://laravel.com/docs/5.1/authentication#included-authenticating).

## Troubleshooting

**"PHP Fatal error: Class 'MongoDB\Client' not found in ..."**

The `MongoDB\Client` class is contained in the [MongoDB driver](https://pecl.php.net/package/mongodb) for PHP.
[Here is an installation guide](http://php.net/manual/en/mongodb.setup.php).
The driver is a PHP extension written in C and maintained by [MongoDB](https://mongodb.com).
MongoLid and most other MongoDB PHP libraries utilize it in order to be fast and reliable.

**"Class 'MongoDB\Client' not found in ..." in CLI persists even with MongoDB driver installed.**

Make sure that the **php.ini** file used in the CLI environment includes the MongoDB extension. In some systems, the default PHP installation uses different **.ini** files for the web and CLI environments.

Run `php --ini` in a terminal to check the **.ini** that is being used.

To check if PHP in the CLI environment is importing the driver properly run `php -i | grep mongo` in your terminal. You should get output similar to:

```shell
$ php -i | grep mongo
mongodb
mongodb support => enabled
...
```

**"This package requires php >=7.0 but your PHP version (X.X.X) does not satisfy that requirement."**

The new (and improved) version 2.0 of Mongolid Laravel requires php7. If you are looking for the old PHP 5.x version, or other Laravel versions, head to the [v0.8 branch](https://github.com/leroy-merlin-br/mongolid-laravel/tree/v0.8-dev).

## License

MongoLid & MongoLid Laravel are free software distributed under the terms of the [MIT license](http://opensource.org/licenses/MIT)

## Additional information

Any questions, feel free to contact us.

Any issues, please [report here](https://github.com/leroy-merlin-br/mongolid-laravel/issues)
