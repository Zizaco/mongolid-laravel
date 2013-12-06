<?php

namespace Zizaco\MongolidLaravel;

/**
 * This class extends the Zizaco\Mongolid\Model, so, in order
 * to understand the ODM implementation make sure to check the
 * base class.
 *
 * The Zizaco\MongolidLaravel\MongoLid simply extends the original
 * and framework agnostic model of MongoLid and implements some
 * validation rules using Laravel validation components.
 *
 * Remember, this package is meant to be used with Laravel while
 * the "zizaco\mongolid" is meant to be used with other frameworks
 * or even without any.
 *
 * @license MIT
 * @author  Zizaco Zizuini <zizaco@gmail.com>
 */
abstract class MongoLid extends \Zizaco\Mongolid\Model implements \ArrayAccess
{
    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = null;

    /**
     * Error message bag
     *
     * @var Illuminate\Support\MessageBag
     */
    public $errors;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected static $dispatcher;

    /**
     * Public static mock
     *
     * @var Mockery\Mock
     */
    public static $mock;

    /**
     * List of attribute names which should be hashed on save. For
     * example: array('password');
     *
     * @var array
     */
    protected $hashedAttributes = array();

    /**
     * Save the model to the database if it's valid
     *
     * @param $force Force save even if the object is invalid
     * @return bool
     */
    public function save($force = false)
    {
        if ($this->isValid() || $force) {
            $this->hashAttributes();

            return parent::save();
        } else {
            return false;
        }
    }

    /**
     * Verify if the model is valid
     *
     * @return bool
     */
    public function isValid()
    {
        /**
         * Return true if there arent validation rules
         */
        if(! is_array(static::$rules) )
            return true;

        /**
         * Get the attributes and the rules to validate then
         */
        $attributes = $this->attributes;
        $rules = static::$rules;

        /**
         * Verify attributes that are hashed and that have not changed
         * those doesn't need to be validated.
         */
        foreach ($this->hashedAttributes as $hashedAttr) {
            if(isset($this->original[$hashedAttr]) && $this->$hashedAttr == $this->original[$hashedAttr]) {
                unset($rules[$hashedAttr]);
            }
        }

        /**
         * Creates validator with attributes and the rules of the object
         */
        $validator = \Validator::make( $attributes, $rules );

        /**
         * Validate and attach errors
         */
        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the contents of errors attribute
     *
     * @return Illuminate\Support\MessageBag Validation errors
     */
    public function errors()
    {
        if(! $this->errors) $this->errors = new \Illuminate\Support\MessageBag;

        return $this->errors;
    }

    /**
     * Sets the database and the cache component of the model
     * If you extend the __construct() method, please don't forget
     * to call parent::__construct()
     */
    public function __construct()
    {
        if (is_null($this->database)) {
            $this->database = \Config::get('database.connections.mongodb.default.database', null);
        }

        static::$cacheComponent = \App::make('cache');
    }

    /**
     * Returns a new instance of the current model
     * Overwrites the original newInstance method in order
     * to use the IoC container.
     *
     * @return  mixed An instance of the current model
     */
    public static function newInstance()
    {
        return app()->make(get_called_class());
    }

    /**
     * Hashes the attributes specified in the hashedAttributes
     * array.
     *
     * @return void
     */
    protected function hashAttributes()
    {
        foreach ($this->hashedAttributes as $attr) {
            /**
             * Hash attribute if changed
             */
            if(! isset($this->original[$attr]) || $this->$attr != $this->original[$attr] ) {
                $this->$attr = static::$app['hash']->make($this->$attr);
            }

            /**
             * Removes any confirmation field before saving it into the database
             */
            $confirmationField = $attr.'_confirmation';
            if($this->$confirmationField) {
                unset($this->$confirmationField);
            }
        }
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Events\Dispatcher  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(\Illuminate\Events\Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string $event
     * @param  bool   $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if ( ! isset(static::$dispatcher)) return true;

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "mongolid.{$event}: ".get_class($this);

        $method = $halt ? 'until' : 'fire';

        return static::$dispatcher->$method($event, $this);
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @param  dynamic
     * @return \Mockery\Expectation
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name == 'shouldReceive')
        {
            if (! static::$mock) {
                static::$mock = \Mockery::mock(get_called_class().'Mock');
            }

            return call_user_func_array(array(static::$mock, 'shouldReceive'), $arguments);
        }
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function first($id = array(), $fields = array())
    {
        if (static::$mock && static::$mock->mockery_getExpectationsFor('first'))
            return call_user_func_array(array(static::$mock, 'first'), func_get_args());
        else
            return parent::first($id, $fields);
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function find($id = array(), $fields = array(), $cachable = false)
    {
        if (static::$mock && static::$mock->mockery_getExpectationsFor('find'))
            return call_user_func_array(array(static::$mock, 'find'), func_get_args());
        else
            return parent::find($id, $fields, $cachable);
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function where($query = array(), $fields = array(), $cachable = false)
    {
        if (static::$mock && static::$mock->mockery_getExpectationsFor('where'))
            return call_user_func_array(array(static::$mock, 'where'), func_get_args());
        else
            return parent::where($query, $fields, $cachable);
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function group($fields = array(), $initial = array(), $reduce = '', $condition = array())
    {
        if (static::$mock && static::$mock->mockery_getExpectationsFor('group'))
            return call_user_func_array(array(static::$mock, 'group'), func_get_args());
        else
            return parent::group($fields, $initial, $reduce, $condition);
    }

    /**
     * Overwrites the "static" method in order to make it mockable
     *
     */
    public static function all( $fields = array() )
    {
        if (static::$mock && static::$mock->mockery_getExpectationsFor('all'))
            return call_user_func_array(array(static::$mock, 'all'), func_get_args());
        else
            return parent::all($fields);
    }

    /*
     * Check whether an offset exists
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /*
     * Get the value of an offset
     */
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /*
     * Set the value of an offset
     */
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    /*
     * Delete the value of an offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

}
