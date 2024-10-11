<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Arr;
use Joemunapo\Whatsapp\Events\MessageReceived;
use Joemunapo\Whatsapp\Events\MessageSent;

class Message extends Session
{
    public string $from;

    public string $id;

    // User/Client sending message - Can be initializes in webhook
    public $user;

    public string $text;

    public string $type;

    public ?string $mediaId;

    public ?object $media;

    public bool $isButton;

    public bool $isOrder;

    public Whatsapp $whatsapp;

    public function __construct($message, Whatsapp $whatsapp)
    {
        parent::__construct($message->from);
        $this->whatsapp = $whatsapp;
        $this->initializeMessage($message);
    }

    public function getAccount()
    {
        return $this->whatsapp->getAccount();
    }

    protected function initializeMessage($message)
    {
        $this->from = $message->from;
        $this->id = $message->id;
        $this->type = $message->type;

        $media = $message->{$this->type};
        $media_type = Arr::get($media, 'type', '__');
        $this->mediaId = Arr::get($media, "$media_type.id", Arr::get($media, 'id', ''));
        $this->media = (object) $media;

        $this->text = collect([
            Arr::get($media, 'body', null),
            Arr::get($media, 'caption', null),
            Arr::get($media, "{$media_type}.title", null),
        ])->whereNotNull()->first(default: '');

        $this->isButton = $media_type === 'button_reply';
        $this->isOrder = $this->type === 'order';

        // Dispatch the MessageReceived event
        event(new MessageReceived($this));
    }

    public function isHi()
    {
        if (! is_string($this->text)) {
            return false;
        }
        $his = ['hi', '#', 'hie', 'hey', 'hello', 'menu', 'hy', 'yo', 'go home', 'home', '🏠 home', 'mom', 'makadini', 'murisei'];

        return in_array(strtolower($this->text), $his);
    }

    public function reply($content)
    {
        if (gettype($content) === 'array') {
            $content = (object) $content;
        }

        if (gettype($content) === 'string') {
            $content = (object) [
                'text' => [
                    'body' => $content,
                ],
            ];
        }

        if (
            empty($content->buttons) &&
            empty($content->list) &&
            empty($content->description_list) &&
            empty($content->flow) &&
            gettype($content->text) === 'string'
        ) {
            $header = optional($content)->header ?? null;
            $caption = optional($content)->caption ?? null;

            $text = $content->text;
            $text = ! is_null($header) ? "*{$header}*\n$text" : $text;
            $text = ! is_null($caption) ? "$text\n\n_{$caption}_" : $text;

            $content = (object) [
                'text' => [
                    'body' => $text,
                ],
            ];
        }

        $this->markAsRead();

        $messageId = $this->whatsapp->sendMessage($this->from, $content);
        event(new MessageSent($this, $content, $messageId));

        return $messageId;
    }

    public function replyWithMedia($mediaType, $mediaUrl, $caption = null)
    {
        $messageId = $this->whatsapp->sendMedia($this->from, $mediaType, $mediaUrl, $caption);

        $content = (object) [
            'type' => $mediaType,
            $mediaType => [
                'link' => $mediaUrl,
                'caption' => $caption,
            ],
        ];

        event(new MessageSent($this, $content, $messageId));

        return $messageId;
    }

    public function replyWithProducts($content)
    {
        throw_if(! $this->getAccount()->catalog_id, 'NO_CATALOG_ID');

        if (gettype($content) === 'array') {
            $content = (object) $content;
        }

        $total = count($content->results) + count($content?->related ?? []);

        throw_if($total === 0, 'NO_PRODUCTS_FOUND');

        throw_if($total > 30, '30_MAX_PRODUCTS_ALLOWED');

        $messageId = $this->whatsapp->sendMessage($this->from, $content);

        event(new MessageSent($this, $content, $messageId));

        return $messageId;
    }

    public function markAsRead()
    {
        return $this->whatsapp->markMessageAsRead($this->from, $this->id);
    }

    public function replyWithTemplate($templateName, $languageCode, $components = [])
    {
        $messageId = $this->whatsapp->sendTemplate($this->from, $templateName, $languageCode, $components);

        $content = (object) [
            'type' => 'template',
            'template' => (object) [
                'name' => $templateName,
                'language' => (object) ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        event(new MessageSent($this, $content, $messageId));

        return $messageId;
    }

    public function getMediaContent()
    {
        if ($this->mediaId) {
            return $this->whatsapp->getMedia($this->mediaId);
        }

        return null;
    }

    public function downloadMedia()
    {
        if (! $this->mediaId) {
            return null;
        }

        $media = $this->getMediaContent();

        return $this->whatsapp->downLoadMedia($media->url);
    }

    public function isText()
    {
        return $this->type === 'text';
    }

    public function isMedia()
    {
        return in_array($this->type, ['image', 'video', 'audio', 'document']);
    }

    public function isLocation()
    {
        return $this->type === 'location';
    }

    public function isContact()
    {
        return $this->type === 'contacts';
    }

    public function next($param = null)
    {
        try {
            return app($this->get('controller'))->{$this->get('method')}($this, $param);
        } catch (\Throwable $th) {
            throw new \Exception('FAILED TO RUN METHOD: '.$th->getMessage());
        }
    }
}
