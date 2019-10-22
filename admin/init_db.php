<?php

$import = file_get_contents("init.sql");

   $import = preg_replace ("%/\*(.*)\*/%Us", '', $import);
   $import = preg_replace ("%^--(.*)\n%mU", '', $import);
   $import = preg_replace ("%^$\n%mU", '', $import);

   mysql_real_escape_string($import); 
   $import = explode (";", $import); 

   foreach ($import as $imp){
    if ($imp != '' && $imp != ' '){
     mysql_query($imp);
    }
   }  
   
?>
