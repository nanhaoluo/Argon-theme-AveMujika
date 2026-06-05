<?php
/**
 * Argon 主题彩蛋系统
 * 支持在特定日期触发自定义网页效果
 */

// 获取所有可用的彩蛋效果文件
function argon_get_available_easter_eggs() {
    $easter_egg_dir = get_template_directory() . '/easter-egg';
    $eggs = array();
    
    if (!is_dir($easter_egg_dir)) {
        return $eggs;
    }
    
    $files = glob($easter_egg_dir . '/*.php');
    foreach ($files as $file) {
        $id = basename($file, '.php');
        $egg_data = include $file;
        
        if (is_array($egg_data)) {
            $egg_data['id'] = $id;
            $egg_data['file'] = $file;
            $eggs[$id] = $egg_data;
        }
    }
    
    return $eggs;
}

// 获取彩蛋配置（从数据库）
function argon_get_easter_egg_config($egg_id) {
    $configs = get_option('argon_easter_egg_configs', array());
    
    if (isset($configs[$egg_id])) {
        return $configs[$egg_id];
    }
    
    // 返回默认配置
    $available_eggs = argon_get_available_easter_eggs();
    if (isset($available_eggs[$egg_id])) {
        return array(
            'enabled' => false,
            'date' => $available_eggs[$egg_id]['default_date'],
            'type' => $available_eggs[$egg_id]['default_type'],
            'duration' => 1
        );
    }
    
    return null;
}

// 保存彩蛋配置
function argon_save_easter_egg_config($egg_id, $config) {
    $configs = get_option('argon_easter_egg_configs', array());
    $configs[$egg_id] = $config;
    update_option('argon_easter_egg_configs', $configs);
    argon_clear_easter_egg_cache();
}

// 检查今天是否匹配某个彩蛋日期
function argon_check_easter_egg() {
    $available_eggs = argon_get_available_easter_eggs();
    $today_solar = date('m-d');
    $today_timestamp = strtotime(date('Y-m-d'));
    
    foreach ($available_eggs as $egg_id => $egg_data) {
        $config = argon_get_easter_egg_config($egg_id);
        
        if (!$config || !isset($config['enabled']) || !$config['enabled']) {
            continue;
        }
        
        $match = false;
        
        if ($config['type'] === 'solar') {
            $egg_date = strtotime(date('Y') . '-' . $config['date']);
            $duration = isset($config['duration']) ? intval($config['duration']) : 1;
            
            if ($today_timestamp >= $egg_date && $today_timestamp < $egg_date + ($duration * 86400)) {
                $match = true;
            }
        } elseif ($config['type'] === 'lunar') {
            $lunar_date = argon_get_lunar_date();
            if ($lunar_date === $config['date']) {
                $match = true;
            }
        }
        
        if ($match) {
            return array_merge($egg_data, $config);
        }
    }
    
    return null;
}

// 简单的农历日期获取
function argon_get_lunar_date() {
    return apply_filters('argon_lunar_date', null, date('Y'), date('m'), date('d'));
}

// 在页面头部输出彩蛋样式和脚本
function argon_output_easter_egg() {
    $cache_key = 'argon_easter_egg_' . date('Y-m-d');
    $egg = wp_cache_get($cache_key);
    
    if ($egg === false) {
        $egg = argon_check_easter_egg();
        wp_cache_set($cache_key, $egg ? $egg : 'none', '', 3600);
    }
    
    if ($egg && $egg !== 'none') {
        if (!empty($egg['css'])) {
            echo "\n<!-- Argon Easter Egg: " . esc_html($egg['name']) . " -->\n";
            echo '<style id="argon-easter-egg-css">' . $egg['css'] . '</style>' . "\n";
        }
        
        if (!empty($egg['js'])) {
            echo '<script id="argon-easter-egg-js">' . $egg['js'] . '</script>' . "\n";
        }
        
        do_action('argon_easter_egg_activated', $egg);
    }
}
add_action('wp_head', 'argon_output_easter_egg', 999);

// 清除彩蛋缓存
function argon_clear_easter_egg_cache() {
    $cache_key = 'argon_easter_egg_' . date('Y-m-d');
    wp_cache_delete($cache_key);
}
