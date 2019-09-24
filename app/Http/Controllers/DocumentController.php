<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * Create the word document for the transcript
     */
    public function createDocument($pages, $name){
        $public_path = app()->make('path.public').(DIRECTORY_SEPARATOR . "pdf") . DIRECTORY_SEPARATOR;
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $textrun = $section->addTextRun();
        $numItems = count($pages);
        $i = 0;
        foreach ($pages as $page) {
            $text = $page;
            $textlines = explode("\n", $text);
            $textrun = $section->addTextRun();
            $textrun->addText(array_shift($textlines));
            foreach($textlines as $line) {
                $line = preg_replace("/\\\\n/"," ",strtolower($line));
                $textrun->addTextBreak();
                $textrun->addText(preg_replace("/[^a-zA-Z0-9|:\"\s+]/","",$line));
                
            }
            if(++$i !== $numItems) {        
                $section->addPageBreak();
            }

        }
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($public_path.$name.'.docx');
        return $public_path.$name.'.docx';
    }
}
