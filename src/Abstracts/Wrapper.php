<?php

namespace Fintech\Remit\Abstracts;

use ArrayAccess;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

abstract class Wrapper implements Arrayable, ArrayAccess, Jsonable
{
    protected $raw;

    protected array $extra = [];

    protected array $attributes = [];

    protected array $fillable = [];

    protected array $casts = [];

    public function __construct($raw)
    {
        $this->raw = $raw;
    }

    public function __toString()
    {
        return $this->toJson();
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]) || isset($this->extra[$offset]);
    }

    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->attributes)) {
            return $this->attributes[$offset];
        } elseif (array_key_exists($offset, $this->extra)) {
            return $this->extra[$offset];
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value): void
    {
        (array_key_exists($offset, $this->attributes))
            ? $this->attributes[$offset] = $value
            : $this->extra[$offset] = $value;

        $this->resolveCasts();
    }

    public function offsetUnset($offset): void
    {
        if (array_key_exists($offset, $this->attributes)) {
            unset($this->attributes[$offset]);
        }

        if (array_key_exists($offset, $this->extra)) {
            unset($this->extra[$offset]);
        }
    }

    public function toRaw()
    {
        return $this->raw;
    }

    public function toArray(): array
    {
        return array_merge($this->attributes, $this->extra);
    }

    public function toJson($options = 0): bool|string
    {
        $json = json_encode($this->attributes, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \JsonException(json_last_error_msg());
        }

        return $json;
    }

    public function resolveCasts(): void
    {
        foreach ($this->casts as $field => $cast) {

            if (array_key_exists($field, $this->attributes)) {
                $this->attributes[$field] = $this->convertValue($cast, $this->attributes[$field]);
            }

            if (array_key_exists($field, $this->extra)) {
                $this->extra[$field] = $this->convertValue($cast, $this->extra[$field]);
            }
        }

    }

    private function convertValue($type, $value): mixed
    {
        if ($value == null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
            case 'bool' :
                if (is_string($value)) {
                    return $value == 'true';
                }

                return (bool) $value;

            case 'float' :
            case 'double' :
            case 'decimal' :
                return (float) $value;

            case 'integer' :
                return (int) $value;

            case 'array' :
                return Arr::wrap($value);

            case 'datetime' :
            case 'date':
                return Carbon::parse($value);

                //            case 'money' :
                //                return Money::parse($value);

            case 'string' :
                return (string) $value;

            default:
                return $value;
        }
    }
}
