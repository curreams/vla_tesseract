<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    
    public function checkIfValidPdf($email_body,$pdf_name)
    {
        return [$email_body,$pdf_name];
        
    }

}
