<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Arr;

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

    protected Whatsapp $whatsapp;

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

        $context = $content->context ?? null;

        if (empty($content->buttons) && empty($content->list) && empty($content->description_list) && gettype($content->text) === 'string') {
            $content = (object) [
                'text' => [
                    'body' => $content->text,
                ],
            ];
        }

        if (isset($context)) {
            $content->context = [
                'message_id' => $context,
            ];
        }

        $this->markAsRead();

        return $this->whatsapp->sendMessage($this->from, $content);
    }

    public function replyWithMedia($mediaType, $mediaUrl, $caption = null)
    {
        return $this->whatsapp->sendMedia($this->from, $mediaType, $mediaUrl, $caption);
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

        return $this->whatsapp->sendMessage($this->from, $content);
    }

    public function markAsRead()
    {
        return $this->whatsapp->markMessageAsRead($this->from, $this->id);
    }

    public function replyWithTemplate($templateName, $languageCode, $components = [])
    {
        return $this->whatsapp->sendTemplate($this->from, $templateName, $languageCode, $components);
    }

    public function getMediaContent()
    {
        if ($this->mediaId) {
            return $this->whatsapp->getMedia($this->mediaId);
        }

        return null;
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
            throw new \Exception("FAILED TO RUN METHOD: " . $th->getMessage());
        }
    }
}
