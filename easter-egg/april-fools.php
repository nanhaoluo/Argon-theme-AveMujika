<?php
/**
 * 愚人节彩蛋效果
 * 文件名作为彩蛋标识
 */

return array(
    'name' => '愚人节',
    'description' => '页面元素随机旋转效果',
    'default_date' => '04-01',
    'default_type' => 'solar',
    'css' => '
        @keyframes argon-april-fools {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(2deg); }
            75% { transform: rotate(-2deg); }
            100% { transform: rotate(0deg); }
        }
        .card, .btn, img {
            animation: argon-april-fools 3s ease-in-out infinite;
        }
    ',
    'js' => '
        console.log("🎉 愚人节快乐！");
    '
);
