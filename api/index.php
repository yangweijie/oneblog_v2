<?php
/**
 * Here is the serverless function entry
 * for deployment with Vercel.
 */

var_dump($_ENV);
die;
if(stripos($_SERVER['REQUEST_URI'], 'admin.php') !== false){
    require __DIR__.'/../public/admin.php';
}else{
    require __DIR__.'/../public/index.php';
}
