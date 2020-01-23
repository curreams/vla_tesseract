<?php

namespace App;



class Client extends ClientClient
{


    public function getClient($combination)
    {
        $url = 'client/getclientOCR';
        $result = $this->client->get($url, $combination);
        return $result;
    }
    
}
