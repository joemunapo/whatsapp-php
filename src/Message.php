<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Arr;

class Message extends Session
{
    public string $from;

    public string $id;

    public $profile;

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

    // You can add more helper methods as needed
}
