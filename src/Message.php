<?php

namespace Joemunapo\Whatsapp;

use Illuminate\Support\Arr;

class Message
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
        $this->whatsapp = $whatsapp;
        $this->initializeMessage($message);
    }

    protected function initializeMessage($message)
    {
        $this->from = $message->from;
        $this->id = $message->id;
        $this->type = $message->type;

        $media = $message->{$this->type};
        $mediaType = Arr::get($media, "type", "__");
        $this->mediaId = Arr::get($media, "$mediaType.id", Arr::get($media, "id", ""));
        $this->media = (object) $media;

        $this->text = collect([
            Arr::get($media, 'body', null),
            Arr::get($media, 'caption', null),
            Arr::get($media, "{$mediaType}.title", null)
        ])->whereNotNull()->first('');

        $this->isButton = $mediaType === 'button_reply';
        $this->isOrder = $this->type === 'order';
    }

    public function reply($content)
    {
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
