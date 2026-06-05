<?php
/**
 * 清明节彩蛋效果
 */

return array(
    'name' => '清明节',
    'description' => '全网页黑白效果',
    'default_date' => '04-04',
    'default_type' => 'solar',
    'css' => '
        html {
            filter: grayscale(100%);
            -webkit-filter: grayscale(100%);
        }
    ',
    'js' => ''
);
