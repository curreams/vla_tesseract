<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Email;

class EmailController extends Controller
{
    public function checkIfPdfSignature($request){
        $email_obj = new Email();
        $result = $email_obj->checkIfValidPdf($request['email_body'],$request['pdf_name']);
        return $result;
    }
}
