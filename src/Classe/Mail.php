<?php

namespace App\Classe;

use Mailjet\Client;
use Mailjet\Resources;
use Symfony\Component\Dotenv\Dotenv;

class Mail
{
    private $api_key;
    private $api_key_secret;

    public function __construct()
    {
        (new Dotenv())->bootEnv(dirname(__DIR__) . '/../.env');
        $this->api_key = $_ENV['API_KEY'];
        $this->api_key_secret = $_ENV['API_KEY_SECRET'];
    }

    public function send($to_email, $to_name, $subject, $content)
    {
        $mj = new Client($this->api_key, $this->api_key_secret, true, ['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "uhtred.fils.d.uhtred@gmail.com",
                        'Name' => "La boutique"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'TemplateID' => 1810610,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'Variables' => [
                        'content' => $content
                    ]
                ]
            ]
        ];

        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
    }
}
