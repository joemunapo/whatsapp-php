<?php

namespace Joemunapo\Whatsapp;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class Whatsapp
{
    protected static $instance;

    protected $token;

    protected $numberId;

    protected $catalogId;

    protected $apiUrl = 'https://graph.facebook.com/v16.0';

    protected $accountResolver;

    public function __construct(AccountResolver $accountResolver)
    {
        $this->accountResolver = $accountResolver;
    }

    public static function getInstance(AccountResolver $accountResolver)
    {
        if (! self::$instance) {
            self::$instance = new self($accountResolver);
        }

        return self::$instance;
    }

    public static function useNumberId($numberId)
    {
        $instance = self::getInstance(app(AccountResolver::class));
        $account = $instance->accountResolver->resolve($numberId);
        if (! $account) {
            throw new Exception("No WhatsApp account found for number ID: $numberId");
        }
        $instance->setAccount($account['token'], $account['number_id'], $account['catalog_id'] ?? null);

        return $instance;
    }

    protected function setAccount($token, $numberId, $catalogId = null)
    {
        $this->token = $token;
        $this->numberId = $numberId;
        $this->catalogId = $catalogId;

        return $this;
    }

    public function sendMessage($to, $content)
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $content],
        ];

        return $this->sendRequest('messages', $data);
    }

    public function sendMedia($to, $mediaType, $mediaUrl, $caption = null)
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
                'caption' => $caption,
            ],
        ];

        return $this->sendRequest('messages', $data);
    }

    public function sendTemplate($to, $templateName, $languageCode, $components = [])
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->sendRequest('messages', $data);
    }

    public function markMessageAsRead($phoneNumber, $messageId)
    {
        $this->validateSetup();

        $data = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendRequest('messages', $data);
    }

    public function getMedia($mediaId)
    {
        $this->validateSetup();

        $response = Http::withToken($this->token)->get("{$this->apiUrl}/{$mediaId}");

        if ($response->failed()) {
            throw new Exception('Failed to get media: '.$response->body());
        }

        return $response->json();
    }

    protected function sendRequest($endpoint, $data)
    {
        $url = "{$this->apiUrl}/{$this->numberId}/{$endpoint}";

        $response = Http::withToken($this->token)->post($url, $data);

        if ($response->failed()) {
            throw new Exception('WhatsApp API request failed: '.$response->body());
        }

        return $response->json();
    }

    protected function validateSetup()
    {
        if (! $this->token || ! $this->numberId) {
            throw new Exception('WhatsApp account not properly configured. Use useNumberId() before making requests.');
        }
    }

    public static function handleWebhook($payload)
    {
        $instance = self::getInstance(app(AccountResolver::class));
        $entry = Arr::get($payload, 'entry.0', null);
        if (! $entry) {
            return null;
        }

        $change = Arr::get($entry, 'changes.0', null);
        if (! $change || Arr::get($change, 'field') !== 'messages') {
            return null;
        }

        $messageData = Arr::get($change, 'value.messages.0', null);
        if (! $messageData) {
            return null;
        }

        $numberId = Arr::get($change, 'value.metadata.phone_number_id');
        $instance->useNumberId($numberId);

        return new Message($messageData, $instance);
    }
}

// {
//     protected $token;

//     protected $numberId;

//     protected $catalogId;

//     protected $apiUrl = 'https://graph.facebook.com/v18.0';

//     protected $accountResolver;

//     public function __construct(AccountResolver $accountResolver)
//     {
//         $this->accountResolver = $accountResolver;
//     }

//     public function useNumberId($numberId)
//     {
//         $account = $this->accountResolver->resolve($numberId);
//         if (! $account) {
//             throw new Exception("No WhatsApp account found for number ID: $numberId");
//         }
//         $this->setAccount($account['token'], $account['number_id'], $account['catalog_id'] ?? null);

//         return $this;
//     }

//     protected function setAccount($token, $numberId, $catalogId = null)
//     {
//         $this->token = $token;
//         $this->numberId = $numberId;
//         $this->catalogId = $catalogId;

//         return $this;
//     }

//     public function sendMessage($to, $content)
//     {
//         $this->validateSetup();

//         $data = [
//             'messaging_product' => 'whatsapp',
//             'recipient_type' => 'individual',
//             'to' => $to,
//             'type' => 'text',
//             'text' => ['body' => $content],
//         ];

//         return $this->sendRequest('messages', $data);
//     }

//     public function sendMedia($to, $mediaType, $mediaUrl, $caption = null)
//     {
//         $this->validateSetup();

//         $data = [
//             'messaging_product' => 'whatsapp',
//             'recipient_type' => 'individual',
//             'to' => $to,
//             'type' => $mediaType,
//             $mediaType => [
//                 'link' => $mediaUrl,
//                 'caption' => $caption,
//             ],
//         ];

//         return $this->sendRequest('messages', $data);
//     }

//     public function sendTemplate($to, $templateName, $languageCode, $components = [])
//     {
//         $this->validateSetup();

//         $data = [
//             'messaging_product' => 'whatsapp',
//             'recipient_type' => 'individual',
//             'to' => $to,
//             'type' => 'template',
//             'template' => [
//                 'name' => $templateName,
//                 'language' => ['code' => $languageCode],
//                 'components' => $components,
//             ],
//         ];

//         return $this->sendRequest('messages', $data);
//     }

//     public function markMessageAsRead($phoneNumber, $messageId)
//     {
//         $this->validateSetup();

//         $data = [
//             'messaging_product' => 'whatsapp',
//             'status' => 'read',
//             'message_id' => $messageId,
//         ];

//         return $this->sendRequest('messages', $data);
//     }

//     public function getMedia($mediaId)
//     {
//         $this->validateSetup();

//         $response = Http::withToken($this->token)->get("{$this->apiUrl}/{$mediaId}");

//         if ($response->failed()) {
//             throw new Exception('Failed to get media: '.$response->body());
//         }

//         return $response->json();
//     }

//     protected function sendRequest($endpoint, $data)
//     {
//         $url = "{$this->apiUrl}/{$this->numberId}/{$endpoint}";

//         $response = Http::withToken($this->token)->post($url, $data);

//         if ($response->failed()) {
//             throw new Exception('WhatsApp API request failed: '.$response->body());
//         }

//         return $response->json();
//     }

//     protected function validateSetup()
//     {
//         if (! $this->token || ! $this->numberId) {
//             throw new Exception('WhatsApp account not properly configured. Use useNumberId() before making requests.');
//         }
//     }

//     public function handleWebhook($payload)
//     {
//         $entry = Arr::get($payload, 'entry.0', null);
//         if (! $entry) {
//             return null;
//         }

//         $change = Arr::get($entry, 'changes.0', null);
//         if (! $change || Arr::get($change, 'field') !== 'messages') {
//             return null;
//         }

//         $messageData = Arr::get($change, 'value.messages.0', null);
//         if (! $messageData) {
//             return null;
//         }

//         $numberId = Arr::get($change, 'value.metadata.phone_number_id');
//         $this->useNumberId($numberId);

//         return new Message($messageData, $this);
//     }

//     // Additional methods can be added here as needed
// }
