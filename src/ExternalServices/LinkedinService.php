<?php

namespace Core\ExternalServices;

use Core\Utils\Constants;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use GuzzleHttp\Client;


class LinkedinService
{


    public function publishOnLinkedin($data)
    {
        $accessToken = Constants::API_LINKEDIN_TOKEN;
        $personUrn = Constants::API_LINKEDIN_PERSON_URN;

        $content_post = $data["content"];
        $image = $data["post_image"];
        $title_post = $data["title"];


        $client = new Client();
        $response = $client->post('https://api.linkedin.com/v2/shares', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version'=> '2.0.0',
            ],
            'json' => [
                'owner' =>  $personUrn,
                'subject' => $title_post,
                'text' => [
                    'text' => $content_post
                ],
                'content' => [
                    'contentEntities' => [
                        [
                            'entityLocation' => $image, // URL of the article
                            'thumbnails' => [
                                [
                                    'resolvedUrl' => $image// URL of the image
                                ]
                            ]
                        ]
                    ],
                    'title' => $title_post
                ],
                'distribution' => [
                    'linkedInDistributionTarget' => [
                        'visibleToGuest' => true // Set to true for public visibility
                    ]
                ]
            ]]);


        return $response ;
    }
}
