<?php
if(!function_exists('sg_load')){$__v=phpversion();$__x=explode('.',$__v);$__v2=$__x[0].'.'.(int)$__x[1];$__u=strtolower(substr(php_uname(),0,3));$__ts=(@constant('PHP_ZTS') || @constant('ZEND_THREAD_SAFE')?'ts':'');$__f=$__f0='ixed.'.$__v2.$__ts.'.'.$__u;$__ff=$__ff0='ixed.'.$__v2.'.'.(int)$__x[2].$__ts.'.'.$__u;$__ed=@ini_get('extension_dir');$__e=$__e0=@realpath($__ed);$__dl=function_exists('dl') && function_exists('file_exists') && @ini_get('enable_dl') && !@ini_get('safe_mode');if($__dl && $__e && version_compare($__v,'5.2.5','<') && function_exists('getcwd') && function_exists('dirname')){$__d=$__d0=getcwd();if(@$__d[1]==':') {$__d=str_replace('\\','/',substr($__d,2));$__e=str_replace('\\','/',substr($__e,2));}$__e.=($__h=str_repeat('/..',substr_count($__e,'/')));$__f='/ixed/'.$__f0;$__ff='/ixed/'.$__ff0;while(!file_exists($__e.$__d.$__ff) && !file_exists($__e.$__d.$__f) && strlen($__d)>1){$__d=dirname($__d);}if(file_exists($__e.$__d.$__ff)) dl($__h.$__d.$__ff); else if(file_exists($__e.$__d.$__f)) dl($__h.$__d.$__f);}if(!function_exists('sg_load') && $__dl && $__e0){if(file_exists($__e0.'/'.$__ff0)) dl($__ff0); else if(file_exists($__e0.'/'.$__f0)) dl($__f0);}if(!function_exists('sg_load')){$__ixedurl='https://www.sourceguardian.com/loaders/download.php?php_v='.urlencode($__v).'&php_ts='.($__ts?'1':'0').'&php_is='.@constant('PHP_INT_SIZE').'&os_s='.urlencode(php_uname('s')).'&os_r='.urlencode(php_uname('r')).'&os_m='.urlencode(php_uname('m'));$__sapi=php_sapi_name();if(!$__e0) $__e0=$__ed;if(function_exists('php_ini_loaded_file')) $__ini=php_ini_loaded_file(); else $__ini='php.ini';if((substr($__sapi,0,3)=='cgi')||($__sapi=='cli')||($__sapi=='embed')){$__msg="\nPHP script '".__FILE__."' is protected by SourceGuardian and requires a SourceGuardian loader '".$__f0."' to be installed.\n\n1) Download the required loader '".$__f0."' from the SourceGuardian site: ".$__ixedurl."\n2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="\n3) Edit ".$__ini." and add 'extension=".$__f0."' directive";}}$__msg.="\n\n";}else{$__msg="<html><body>PHP script '".__FILE__."' is protected by <a href=\"https://www.sourceguardian.com/\">SourceGuardian</a> and requires a SourceGuardian loader '".$__f0."' to be installed.<br><br>1) <a href=\"".$__ixedurl."\" target=\"_blank\">Click here</a> to download the required '".$__f0."' loader from the SourceGuardian site<br>2) Install the loader to ";if(isset($__d0)){$__msg.=$__d0.DIRECTORY_SEPARATOR.'ixed';}else{$__msg.=$__e0;if(!$__dl){$__msg.="<br>3) Edit ".$__ini." and add 'extension=".$__f0."' directive<br>4) Restart the web server";}}$__msg.="</body></html>";}die($__msg);exit();}}return sg_load('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');
