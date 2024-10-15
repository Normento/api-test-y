<?php

namespace Core\ExternalServices;

use Webklex\IMAP\Facades\Client;

class  ImapService
{

    public static function connection()
    {
        $client = Client::account("default");
        return $client->connect();

    }
}
