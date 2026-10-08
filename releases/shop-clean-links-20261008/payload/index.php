<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$routes=['/'=>'index.php','/shop'=>'index.php','/login'=>'login.php','/register'=>'register.php','/logout'=>'logout.php'];
$pages=['/about'=>'about','/contact'=>'contact','/help'=>'help','/privacy'=>'privacy','/terms'=>'terms'];
$path=$path==='/'?'/':rtrim($path,'/');
if(isset($pages[$path])){$_GET['page']=$pages[$path];$file='page.php';}
elseif(isset($routes[$path])){$file=$routes[$path];}
else{http_response_code(404);exit('Page not found');}
$_SERVER['SCRIPT_NAME']='/b2c-preview/'.$file;
require __DIR__.'/b2c-preview/'.$file;
