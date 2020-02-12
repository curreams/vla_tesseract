<?php

    /**
     * Get cartesian product
     *
     * @return string
     */
if (! function_exists('getCommonWords')) {
    function getCommonWords()
    {
        $commonwords = 'a,an,and,i,it,is,do,does,for,from,go,how,the,etc,name,family,given,names,sex,date,of,the,birth,prior,convictions,m,.,|,formant,iin,cee,nn,_,v,informant,accused,contravenes,permit,evidence,statement,-,dob,document,male,age,please,\n,rank,gnien,hames,constable, senior';
        $commonwords = explode(",", $commonwords);
        return $commonwords;
        //cartesian_product($array1)->asArray();
    }
}

if (! function_exists('getCommonWordsOffences')) {
    function getCommonWordsOffences()
    {
        $commonwords = 'a,an,i,it,is,do,does,for,from,go,how,the,etc,name,given,names,sex,date,of,the,birth,prior,convictions,m,.,|,formant,iin,cee,nn,_,v,informant,accused,contravenes,permit,evidence,statement,-,dob,document,male,age,please,\n,act,or,regulation,sectionclause,full,you,should,court,but,must,nee,su,ref,no,to,under,what,law,licwealth,reg,cwealth,summary,offence,type,cweaith,cl,bb,y,indictable,o,a,e,i,u,b,c,d,f,g,h,j,k,l,m,n,p,q,r,s,t,v,w,x,y,z,lcwealth,iv,offenceyou,yo,lcwealth,oa,wealth,';
        $commonwords = explode(",", $commonwords);
        return $commonwords;
        //cartesian_product($array1)->asArray();
    }
}


if (! function_exists('getKeyWordsPattern')) {
    function getKeyWordsPattern()
    {
        $pattern = "/^(Witness|witness|Informant|informant|Corroborator|corroborator|Protective Services Officer)/";
        return $pattern;
        //cartesian_product($array1)->asArray();
    }
}


 



