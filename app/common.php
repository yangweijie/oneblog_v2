<?php
// 应用公共文件
if(!defined('IS_VERCEL')){
    define('IS_VERCEL', $_ENV['VERCEL']??0);
}
