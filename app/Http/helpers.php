<?php

    /**
     * Get cartesian product
     *
     * @return string
     */
if (! function_exists('getCommonWords')) {
    function getCommonWords()
    {
        $commonwords = 'a,an,and,i,it,is,do,does,for,from,go,how,the,etc,name,family,given,names,sex,date,of,the,birth,prior,convictions,m,.,|,formant,iin,cee,nn,_,v,informant,accused,contravenes,permit,evidence,statement,-,dob,document,male,age,please';
        $commonwords = explode(",", $commonwords);
        return $commonwords;
        //cartesian_product($array1)->asArray();
    }
}