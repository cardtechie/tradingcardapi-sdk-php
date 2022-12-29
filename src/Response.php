<?php

namespace CardTechie\TradingCardApiSdk;

use Illuminate\Support\Str;
use stdClass;

/**
 * Class Response
 *
 * Parse the JSON API response and make the objects easily accessible to display in the UI.
 */
class Response
{
    private object $response;

    public $mainObject;

    public $relationships;

    private static object $meta;

    private static object $links;

    /**
     * Response constructor.
     *
     * @param  string  $json
     */
    public function __construct(string $json)
    {
        $this->response = json_decode($json);

        $this->instantiateMainObject();
        $this->parseIncluded();
        $this->objectifyRelationships();

        $this->mainObject->setRelationships($this->relationships);
    }

    /**
     * Create the main object with all its attributes.
     */
    private function instantiateMainObject(): void
    {
        $attributes = (array) $this->response->data->attributes;
        $attributes['id'] = $this->response->data->id;

        $type = ucfirst(Str::singular($this->response->data->type));
        $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$type;
        $this->mainObject = new $class($attributes);
    }

    /**
     * Add any included objects and make them really easy to retrieve.
     */
    private function parseIncluded(): void
    {
        $includes = [];
        if (! property_exists($this->response, 'included')) {
            $this->relationships = $includes;

            return;
        }

        foreach ($this->response->included as $included) {
            $object = [];
            $object['id'] = $included->id;
            foreach ($included->attributes as $key => $value) {
                $object[$key] = $value;
            }
            $includes[$included->type][] = $object;
        }

        $this->relationships = $includes;
    }

    /**
     * Convert the relationships into models.
     */
    private function objectifyRelationships(): void
    {
        foreach ($this->relationships as $type => $theObjects) {
            foreach ($theObjects as $index => $attributes) {
                $theType = ucfirst(Str::singular($type));
                if ('Parentset' === $theType || 'Subset' === $theType) {
                    $theType = 'Set';
                } elseif ('Checklist' === $theType) {
                    $theType = 'Card';
                }
                $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$theType;
                $object = new $class($attributes);

                $this->relationships[$type][$index] = $object;
            }
        }
    }

    /**
     * Get the meta data from the response.
     *
     * @return object
     */
    public static function getMeta(): object
    {
        return self::$meta;
    }

    /**
     * Get the links data from the response.
     *
     * @return object
     */
    public static function getLinks(): object
    {
        return self::$links;
    }

    /**
     * Parse the JSON and convert it into an object
     *
     * @param  string  $json
     * @return \Illuminate\Support\Collection|object
     */
    public static function parse(string $json)
    {
        $response = json_decode($json);

        self::parseMeta($response);
        self::parseLinks($response);

        if (is_array($response->data)) {
            $objects = [];
            foreach ($response->data as $data) {
                $object = self::parseDataObject($data);
                $object->setRelationships(self::getIncluded($response));
                $objects[] = $object;
            }

            return collect($objects);
        } else {
            $object = self::parseDataObject($response->data);
            $object->setRelationships(self::getIncluded($response));

            return $object;
        }
    }

    /**
     * Retrieve the main object with all its attributes.
     *
     * @param  object  $data
     * @return object
     */
    private static function parseDataObject(object $data): object
    {
        $attributes = (array) $data->attributes;
        $attributes['id'] = $data->id;

        $type = ucfirst(Str::singular($data->type));
        $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$type;

        return new $class($attributes);
    }

    /**
     * Get the included objects as an array
     *
     * @param  object  $data
     * @return array
     */
    private static function getIncluded(object $data): array
    {
        $includes = [];
        if (! property_exists($data, 'included')) {
            return $includes;
        }

        foreach ($data->included as $included) {
            $attributes = [];
            $attributes['id'] = $included->id;
            foreach ($included->attributes as $key => $value) {
                $attributes[$key] = $value;
            }

            if ('parentset' == $included->type) {
                $type = 'Set';
            } elseif ('checklist' === $included->type) {
                $type = 'Card';
            } else {
                $type = ucfirst(Str::singular($included->type));
            }
            $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$type;
            $object = new $class($attributes);

            $includes[$included->type][] = $object;
        }

        return $includes;
    }

    /**
     * Parse the meta from the response and set the $meta field of this class.
     *
     * @param $data
     */
    private static function parseMeta($data): void
    {
        $meta = new stdClass();
        if (! property_exists($data, 'meta')) {
            self::$meta = $meta;

            return;
        }

        self::$meta = $data->meta;
    }

    /**
     * Parse the links from the response and set the $links field of this class.
     *
     * @param $data
     */
    private static function parseLinks($data): void
    {
        $links = new stdClass();
        if (! property_exists($data, 'links')) {
            self::$links = $links;

            return;
        }

        self::$links = $data->links;
    }
}
