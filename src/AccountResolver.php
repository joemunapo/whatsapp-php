<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Facades\Config;

class AccountResolver
{
    protected $model;

    protected $fields;

    public function __construct($model = null)
    {
        $this->model = $model ?? Config::get('whatsapp.account_model');
        $this->fields = Config::get('whatsapp.fields');
    }

    public function resolve($numberId): ?array
    {
        $account = $this->model::where($this->fields['number_id'], $numberId)->first();

        if (! $account) {
            return null;
        }

        return [
            'token' => $account->{$this->fields['token']},
            'number_id' => $account->{$this->fields['number_id']},
            'catalog_id' => $account->{$this->fields['catalog_id']},
        ];
    }
}
