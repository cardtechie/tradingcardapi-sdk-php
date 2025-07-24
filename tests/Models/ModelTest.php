<?php

use CardTechie\TradingCardApiSdk\Models\Model;

it('can be instantiated with attributes', function () {
    $model = new Model(['id' => '123', 'name' => 'Test']);

    expect($model)->toBeInstanceOf(Model::class);
    expect($model->attributes)->toBe(['id' => '123', 'name' => 'Test']);
});

it('can be instantiated without attributes', function () {
    $model = new Model;

    expect($model->attributes)->toBe([]);
});

it('can set and get relationships', function () {
    $model = new Model;
    $relationships = ['users' => [['id' => '1']], 'posts' => [['id' => '2']]];

    $model->setRelationships($relationships);

    expect($model->getRelationships())->toBe($relationships);
});

it('can get single relationship', function () {
    $model = new Model;
    $user = ['id' => '1', 'name' => 'John'];

    $model->setRelationships(['user' => [$user]]);

    $reflection = new ReflectionClass($model);
    $method = $reflection->getMethod('getRelationship');
    $method->setAccessible(true);

    expect($method->invoke($model, 'user'))->toBe($user);
});

it('returns null for non-existent relationship', function () {
    $model = new Model;

    $reflection = new ReflectionClass($model);
    $method = $reflection->getMethod('getRelationship');
    $method->setAccessible(true);

    expect($method->invoke($model, 'nonexistent'))->toBeNull();
});

it('can get relationship as array', function () {
    $model = new Model;
    $users = [['id' => '1'], ['id' => '2']];

    $model->setRelationships(['users' => $users]);

    $reflection = new ReflectionClass($model);
    $method = $reflection->getMethod('getRelationshipAsArray');
    $method->setAccessible(true);

    expect($method->invoke($model, 'users'))->toBe($users);
});

it('magic get returns attribute values', function () {
    $model = new Model(['name' => 'Test', 'id' => '123']);

    expect($model->name)->toBe('Test');
    expect($model->id)->toBe('123');
});

it('magic get returns null for non-existent attributes', function () {
    $model = new Model(['name' => 'Test']);

    expect($model->nonexistent)->toBeNull();
});

it('magic isset works correctly', function () {
    $model = new Model(['name' => 'Test']);

    expect(isset($model->name))->toBeTrue();
    expect(isset($model->nonexistent))->toBeFalse();
});

it('converts to string correctly', function () {
    $model = new Model(['id' => '123', 'name' => 'Test']);

    $expected = json_encode(['id' => '123', 'name' => 'Test']);
    expect((string) $model)->toBe($expected);
});

it('converts to string with relationships', function () {
    $model = new Model(['id' => '123']);
    $relationships = [
        'users' => [
            (object) ['attributes' => ['name' => 'John']],
            (object) ['attributes' => ['name' => 'Jane']],
        ],
    ];

    $model->setRelationships($relationships);

    $result = (string) $model;
    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('id', '123');
    expect($decoded)->toHaveKey('users');
    expect($decoded['users'])->toHaveCount(2);
});

it('handles custom attribute accessors', function () {
    $customModel = new class(['first_name' => 'John', 'last_name' => 'Doe']) extends Model
    {
        public function getFullNameAttribute(): string
        {
            return $this->attributes['first_name'].' '.$this->attributes['last_name'];
        }
    };

    expect($customModel->full_name)->toBe('John Doe');
});
