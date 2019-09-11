<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientClient extends Model
{
    protected $client;

    function __construct()
    {
        $this->client = (new \App\Repositories\ClientAPI);
    }
}
