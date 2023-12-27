# ThinkPHP Package的创建

## 首先是一个 composer 包
composer.json

## 特殊的定义
composer.json中定义

~~~
"extra": {
    "think": {
        "services": [
            "think\\debugbar\\Service"
        ],
        "config":{
            "captcha": "src/config.php"
        }  
    }
  },
~~~
