<?php

namespace App\Connectors;

class SlackClient
{
    private string $endpoint;

    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function send(string $text): void
    {
        $payload = [
            'text' => $text,
            'channel' => null,
            'username' => null,
            'link_names' => 0,
            'unfurl_links' => false,
            'unfurl_media' => true,
            'mrkdwn' => true,
            'attachments' => [],
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $this->post($this->endpoint, $encoded);
    }

    private function post($url, string $body): void
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => $body
            ],
        ];

        file_get_contents($url, false, stream_context_create($options));
    }
}
