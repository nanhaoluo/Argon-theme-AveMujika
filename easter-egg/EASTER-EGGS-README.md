# Argon 彩蛋系统使用说明

## 系统架构

彩蛋系统采用文件+配置分离的设计：
- **彩蛋效果文件**：存放在 `easter-egg/` 目录，定义效果的 CSS 和 JS
- **彩蛋配置**：存储在数据库中，管理员在设置页配置日期和启用状态
- **自动 ID**：系统根据文件名自动分配 ID

## 已有彩蛋效果

1. **清明节** (`qingming.php`) - 全网页黑白效果
4. **愚人节** (`april-fools.php`) - 页面元素随机旋转

## 创建新彩蛋效果

在 `easter-egg/` 目录创建 PHP 文件，文件名即为彩蛋 ID：

```php
<?php
return array(
    'name' => '彩蛋名称',
    'description' => '效果描述',
    'default_date' => 'MM-DD',
    'default_type' => 'solar', // solar 或 lunar
    'css' => '/* CSS 代码 */',
    'js' => '/* JS 代码 */'
);
```

## 配置参数说明

- `name`: 彩蛋显示名称
- `description`: 效果描述（在设置页显示）
- `default_date`: 默认触发日期（格式：MM-DD）
- `default_type`: 默认日期类型（solar=阳历，lunar=农历）
- `css`: 自定义 CSS 样式
- `js`: 自定义 JavaScript 代码

## 管理员配置

管理员在主题设置页可以：
- 查看所有可用彩蛋效果
- 启用/禁用彩蛋
- 设置触发日期
- 设置日期类型（阳历/农历）
- 设置持续天数

## 核心函数

- `argon_get_available_easter_eggs()` - 获取所有可用彩蛋
- `argon_get_easter_egg_config($egg_id)` - 获取彩蛋配置
- `argon_save_easter_egg_config($egg_id, $config)` - 保存彩蛋配置
- `argon_clear_easter_egg_cache()` - 清除缓存

## 示例效果

### 全网页黑白
```css
html {
    filter: grayscale(100%);
    -webkit-filter: grayscale(100%);
}
```

### 红色主题
```css
:root {
    --primary-color: #d32f2f !important;
}
```

### 动画效果
```css
@keyframes shake {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(2deg); }
    75% { transform: rotate(-2deg); }
}
.card { animation: shake 3s infinite; }
```
