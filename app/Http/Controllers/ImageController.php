<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class ImageController extends Controller
{
    public function convertPdf(Request $request){
        $file =  $request['upload_file'];
        dd($file);
    }
}
