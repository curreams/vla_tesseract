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
     * Need to be pointed to the right Atlas Web Service
     */
    public function searchClient($combinations)
    {
        $possible_clients = [];
        $result = [];
        $client_obj = new Client();
        foreach ($combinations as $combination) {
            $response = $client_obj->getClient($combination);
            if(isset($response->Data)) {
                foreach ($response->Data as $client) {
                    $possible_clients[$client->AtlasClientID] = $client;
                }
            }
        }        
        return array_values($possible_clients);

    }


    /**
     * Find a possible DOB using a regex
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
     * Find the possible client names in the brief
     * 
     */
    public function findClientName($text)
    {
        $possible_names = [];
        $commonwords = getCommonWords();        
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

    public function findWitnessList($text, $args)
    {
        $witnesses= [];
              
        $data = preg_split('/\n+/', preg_replace("/[^a-zA-Z0-9\s+]/","",$text));
        $data_trimmed   =  array_map(function($line) {
                                return trim(strtolower($line));
                            },$data);

        $number_pattern = "/^[0-9]+/";
        $keys_witness = array_keys($data_trimmed,'witness list');
        foreach ($keys_witness as  $key_value) 
        {
            $names = self::getWitnessNames($args);

            $evidence = self::getEvidenceList($args);
            return $evidence;

            break;
        }
        return $witnesses;
        
    }

    private function getWitnessNames($args)    
    {
        $name_capital_pattern = "/([A-Z]{2,})\w+/";
        $name_lower_pattern = "/([A-Z0-9][a-z]{1,})\w+/";
        $cont_pos = 0;
        $test=0;
        $witness_names= [];
        // Crop Image
        $args["iw"] = 0;
        $args["ih"] = 360;
        $args["w"] = 850;
        $args["suffix"] = "names";
        
        $text = self::cropImage($args);
        // The witnesses names
        $data = preg_split('/\s+/', preg_replace("/[^a-zA-Z0-9\s+]/","",$text));
        
        $names = array_slice(array_map(function($line) {
                                                    return trim($line);
                                                },$data), 0);
                                                        

        // Check Spelling for some words.
        $names = array_map(function($word) use ($name_lower_pattern, $name_capital_pattern) {
                if(preg_match($name_lower_pattern,$word) != 1 && 
                    preg_match($name_capital_pattern,$word) != 1 &&
                    strlen($word) > 2 ){
                        $suggestions = self::checkSpelling($word);
                        if(isset($suggestions[0])){
                            return ucwords($suggestions[0]);
                        }
                    }
                else {
                    return $word;
                }    

            },$names);
        // Remove Words of 2 letters or less.
        
        $names = array_values(array_filter($names, function($word){
            return strlen($word) > 2 ? true : false;
        }));

        // Process each Name
        for($i=0; $i< count($names); $i++) {
            $temp = [];
            if(preg_match($name_lower_pattern,$names[$i]) == 1){
                $cont_pos++;
            }
            if((preg_match($name_capital_pattern,$names[$i]) == 1 
                && isset($names[$i + 1]) 
                && preg_match($name_lower_pattern,$names[$i+1]) == 1)
                ||
               (preg_match($name_capital_pattern,$names[$i]) == 1 
                && !isset($names[$i + 1]) )) {
                    $test++;
                    for($j = $i-$cont_pos; $j<=$i; $j++){
                        $temp[] = $names[$j];
                    }
                    $witness_names[] = implode(" ",$temp);
                    $cont_pos=0;
            }

        }

        return $witness_names;
        
    }

    function getEvidenceList($args)
    {
        $word_pattern = "/\b[Nn][A-Za-z]\b|\b[Yy][A-Za-z][A-Za-z]\b/";
        $single_word_pattern = "/^\b[A-Za-z0-9][A-Za-z0-9]\b|^\b[A-Za-z0-9]\b/";
        $args["iw"] = 820;
        $args["ih"] = 360;
        $args["w"] = 1400;
        $args["suffix"] = "evidences";
        $witness_evidence= [];
        
        $text = self::cropImage($args);
        $data = preg_split('/\n+/', preg_replace("/[^a-zA-Z0-9\s+]/","",$text));
        $evidences = array_map(function($line){
            return trim($line);
        }, $data);

        // First Solution -- Consist in see the reserved words

        // Filter undesirable words
        $evidences = array_values(array_filter($evidences, function($evidence) use($word_pattern, $single_word_pattern){
            if(preg_match($word_pattern, $evidence) || empty($evidence) || preg_match($single_word_pattern, $evidence)){
                return false;
            }
            return true;
        }));
        return $evidences;
        foreach ($evidences as $key => $line) {
            $temp = [];
            if(preg_match(getKeyWordsPattern(), $line)){
                $temp[] = $line;
                $index = $key;
                while(isset($evidences[$index + 1]) && preg_match(getKeyWordsPattern(), $evidences[$index + 1]) == 0)
                {
                    $temp[] = $evidences[$index + 1];
                    $index++;
                }
                $witness_evidence[] = implode(" ",$temp);
            }
        }



        return $witness_evidence;

    }

    function cropImage($args)
    {
        // Crop the image and run the ocr
        $ocr = new TesseractOCR();
        $image = new Imagick($args["file_name"]);
        $dimensions = $image->getImageGeometry();
        $image->cropImage($args["w"], $dimensions['height'], $args["iw"], $args["ih"]);        
        $names_path = $args['public_path'].$args['name'].$args["suffix"].'.tiff';
        $image->writeImage($names_path);
        $ocr->image($names_path);
        $text = $ocr->run();
        return $text;

    }
    /**
     * Find the possible informant names in the brief
     * 
     */
    public function findInformantName($text, $client_names)
    {
        $possible_names = [];
        $commonwords = getCommonWords();        
        $data   = preg_split('/\s+/', preg_replace("/[^a-zA-Z0-9\s+]/","",strtolower($text)));
        $keys_informant = array_keys($data,'informant');
        foreach ($keys_informant as  $key_value) {
            $sub_array = array_slice($data, $key_value, 20);
            $name_keys = array_keys($sub_array,'name');
            foreach ($name_keys as $key => $name_key) {
                $temp_name= [];
                for ($i=1; $i <= 3 ; $i++) {
                    if(isset($sub_array[$name_key + $i])){
                        $word = preg_replace("/[^a-zA-Z]/", "", $sub_array[$name_key + $i]);                    
                        if(strtolower($word) == 'coaccused' ){
                            break;
                        }
                        if(isset($word) && trim($word) != '' && !in_array($word, $commonwords)){
                            $temp_name[] = $word;
                        }
                    } 
                }
                if(!empty($temp_name)){
                    $implode_name =  implode(' ',$temp_name);
                    foreach ($client_names as $name) {
                        if($implode_name != $name){
                            $possible_names[] = $implode_name;
                        }
                    }
                }
            }           
        }
        //Search for duplicated names.

        return $possible_names;
        
    }

    public function findCharges($text)
    {
        $possible_charges = [];
        $commonwords = getCommonWordsOffences();
        $data = preg_replace("/[^a-zA-Z0-9:.\"\/\s+()]/","",strtolower($text));
        $pos_charge = strpos($data, 'details of the charge against');
        $pos_offence = strpos($data, 'offence literal');
        $pos_cont_charges = strpos($data, 'continuation of charges');
        preg_match_all("/offence code/",$data,$pos_offence_code, PREG_OFFSET_CAPTURE );
        preg_match_all("/act or regulation/",$data,$pos_regulations, PREG_OFFSET_CAPTURE );
        
        $result = [];
        if($pos_charge) {
            $temp_string = substr($data, $pos_charge, strpos($data,".\n",$pos_charge) - $pos_charge);
            $result["Charge"][] = substr($temp_string, strpos($temp_string, "1"));

        }
        if($pos_offence) {
            $substr = substr($data, $pos_offence);
            preg_match_all("/offence literal/",$substr,$matches_offences, PREG_OFFSET_CAPTURE );
            foreach (array_flatten($matches_offences) as $key => $match) {
                if(is_int($match)) {
                    $temp_string = substr($substr, $match, strpos($substr,".\n",$match) - $match);
                    $result["Offense"][] = trim(substr($temp_string, strpos($temp_string, ":") + 1, strpos($temp_string, "\n") - strpos($temp_string, ":") ));
                }
            }
           /* $temp_string = substr($data, $pos_offence, strpos($data,"\n",$pos_offence) - $pos_offence);            
            $result["Offense"][] = trim(substr($temp_string, strpos($temp_string, ":") + 1 ));*/

        }
        if($pos_cont_charges){
            $substr = substr($data, $pos_cont_charges);
            preg_match_all("/the accused/",$substr,$matches_charges, PREG_OFFSET_CAPTURE );
            foreach (array_flatten($matches_charges) as $key => $match) {
                if(is_int($match)) {
                    $temp_string = substr($substr, $match, strpos($substr,".\n",$match) - $match);
                    $result["Charge"][] = substr($temp_string, strpos($temp_string, "\n\d"));
                }
            }
        }
        if(!empty(array_flatten($pos_offence_code))){
            foreach (array_flatten($pos_offence_code) as $key => $pos_code) {
                if(is_int($pos_code)){
                    $substr = substr($data, $pos_code, 25);
                    preg_match("/([0-9])\w+/", $substr, $codes);
                    foreach ($codes as $key => $code) {
                        if(strlen($code) > 1 ){
                            $result["Code"][] = $code;
                        }
                    }
                }
            }
        }

        if (!empty(array_flatten($pos_regulations))) {
            foreach (array_flatten($pos_regulations) as $key => $pos_regulation) {
                if(is_int($pos_regulation)){
                    // Regulation Code
                    $temp_regulations=[];
                    $regulation="";
                    $substr = substr($data, $pos_regulation, 150);
                    $words = preg_split('/\s+/', $substr);
                    foreach ($words as $word) {
                        $word = preg_replace("/[^a-zA-Z]/", "", $word);
                        if(isset($word) && trim($word) != '' && !in_array($word, $commonwords)){
                            $temp_regulations[] = $word;
                        }
                    }
                    $regulation = implode(" ",$temp_regulations);
                    preg_match("/([0-9])\w+\/([0-9])\w+/", $substr, $regulation_numbers);
                    $regulation_numbers =   array_filter($regulation_numbers, function($number) {
                        return strlen($number) > 1;
                    });
                    $result["Regulation"][] = ucwords($regulation) . " ACT No " . implode(" ",$regulation_numbers);
                    // References
                    preg_match("/([0-9])\w+\(([0-9])\)\([a-z]\)|([0-9])\w+\(([0-9])\)\(([a-z])\w+\)|([0-9])\w+\(([0-9])\)|(?<=\s)\d+(?=\s)/", $substr, $references);
                    $reference_number = array_filter($references, function($number) {
                        return strlen($number) > 1;
                    });
                    $result["Reference"][] = implode(" ",$reference_number);
                }
            }

        }
        
        return $result;
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

        } else if (isset($rest_request->image_path)) {
            $fileName =  $rest_request->image_path;
            $name = basename($rest_request->image_path, '.tiff');

        } else  {
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
     * Split the pdf in one per page to make the image process easy
     */
    public function splitPdf($args) 
    {
        $pdf = new Pdf($args['file_path']);
        $result = $pdf->burst($args['public_path'].$args['name'].'%d.pdf');
        return $result;
    }
    /**
     * From each of the pdf create a readable image to Tesseract.
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

    private function checkSpelling($word)
    {
        $pspell_link = pspell_new("en");
        $suggestion = [];
        if (!pspell_check($pspell_link, $word)) {
            $suggestion = pspell_suggest($pspell_link, $word);
           //array_map(function($suggestion){

            //});
        } 
        return $suggestion;

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
