<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Str;

/**
 * Class Model
 */
class Model
{
    public array $attributes = [];

    public array $relationships = [];

    /**
     * Model constructor.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Set the relationships for the object
     */
    public function setRelationships(array $relationships): void
    {
        $this->relationships = $relationships;
    }

    /**
     * Return the array of relationships
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Helper function to get a relationship of the model.
     *
     * @return mixed|null
     */
    protected function getRelationship(string $key): mixed
    {
        if (array_key_exists($key, $this->relationships)) {
            if (is_array($this->relationships[$key])) {
                return $this->relationships[$key][0];
            } else {
                return $this->relationships[$key];
            }
        }

        return null;
    }

    /**
     * Helper function to get the relationship and return it as an array
     *
     * @return mixed|null
     */
    protected function getRelationshipAsArray(string $key): mixed
    {
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        return null;
    }

    /**
     * Magic method to get attribute values from the attributes array.
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        $method = 'get'.Str::studly($name).'Attribute';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Magic method to see if the class variable exists.
     */
    public function __isset($name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Magic method to get a relationship.
     *
     * @return \Illuminate\Support\Collection|mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function __call($methodName, $arguments)
    {
        //
    }

    /**
     * Convert the model to a string.
     */
    public function __toString(): string
    {
        $output = [];
        $output = $this->attributes;

        foreach ($this->relationships as $type => $relations) {
            foreach ($relations as $relation) {
                if (is_array($relation) && array_key_exists('attributes', $relation)) {
                    $output[$type][] = $relation['attributes'];
                } elseif (is_object($relation) && property_exists($relation, 'attributes')) {
                    $output[$type][] = $relation->attributes;
                }
            }
        }

        return json_encode($output);
    }
}
