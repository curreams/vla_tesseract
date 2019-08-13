<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Imagick;


class ImageController extends Controller
{
    public function convertPdf(Request $request){
        try {
            $fileName =  $request['upload_file']->getClientOriginalName();
            $public_path = app()->make('path.public').(DIRECTORY_SEPARATOR. "pdf");
            $request['upload_file']->move($public_path, $fileName);
            $path = $public_path.DIRECTORY_SEPARATOR;
            $imagick = new Imagick($path.$fileName);
            dd($imagick);
            $imagick->setResolution(300,300);
            $imagick->writeFile($path.'pageone.jpg');

            //$result = $imagick->readImage($path);
            //return $imagick;
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function testImage(){
        $imagick = new Imagick();
        $imagick->setResolution(300,300);
        //return $imagick;
        $result = $imagick->readImage("C:\Users\sc9614\Pictures\Camera Roll\document_20190805.pdf");
        return $result;
    }

}
