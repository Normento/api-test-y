<?php


namespace Core\ExternalServices;

use Core\Utils\Constants;
use Facebook\Facebook;

class FacebookService
{
    protected $fb;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => Constants::FACEBOOK_APP_ID,
            'app_secret' => Constants::FACEBOOK_APP_SECRET,
            'default_graph_version' => 'v15.0', // Assurez-vous d'utiliser la dernière version de l'API
        ]);
    }

    public function publishOnFacebook($data)
    {

            $response = $this->fb->post(
                '/me/feed',
                ['message' => $data['content']],
                Constants::FACEBOOK_ACCESS_TOKEN
            );

            return $response;

    }
}
