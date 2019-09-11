<?php

namespace App;



class Client extends ClientClient
{


    public function getClient($combination)
    {
        $url = 'client/';
        $result = $this->client->get($url, $combination);
        return $result;
    }
    
}
