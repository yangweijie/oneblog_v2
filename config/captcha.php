<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 验证码设置
// +----------------------------------------------------------------------

return [
    // 验证码密钥
    'seKey'    => 'DolphinPHP.COM',
    'codeSet'  => '1234567890',
    // 验证码图片高度
    'imageH'   => 40,
    // 验证码图片宽度
    'imageW'   => 130,
    // 验证码字体大小(px)
    'fontSize' => 18,
    // 验证码位数
    'length'   => 3,
    'useNoise'=>false,
    'useCurve'=>false,
];
