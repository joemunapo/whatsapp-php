<?php

use Joemunapo\Whatsapp\AccountResolver;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->accountResolver = new AccountResolver();

    Config::shouldReceive('get')
        ->with('whatsapp.fields')
        ->andReturn([
            'token' => 'token',
            'number_id' => 'number_id',
            'catalog_id' => 'catalog_id',
        ]);

    Config::shouldReceive('get')
        ->with('whatsapp.account_model')
        ->andReturn(\stdClass::class);


    Config::shouldReceive('get')
        ->with('database.default')
        ->andReturn('dummy');

    Config::shouldReceive('get')
        ->with('database.connections.dummy')
        ->andReturn(['driver' => 'dummy']);
});

it('resolves an account with valid number ID', function () {
    $numberId = '123456789';
    
    $expectedAccount = [
        'token' => 'test_token',
        'number_id' => $numberId,
        'catalog_id' => 'test_catalog_id',
    ];

    $mockModel = Mockery::mock(\stdClass::class);
    $mockModel->shouldReceive('where->first')
        ->andReturn((object) $expectedAccount);

    $result = (new AccountResolver($mockModel))->resolve($numberId);

    expect($result)->toBe($expectedAccount);
});

it('returns null for non-existent number ID', function () {
    $numberId = 'non_existent';

    $mockModel = Mockery::mock(\stdClass::class);
    $mockModel->shouldReceive('where->first')
        ->andReturn(null);

    $result = (new AccountResolver($mockModel))->resolve($numberId);

    expect($result)->toBeNull();
});
