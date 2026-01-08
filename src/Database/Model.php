<?php

namespace Velocix\Database;

abstract class Model
{
    protected static $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    protected $incrementing = true;
    protected $attributes = [];
    protected $original = [];
    protected $fillable = [];
    protected $hidden = [];
    
    public static function setConnection(Connection $connection)
    {
        static::$connection = $connection;
    }

    public function __construct($attributes = [])
    {
        $this->bootIfNotBooted();
        $this->fill($attributes);
    }

    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            $this->bootTraits();
        }
    }

    protected function bootTraits()
    {
        $class = static::class;
        foreach (class_uses_recursive($class) as $trait) {
            $method = 'boot' . class_basename($trait);
            if (method_exists($class, $method)) {
                forward_static_call([$class, $method]);
            }
        }
    }

    protected static $booted = [];

    protected function fill($attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    protected function isFillable($key)
    {
        return empty($this->fillable) || in_array($key, $this->fillable);
    }

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function getKeyType()
    {
        return $this->keyType;
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }

    protected function getTable()
    {
        if ($this->table) {
            return $this->table;
        }
        
        $class = (new \ReflectionClass($this))->getShortName();
        return strtolower($class) . 's';
    }

    protected function newQuery()
    {
        return (new QueryBuilder(static::$connection))->table($this->getTable());
    }

    public static function query()
    {
        return (new static)->newQuery();
    }

    public static function all()
    {
        return static::query()->get();
    }

    public static function find($id)
    {
        $instance = new static;
        $data = static::query()->where($instance->getKeyName(), $id)->first();
        
        if ($data) {
            $model = new static;
            $model->attributes = $data;
            $model->original = $data;
            return $model;
        }
        return null;
    }

    public static function where($column, $operator, $value = null)
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function create($attributes)
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    public function save()
    {
        // Trigger creating event
        if (!isset($this->attributes[$this->primaryKey]) || empty($this->attributes[$this->primaryKey])) {
            static::creating($this);
        }

        if (isset($this->attributes[$this->primaryKey]) && !empty($this->attributes[$this->primaryKey])) {
            // Update existing record
            $id = $this->attributes[$this->primaryKey];
            $changes = array_diff_assoc($this->attributes, $this->original);
            
            if (!empty($changes)) {
                static::query()
                    ->where($this->primaryKey, $id)
                    ->update($changes);
                $this->original = $this->attributes;
            }
        } else {
            // Insert new record
            $data = $this->attributes;
            
            if ($this->incrementing) {
                $id = $this->newQuery()->insert($data);
                $this->attributes[$this->primaryKey] = $id;
            } else {
                // For UUID/ULID, ID is already set by trait
                $this->newQuery()->insert($data);
            }
            
            $this->original = $this->attributes;
        }
        
        return $this;
    }

    public function delete()
    {
        if (isset($this->attributes[$this->primaryKey])) {
            return static::query()
                ->where($this->primaryKey, $this->attributes[$this->primaryKey])
                ->delete();
        }
        return false;
    }

    protected static function creating($model)
    {
        // Hook for traits
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function toArray()
    {
        $array = $this->attributes;
        
        // Hide sensitive fields
        foreach ($this->hidden as $hidden) {
            unset($array[$hidden]);
        }
        
        return $array;
    }
}

function class_uses_recursive($class)
{
    $results = [];
    foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
        $results += trait_uses_recursive($class);
    }
    return array_unique($results);
}

function trait_uses_recursive($trait)
{
    $traits = class_uses($trait);
    foreach ($traits as $trait) {
        $traits += trait_uses_recursive($trait);
    }
    return $traits;
}

function class_basename($class)
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}