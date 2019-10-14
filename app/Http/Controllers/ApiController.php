<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\DocumentController;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ApiController extends Controller
{

    /** Process the File using a single Call. Suitable for Small Files */
    public function processPDF(Request $request)
    {
        try {
            $validate_request = json_decode($request->getContent());
            if(isset($validate_request->file_name)){
                $image_obj = new ImageController();
                return $image_obj->parseDocument($request);                
            }
            return response()->json(['error'=> 'file_name is mandatory']);

            
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }

    }


    public function parseArgsAndSplit(Request $request)
    {
        try {
            $validate_request = json_decode($request->getContent());
            if(isset($validate_request->file_name)){
                $image_obj = new ImageController();
                $args= $image_obj->getFileArgs($request);
                if($image_obj->splitPdf($args)){
                    $images_path = $image_obj->convertPdfToImage($args);
                    return response()->json($images_path);
                }
                return response()->json(['error'=> 'No possible to create PDF files.']);
            }
            return response()->json(['error'=> 'file_name is mandatory']);

            
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }


        
    }

    public function parseEachImage(Request $request)
    {
        try {
            $possible_names = [];
            $possible_DOBs = [];
            $charges_offences = [];
            $possible_offences = [];
            $possible_charges = [];

            $validate_request = json_decode($request->getContent());
            if(isset($validate_request->image_path)){
                $image_obj = new ImageController();
                $temp_name = [];
                $ocr = new TesseractOCR();
                $ocr->image($validate_request->image_path);
                $text = $ocr->run();
                $charges_offences = $image_obj->findCharges($text);
                if (array_key_exists('Charge', $charges_offences)) {
                    foreach ($charges_offences['Charge'] as $charge) {
                        $possible_charges[] = $charge;                        
                    }
                }
                if (array_key_exists('Offense', $charges_offences)) {
                    foreach ($charges_offences['Offense'] as $offence) {
                        $possible_offences[] = $offence;                        
                    }
                }
                $temp_name = $image_obj->findClientName($text);
                foreach ($temp_name as $name) {
                    $possible_names[] = $name;
                }
                $possible_DOBs[] = $image_obj->findClientDOB($text);
                $possible_DOBs = array_flatten($possible_DOBs);
                $text = preg_replace('/\n\n\s+/', '\n\n', $text) . "\n\n";
                return  response()->json(["result"=> $text,  
                                          "possible_DOBs"=> implode(", ",$possible_DOBs), 
                                          "possible_names"=> implode (", ", $possible_names),
                                          "possible_charges" => $possible_charges,
                                          "possible_offences"=> $possible_offences]);

            }
           return response()->json(['error'=> 'image_path is mandatory']);
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }
    }

    public function searchClient(Request $request)
    {
        try {
            $possible_names = [];
            $possible_dobs = [];
            $possibleClients = [];          
            $validate_request = json_decode($request->getContent());
            if(isset($validate_request->possible_names)  &&  isset($validate_request->possible_DOBs)){
                $image_obj = new ImageController();
                foreach ($validate_request->possible_names as $possible_name) {
                    $temp_array = explode(',', $possible_name);
                    foreach ($temp_array as $name) {
                        if(!empty($name)) {
                            $possible_names[] = trim($name);
                        }
                    }
                }
                foreach ($validate_request->possible_DOBs as $possible_dob) {
                    if(!empty($possible_dob)){
                        $possible_dobs[] = $possible_dob;
                    }
                }
                $combinations = $image_obj->makeCombinations($possible_names ,$possible_dobs);
                $possibleClients = $image_obj->searchClient($combinations);

                return response()->json($possibleClients);

            }
            return response()->json(['error'=> 'possible_names and possible_DOBs are mandatory']);
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }

    }

   /**
     * After execution Clean the server files.
     */
    public function cleanServerFiles(Request $request) 
    {
        try {
            $public_path = app()->make('path.public').(DIRECTORY_SEPARATOR . "pdf") . DIRECTORY_SEPARATOR;
            $validate_request = json_decode($request->getContent());
            if(isset($validate_request->args) && isset($validate_request->original)){
                foreach ($validate_request->args as $image) {
                    $pdf = str_replace(".tiff",".pdf",$image);
                    unlink($image);            
                    unlink($pdf);             
                }
                unlink($public_path.$validate_request->original);
                return response()->json(['Ok'=> 'Files Cleaned']);
            }
            return response()->json(['error'=> 'args is mandatory']);
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }
    }

    /**
     * Get Vla Address from the CC text
     */
    public function getVLAEmail(Request $request)
    {
        try{
            $result="";
            $vla_emails = [];
            if(isset($request["CCText"])){
                $emails = explode(";",$request["CCText"]);
                foreach ($emails as $email) {
                    if(strpos($email, '@vla.vic.gov.au') !== false) {
                        $vla_emails[] = $email;
                    }
                }
                if(!empty($vla_emails)){
                   $result = implode (";", $vla_emails);
                }

            }
            return response()->json($result);
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }
    }

    public function setEmailAttachment(Request $request){
        try {
            $validate_request = json_decode($request->getContent());
            if(isset($validate_request->pages) && isset($validate_request->name)){
                $document_obj = new DocumentController();
                $name = basename($validate_request->name, '.pdf');
                $attachment  = $document_obj->createDocument($validate_request->pages, $name);
                return response()->json($attachment);
                
            }
            return response()->json(['error'=> 'pages and name are mandatory']);
        
        } catch (\Exception $ex) {
            return response()->json(['error'=>$ex instanceof \Illuminate\Validation\ValidationException ? 
                                            implode(" ",array_flatten($ex->errors())) : $ex->getMessage()],500);
        }


    }

}
