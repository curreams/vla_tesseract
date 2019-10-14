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
        //Header
        $section = $phpWord->addSection();
        self::createDocumentHeader($section, $name);
        self::createDocumentFooter($section, $name);        
        $numItems = count($pages);
        $i = 0;
        foreach ($pages as $key => $page) {            
            $textlines = explode("\n", $page);
            $textrun = $section->addTextRun();            
            $textrun->addText('PAGE ' . strval($key + 1), ['size' => 18, 'bold' => true,"color" => "FF0000"]);
            $textrun->addTextBreak();
            foreach($textlines as $line) {
                $textrun->addTextBreak();
                $line = preg_replace("/\\\\n/"," ",strtolower($line));
                $textrun->addText(preg_replace("/[^a-zA-Z0-9|:\"\/\s+]/","",$line));
            }
            if(++$i !== $numItems) {
                $section->addLine(
                        array(
                        'width' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(12),
                        'height' => \PhpOffice\PhpWord\Shared\Converter::cmToPixel(0),
                        'positioning' => 'absolute','weight' => 2
                        )
                    );
                //$section->addPageBreak();
            }

        }
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($public_path.$name.'.docx');
        return $public_path.$name.'.docx';
    }

    private function createDocumentHeader($section, $name)
    {
        $header = $section->addHeader();
        $textRun = $header->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $textRun->addText("Transcript ".$name.".pdf",['size' => 10]);
    }
    private function createDocumentFooter($section)
    {
        $footer = $section->addFooter();
        $textRun = $footer->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $textRun->addField('PAGE',[]);
    }
}
