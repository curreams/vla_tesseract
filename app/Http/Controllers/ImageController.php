<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;
use mikehaertl\pdftk\Pdf;
use App\Client;

use function BenTools\CartesianProduct\cartesian_product;




class ImageController extends Controller
{

    
    /**
     * Main function to read the pdf 
     */
    public function parseDocument(Request $request)
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
                    $temp_name = [];
                    $ocr = new TesseractOCR();
                    $ocr->image($image);
                    $text = $ocr->run();
                    $temp_name = self::findClientName($text);
                    foreach ($temp_name as $name) {
                        $possible_names[] = $name;
                    }
                    $possible_DOBs[] = self::findClientDOB($text);
                    $result[] = nl2br($text);                    
                }                
            }
            $possible_DOBs = array_flatten($possible_DOBs);

            $combinations = self::makeCombinations($possible_names ,$possible_DOBs);
            $possibleClients = self::searchClient($combinations);

             if(!empty($result)){
                 self::cleanServerFiles($args, $result);                 
             }
             $validate_request = json_decode($request->getContent());
             if(isset($validate_request->file_name)){
                return response()->json(["data"=> $result,  "possible_clients"=> $possibleClients]);
             } 
             return view('result', compact('result','possibleClients'));
             
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }


    /**
     * Do a cartesian product to know the possible combination when search a client. 
     */
    public function makeCombinations($possible_names ,$possible_DOBs)
    {
        
        $data = [
            'Name' => !empty($possible_names) ? $possible_names : [""],
            'DOBStart'=>!empty($possible_DOBs) ? $possible_DOBs : [""],
            'DOBEnd'=>!empty($possible_DOBs) ? $possible_DOBs : [""]
        ];
        return cartesian_product($data)->asArray();
    }

    /**
     * Consult the Atlas Web service for search the possible clients. 
     */
    public function searchClient($combinations)
    {
        $possible_clients = [];
        $client_obj = new Client();
        foreach ($combinations as $combination) {
            $response = $client_obj->getClient($combination);
            if(isset($response->Data)) {
                foreach ($response->Data as $client) {
                    $possible_clients[$client->ClientId] = $client;
                }
            }
        }
        return $possible_clients;

    }


    /**
     * Find a possible DOB
     */
    public function findClientDOB($text)
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
    public function findClientName($text)
    {
        $possible_names = [];
        $commonwords = 'a,an,and,i,it,is,do,does,for,from,go,how,the,etc,name,family,given,names,sex,date,of,the,birth,prior,convictions,m,.,|,formant,iin,cee,nn,_,v,informant,accused,contravenes,permit,evidence,statement,-,dob,document,male,age,please';
        $commonwords = explode(",", $commonwords);        
        $data   = preg_split('/\s+/', preg_replace("/[^a-zA-Z0-9\s+]/","",strtolower($text)));
        $keys_accused = array_keys($data,'accused');
        foreach ($keys_accused as  $key_value) {
            $sub_array = array_slice($data, $key_value, 20);
            $name_keys = array_keys($sub_array,'name');
            foreach ($name_keys as $key => $name_key) {
                $temp_name= [];
                for ($i=1; $i <= 3 ; $i++) {
                    if(isset($sub_array[$name_key + $i])){
                        $word = preg_replace("/[^a-zA-Z]/", "", $sub_array[$name_key + $i]);                    
                        if(strtolower($word) == 'informant' ){
                            break;
                        }
                        if(isset($word) && trim($word) != '' && !in_array($word, $commonwords)){
                            $temp_name[] = $word;
                        }
                    } 
                }
                if(!empty($temp_name)){
                    $possible_names[] = implode(' ',$temp_name);
                }
            }           
        }
        return $possible_names;
        
    }

    /**
     * After execution Clean the server files.
     */
     public function cleanServerFiles($args, $result) 
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
     *  File Args When function is called from UI
     */
    public function getFileArgs($request)
    {
        $args=[];
        $public_path = app()->make('path.public').(DIRECTORY_SEPARATOR . "pdf") . DIRECTORY_SEPARATOR;
        $rest_request = json_decode($request->getContent());
        if(isset($rest_request->file_name)){
            $fileName =  $rest_request->file_name;
            $name = basename($rest_request->file_name, '.pdf');

        } else {
            $fileName =  $request['upload_file']->getClientOriginalName();
            $name = basename($request['upload_file']->getClientOriginalName(), '.'.$request['upload_file']->getClientOriginalExtension());
            $request['upload_file']->move($public_path, $fileName);
        }
        $args['file_name'] = $fileName;
        $args['name'] = $name;
        $args['public_path'] = $public_path;
        $args['file_path'] = $public_path.$fileName;
        
        return $args;
    }



    /**
     * 
     */
    public function splitPdf($args) 
    {
        $pdf = new Pdf($args['file_path']);
        $result = $pdf->burst($args['public_path'].$args['name'].'%d.pdf');
        return $result;
    }
    /**
     * 
     */
    public function convertPdfToImage($args)
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

///////////////// Test Functions //////////////////////////////////
    public function testOCR($image_path)
    {
        $ocr = new TesseractOCR();
        $ocr->image($image_path);
        $text = $ocr->run();
        return $text;
    }

    public static function apiResponse()
    {
        return "Hello World";
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
