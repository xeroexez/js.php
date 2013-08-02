<?php
 
 /**
 * Add lcfirst
 * @return string
 */
 
if(!function_exists('lcfirst')){

    function lcfirst( $sStr ) {
        $sStr[0] = strtolower($sStr[0]);
        return (string)$sStr;
    }
}

 /**
 * See if mbstring compiled/enabled 
 * 
 * 
 */
if (!extension_loaded('mbstring')) {
	if((PHP_OS == 'WINNT') && (@dl('php_mbstring'))){
	 		return;
	}
	throw new Exception("Enable mbstring extension");
}
