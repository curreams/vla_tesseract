<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;
use mikehaertl\pdftk\Pdf;


class ImageController extends Controller
{
    /**
     * Main function to convert the image to text
     */
    public function convertPdf(Request $request)
    {
        try {
            $result = [];
            $possible_names = [];
            $possible_DOBs = [];
            $args= self::getFileArgs($request);            
            if(self::splitPdf($args)){
                $image_path = self::convertPdfToImage($args);
            }
            if (isset($image_path) && !empty($image_path)) {
                foreach ($image_path as $image) {
                    $ocr = new TesseractOCR();
                    $ocr->image($image);
                    $text = $ocr->run();
                    $possible_names[] = self::searchClientName($text);
                    $possible_DOBs[] = self::searchClientDOB($text);
                    $result[] = nl2br($text);
                    
                }                
            }
            $possible_DOBs = array_flatten($possible_DOBs);
            dd($possible_DOBs);
            $possible_names = array_flatten($possible_names);
             if(!empty($result)){
                 self::cleanServerFiles($args, $result);                 
             }
             
            return view('result', compact('result'));
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    private function searchClientDOB($text)
    {
        $result = [];
        $dates = [];
        $date_pattern = "/\d{1,2}\/\d{1,2}\/\d{4}/";
        $data   = preg_split('/\s+/', strtolower($text));
        $keys_accused = array_keys($data,'accused');
        foreach ($keys_accused as  $key_value) {
            $sub_array = array_slice($data, $key_value, 30);
            $date_keys = array_keys($sub_array,'birth');
            foreach ($date_keys as $date_key) {
                $dates = preg_grep($date_pattern, $sub_array);
            }
            
        }
        foreach ($dates as $key => $date) {
            $result[] = preg_replace("/[^0-9\/-]/", "", $date);
        }
        return $result;

    }

    /**
     * 
     */
    private function searchClientName($text)
    {
        $possible_names = [];
        $commonwords = 'a,an,and,i,it,is,do,does,for,from,go,how,the,etc,name,family,given,names,sex,date,of,the,birth,prior,convictions,m,.,|,formant,iin,cee,nn,_,v,informant,accused,contravenes,permit,evidence,statement';
        $commonwords = explode(",", $commonwords);        
        $data   = preg_split('/\s+/', strtolower($text));
        $keys_accused = array_keys($data,'accused');
        foreach ($keys_accused as  $key_value) {
            $sub_array = array_slice($data, $key_value, 20);
            $name_keys = array_keys($sub_array,'name');
            foreach ($name_keys as $name_key) {
                $word1 = preg_replace("/[^a-zA-Z]/", "", $sub_array[$name_key + 1]);
                $word2 = preg_replace("/[^a-zA-Z]/", "", $sub_array[$name_key + 2]);
                if(!in_array($word1, $commonwords)){
                    $possible_names[] = $word1;
                }
                if(!in_array($word2, $commonwords)){
                    $possible_names[] = $word2;
                }
            }

           
        }
        return $possible_names;
        /*
        if($key_accused > 0) {
            $sub_array = array_slice($data, $key_accused, 19);   
            if(array_search('name', $sub_array)){
                foreach ($sub_array as $key => $world) {
                    if(!in_array($world, $commonwords)){
                        $query[] = $world;
                    }
                }
            }

        }
        preg_match_all($date_pattern,$text,$dates);
        dd($dates,$query);
        */
    }

    /**
     * After execution Clean the server files.
     */
     private function cleanServerFiles($args, $result) 
     {
         foreach ($result as $key => $text) {
             $index = $key + 1;
             $file_to_delete = $args['public_path'].$args['name'].$index.'.pdf';
             unlink($file_to_delete);
             $file_to_delete = $args['public_path'].$args['name'].$index.'.tiff';
             unlink($file_to_delete);             
         }
     }



    /**
     * 
     */
    private function getFileArgs($request)
    {
        $args=[];
        $fileName =  $request['upload_file']->getClientOriginalName();
        $name = basename($request['upload_file']->getClientOriginalName(), '.'.$request['upload_file']->getClientOriginalExtension());
        $public_path = app()->make('path.public').(DIRECTORY_SEPARATOR . "pdf") . DIRECTORY_SEPARATOR;
        $request['upload_file']->move($public_path, $fileName);
        $args['file_name'] = $fileName;
        $args['name'] = $name;
        $args['public_path'] = $public_path;
        $args['file_path'] = $public_path.$fileName;
        
        return $args;
    }
    /**
     * 
     */
    private function splitPdf($args) 
    {
        $pdf = new Pdf($args['file_path']);
        $result = $pdf->burst($args['public_path'].$args['name'].'%d.pdf');
        return $result;
    }
    /**
     * 
     */
    private function convertPdfToImage($args)
    {        
        $result=[];
        $imagick = new Imagick();
        $imagick->readImage($args['public_path'].$args['file_name']);
        $countPages = $imagick->getNumberImages();
        $imagick->resetIterator();    
        for ($i=1; $i <= $countPages ; $i++) {
            $path_to_file = $args['public_path'].$args['name'].$i;
            $image = new Imagick();
            $image->setOption('density','300');
            $image->readImage($path_to_file.'.pdf');
            $image->setImageDepth(8);
            $image->stripImage();
            $image->setImageBackgroundColor('white');
            //$image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);            
            $image->setImageFormat( "tiff" );
            if ($image->writeImage($path_to_file.'.tiff')){
                $result[] = $path_to_file.'.tiff';
            }
            
        }
        return $result;


    }


    public function testOCR($image_path)
    {
        $ocr = new TesseractOCR();
        $ocr->image($image_path);
        $text = $ocr->run();
        return $text;
    }


    public function testPdf($pdf_path)
    {
        $path = "/var/www/html/vla_tesseract/public/pdf/";
        $pdf = new Pdf($pdf_path);
        $result = $pdf->burst($path.'page_%d.pdf');
        dd($result);
        
    }


    public function testImagick($path)
    {
        $result=[];
        $public_path = app()->make('path.public').(DIRECTORY_SEPARATOR . "pdf");
        $base_path = $public_path.DIRECTORY_SEPARATOR;
        $imagick = new Imagick();
        $imagick->setOption('density','300');
        $imagick->readImage($path);
        $imagick->resetIterator();    
        foreach ($imagick as $key => $image) {
            $path = $base_path . $key.'.tiff';
            $image->setImageDepth(8);
            $image->stripImage();
            $image->setImageBackgroundColor('white');
            //$image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);            
            $image->setImageFormat( "tiff" );
            if ($image->writeImage($path)){
                $result[] = $path;
            }
        }
        //if($imagick->writeImages($path, false)){
            return $result;
    }


}
