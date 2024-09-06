<?php

namespace Joemunapo\Whatsapp;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Whatsapp Class for Sending messages to Whatsapp cloud API
 */
class Whatsapp
{
    private const WHATSAPP_API_URL = 'https://graph.facebook.com/v18.0';

    private const WHATSAPP_MESSAGE_API = 'messages';

    protected static $instance;

    protected $token;

    protected $numberId;

    protected $catalogId;

    protected $accountResolver;

    public function __construct(AccountResolver $accountResolver)
    {
        $this->accountResolver = $accountResolver;
    }

    // TODO: Verify why we need useNumberId() method and refactor it
    public static function getInstance(AccountResolver $accountResolver)
    {
        return new self($accountResolver);
    }

    public static function useNumberId($numberId)
    {
        $instance = self::getInstance(app(AccountResolver::class));

        return $instance->setNumberId($numberId);
    }

    public function setNumberId($numberId)
    {
        $account = $this->accountResolver->resolve($numberId);
        if (! $account) {
            throw new Exception("No WhatsApp account found for number ID: $numberId");
        }
        $this->setAccount($account['token'], $account['number_id'], $account['catalog_id'] ?? null);

        return $this;
    }

    /**
     * Get the account details, returns an instance of the account model
     *
     * @return mixed
     */
    public function getAccount()
    {
        return $this->accountResolver->getAccount();
    }

    protected function setAccount($token, $numberId, $catalogId = null)
    {
        $this->token = $token;
        $this->numberId = $numberId;
        $this->catalogId = $catalogId;

        return $this;
    }

    public static function handleWebhook($payload, ?self $instance = null)
    {
        $instance = $instance ?? self::getInstance(app(AccountResolver::class));

        $entry = Arr::get($payload, 'entry.0', null);
        if (! $entry) {
            return null;
        }

        $change = Arr::get($entry, 'changes.0', null);
        if (! $change || Arr::get($change, 'field') !== 'messages') {
            return null;
        }

        $messageData = (object) Arr::get($change, 'value.messages.0', null);
        if (! $messageData) {
            return null;
        }

        if (! in_array(optional($messageData)->type, ['text', 'interactive', 'media', 'document', 'image', 'video', 'order'])) {
            return null;
        }

        $numberId = Arr::get($change, 'value.metadata.phone_number_id');
        $instance = $instance->setNumberId($numberId);

        return new Message($messageData, $instance);
    }

    /**
     * Send a message to WhatsApp API
     *
     * @param  string  $to  Recipient's phone number
     * @param  object  $content  Message content
     * @return string|null Message ID if successful, null otherwise
     *
     * @throws \InvalidArgumentException
     * @throws WhatsappApiException
     */
    public function sendMessage(string $to, object $content): ?string
    {
        $this->validateSetup();

        if (! is_object($content)) {
            throw new \InvalidArgumentException('Content must be an object.');
        }

        $context = $content->context ?? null;

        $attempt_interactive = $this->createInteractiveMessage($content);

        if ($attempt_interactive) {
            $content = $attempt_interactive;
        } else {
            $content->type = $content->type ?? 'text';
        }

        if (! is_null($context)) {
            $content->context = [
                'message_id' => $context,
            ];
        }

        return $this->sendToWhatsAppAPI($to, $content);
    }

    /**
     * Create an interactive message based on content type
     *
     * @throws \InvalidArgumentException
     */
    protected function createInteractiveMessage(object $content): ?object
    {
        return match (true) {
            ! empty($content->buttons) => $this->createButtonMessage($content),
            ! empty($content->results) || ! empty($content->related) => $this->createProductListMessage($content),
            ! empty($content->list) || ! empty($content->description_list) => $this->createListMessage($content),
            ! empty($content->flow) => $this->createFlowMessage($content),
            default => null,
        };
    }

    /**
     * Create a button message
     */
    protected function createButtonMessage(object $content): object
    {
        $body = (object) [
            'type' => 'interactive',
            'interactive' => (object) [
                'type' => 'button',
                'body' => [
                    'text' => $content->text,
                ],
                'action' => [
                    'buttons' => $this->createButtons($content->buttons),
                ],
            ],
        ];

        $this->addHeaderAndFooter($body, $content);

        return $body;
    }

    /**
     * Create buttons for interactive messages
     */
    protected function createButtons(array $buttons): array
    {
        return Arr::map($buttons, fn ($btn) => [
            'type' => 'reply',
            'reply' => [
                'id' => $btn,
                'title' => $btn,
            ],
        ]);
    }

    /**
     * Create a product list message
     */
    protected function createProductListMessage(object $content): object
    {
        $body = (object) [
            'type' => 'interactive',
            'interactive' => (object) [
                'type' => 'product_list',
                'body' => [
                    'text' => $content->text,
                ],
                'action' => (object) [
                    'catalog_id' => $this->catalogId,
                    'sections' => [],
                ],
            ],
        ];

        if (! empty($content->results)) {
            $this->addProductSection($body, $content->results, $content->results_title);
        }

        if (! empty($content->related)) {
            $this->addProductSection($body, $content->related, $content->related_title);
        }

        $this->addHeaderAndFooter($body, $content);

        return $body;
    }

    /**
     * Create a list message
     */
    protected function createListMessage(object $content): object
    {
        $body = (object) [
            'type' => 'interactive',
            'interactive' => (object) [
                'type' => 'list',
                'body' => [
                    'text' => $content->text,
                ],
                'action' => (object) [
                    'button' => $content->list_button_title,
                    'sections' => [],
                ],
            ],
        ];

        $rows = ! empty($content->list)
            ? $this->createSimpleListRows($content->list)
            : $this->createDescriptionListRows($content->description_list);

        $body->interactive->action->sections[] = [
            'title' => $content->list_title ?? null,
            'rows' => $rows,
        ];

        $this->addHeaderAndFooter($body, $content);

        return $body;
    }

    /**
     * Add a product section to the message body
     */
    protected function addProductSection(object &$body, array $products, string $title): void
    {
        $items = Arr::map($products, fn ($prod_id) => ['product_retailer_id' => $prod_id]);

        $body->interactive->action->sections[] = [
            'title' => $title,
            'product_items' => $items,
        ];
    }

    /**
     * Create rows for a simple list
     */
    protected function createSimpleListRows(array $list): array
    {
        return Arr::map($list, fn ($item) => [
            'id' => $item,
            'title' => $item,
        ]);
    }

    /**
     * Create rows for a description list
     */
    protected function createDescriptionListRows(array $list): array
    {
        return Arr::map($list, fn ($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description ?? null,
        ]);
    }

    /**
     * Add header and footer to the message body
     */
    protected function addHeaderAndFooter(object &$body, object $content): void
    {
        if (! empty($content->header)) {
            $body->interactive->header = [
                'type' => 'text',
                'text' => $content->header,
            ];
        }

        if (! empty($content->caption)) {
            $body->interactive->footer = [
                'text' => $content->caption,
            ];
        }
    }

    protected function createFlowMessage(object $content): object
    {
        throw_if(strlen($content->flow->cta) > 20, 'Flow CTA must be less than 20 characters');

        $body = (object) [
            'type' => 'interactive',
            'interactive' => (object) [
                'type' => 'flow',
                'body' => [
                    'text' => $content->text,
                ],
                'action' => (object) [
                    'name' => 'flow',
                    'parameters' => (object) [
                        'mode' => $content->flow->mode ?? 'published',
                        'flow_message_version' => '3',
                        'flow_token' => $content->flow->token,
                        'flow_id' => $content->flow->id,
                        'flow_cta' => $content->flow->cta,
                        'flow_action' => $content->flow->action,
                        'flow_action_payload' => (object) [
                            'screen' => $content->flow->screen,
                            'data' => isset($content->flow->data) ? (object) $content->flow->data : null,
                        ],
                    ],
                ],
            ],
        ];

        // If data is null, remove it from the payload
        if (! isset($content->flow->data)) {
            unset($body->interactive->action->parameters->flow_action_payload->data);
        }

        $this->addHeaderAndFooter($body, $content);

        return $body;
    }

    /**
     * Send the message to WhatsApp API
     *
     * @throws WhatsappApiException
     */
    protected function sendToWhatsAppAPI(string $to, object $content): ?string
    {
        $content->to = $to;
        $content->messaging_product = 'whatsapp';
        $content->recipient_type = 'individual';

        $url = $this->buildApiEndpoint('messages');

        try {
            $response = $this->request()->post($url, $content);

            if ($response->failed()) {
                throw new Exception("Failed to send WA message to {$to}: {$response->body()}");
            }

            return Arr::get($response->json(), 'messages.0.id');
        } catch (\Throwable $th) {
            throw new Exception("Failed to send WA message to {$to}: {$th->getMessage()}");
        }
    }

    /**
     * Build the API endpoint URL
     */
    protected function buildApiEndpoint(string $for = self::WHATSAPP_MESSAGE_API, bool $withNumberId = true): string
    {
        return str(self::WHATSAPP_API_URL)
            ->when($withNumberId, fn ($str) => $str->append('/', $this->numberId))
            ->append('/', $for);
    }

    /**
     * Create a new HTTP request instance
     */
    protected function request(): PendingRequest
    {
        return Http::acceptJson()->withToken($this->token);
    }

    public function sendMedia(string $to, string $mediaType, string $mediaUrl, ?string $caption = null): ?string
    {
        $this->validateSetup();

        $data = (object) [
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
                'caption' => $caption,
            ],
        ];

        return $this->sendToWhatsAppAPI($to, $data);
    }

    public function sendTemplate(string $to, string $templateName, string $languageCode, array $components = []): ?string
    {
        $this->validateSetup();

        $data = (object) [
            'type' => 'template',
            'template' => (object) [
                'name' => $templateName,
                'language' => (object) ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->sendToWhatsAppAPI($to, $data);
    }

    public function markMessageAsRead(string $phoneNumber, string $messageId): ?string
    {
        $this->validateSetup();

        $data = (object) [
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->sendToWhatsAppAPI($phoneNumber, $data);
    }

    public function getMedia($mediaId)
    {
        $this->validateSetup();

        $apiUrl = self::WHATSAPP_API_URL;

        $response = Http::withToken($this->token)->get("{$apiUrl}/{$mediaId}");

        if ($response->failed()) {
            throw new Exception("Failed to get media: {$response->body()}");
        }

        return $response->json();
    }

    public function downLoadMedia(string $url)
    {
        $this->validateSetup();

        return $this->request()
            ->get($url);
    }

    protected function validateSetup()
    {
        if (! $this->token || ! $this->numberId) {
            throw new Exception('WhatsApp account not properly configured. Use useNumberId() before making requests.');
        }
    }
}
