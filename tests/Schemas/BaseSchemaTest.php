<?php

use CardTechie\TradingCardApiSdk\Schemas\BaseSchema;

// Create a concrete test implementation of BaseSchema
class TestSchema extends BaseSchema
{
    public function getRules(): array
    {
        return $this->mergeRules(
            $this->getJsonApiRules(),
            $this->getMetaLinksRules(),
            ['custom.field' => 'sometimes|string']
        );
    }

    // Expose protected methods for testing
    public function testGetJsonApiRules(): array
    {
        return $this->getJsonApiRules();
    }

    public function testGetJsonApiCollectionRules(): array
    {
        return $this->getJsonApiCollectionRules();
    }

    public function testGetMetaLinksRules(): array
    {
        return $this->getMetaLinksRules();
    }

    public function testGetSimpleObjectRules(): array
    {
        return $this->getSimpleObjectRules();
    }

    public function testGetSimpleCollectionRules(): array
    {
        return $this->getSimpleCollectionRules();
    }

    public function testMergeRules(array ...$ruleArrays): array
    {
        return $this->mergeRules(...$ruleArrays);
    }
}

it('provides JSON API structure rules', function () {
    $schema = new TestSchema;
    $rules = $schema->testGetJsonApiRules();

    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.id');
    expect($rules)->toHaveKey('data.type');
    expect($rules)->toHaveKey('data.attributes');

    expect($rules['data'])->toBe('required|array');
    expect($rules['data.id'])->toBe('required|string');
    expect($rules['data.type'])->toBe('required|string');
    expect($rules['data.attributes'])->toBe('required|array');
});

it('provides JSON API collection structure rules', function () {
    $schema = new TestSchema;
    $rules = $schema->testGetJsonApiCollectionRules();

    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('data.*.id');
    expect($rules)->toHaveKey('data.*.type');
    expect($rules)->toHaveKey('data.*.attributes');

    expect($rules['data'])->toBe('required|array');
    expect($rules['data.*.id'])->toBe('sometimes|required|string');
    expect($rules['data.*.type'])->toBe('sometimes|required|string');
    expect($rules['data.*.attributes'])->toBe('sometimes|required|array');
});

it('provides meta and links rules', function () {
    $schema = new TestSchema;
    $rules = $schema->testGetMetaLinksRules();

    expect($rules)->toHaveKey('meta');
    expect($rules)->toHaveKey('links');
    expect($rules)->toHaveKey('included');
    expect($rules)->toHaveKey('included.*.id');
    expect($rules)->toHaveKey('included.*.type');
    expect($rules)->toHaveKey('included.*.attributes');

    expect($rules['meta'])->toBe('sometimes|array');
    expect($rules['links'])->toBe('sometimes|array');
    expect($rules['included'])->toBe('sometimes|array');
});

it('provides simple object rules', function () {
    $schema = new TestSchema;
    $rules = $schema->testGetSimpleObjectRules();

    expect($rules)->toHaveKey('id');
    expect($rules['id'])->toBe('sometimes|string');
});

it('provides simple collection rules', function () {
    $schema = new TestSchema;
    $rules = $schema->testGetSimpleCollectionRules();

    expect($rules)->toHaveKey('*');
    expect($rules)->toHaveKey('*.id');
    expect($rules['*'])->toBe('array');
    expect($rules['*.id'])->toBe('sometimes|string');
});

it('merges multiple rule arrays correctly', function () {
    $schema = new TestSchema;

    $rules1 = ['field1' => 'required|string'];
    $rules2 = ['field2' => 'sometimes|integer'];
    $rules3 = ['field3' => 'nullable|boolean'];

    $merged = $schema->testMergeRules($rules1, $rules2, $rules3);

    expect($merged)->toHaveKey('field1');
    expect($merged)->toHaveKey('field2');
    expect($merged)->toHaveKey('field3');
    expect($merged['field1'])->toBe('required|string');
    expect($merged['field2'])->toBe('sometimes|integer');
    expect($merged['field3'])->toBe('nullable|boolean');
});

it('handles empty rule arrays in merge', function () {
    $schema = new TestSchema;

    $rules1 = ['field1' => 'required|string'];
    $rules2 = [];
    $rules3 = ['field3' => 'nullable|boolean'];

    $merged = $schema->testMergeRules($rules1, $rules2, $rules3);

    expect($merged)->toHaveKey('field1');
    expect($merged)->toHaveKey('field3');
    expect($merged)->not->toHaveKey('field2');
});

it('implements abstract getRules method', function () {
    $schema = new TestSchema;
    $rules = $schema->getRules();

    // Should contain merged rules from all base methods plus custom rules
    expect($rules)->toHaveKey('data');
    expect($rules)->toHaveKey('meta');
    expect($rules)->toHaveKey('custom.field');
    expect($rules['custom.field'])->toBe('sometimes|string');
});
