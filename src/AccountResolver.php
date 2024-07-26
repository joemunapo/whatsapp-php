<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Facades\Config;

class AccountResolver
{
    protected $model;

    protected $fields;

    protected $account;

    public function __construct($model = null)
    {
        $this->model = $model ?? Config::get('whatsapp.account_model');
        $this->fields = Config::get('whatsapp.fields');
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function resolve($numberId): ?array
    {
        $this->account = $this->model::where($this->fields['number_id'], $numberId)->first();

        if (! $this->account) {
            return null;
        }

        return [
            'token' => $this->account->{$this->fields['token']},
            'number_id' => $this->account->{$this->fields['number_id']},
            'catalog_id' => $this->account->{$this->fields['catalog_id']},
        ];
    }
}
