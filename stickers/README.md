# 表情图片目录

`manifest.json` 是表情分类清单。每项只需填写显示名称 `name` 和对应文件夹 `directory`；分类中无需逐张登记图片。未写入清单的文件夹不会出现在评论表情面板中。

清单级 `use_filename_as_title` 控制是否把图片文件名用作悬浮标题，默认且建议保持 `false`。某个分类确实需要显示文件名时，可以在该分类项中单独写入 `"use_filename_as_title": true` 覆盖全局默认值。`description` 用于显示分类来源等说明，例如 Flower 分类的来源也直接写在清单中。

支持的图片格式为 JPG、PNG、GIF、WebP 和 AVIF。SVG 因可能包含可执行内容而不加载。图片文件名会参与生成评论中的表情代码，因此图片上线后不应随意改名；原有小恐龙和花表情代码已做兼容。

Apache 会自动读取本目录的 `.htaccess`，禁止目录索引、地址栏直开和跨站盗链。Nginx 不读取 `.htaccess`，需要将 `nginx-hotlink.conf.example` 的内容放入当前站点的 `server {}` 后重载。图片 URL 中的 `?v=` 只负责缓存失效，并不是安全令牌；防盗链依据请求来源判断，因此站内页面能加载图片，而地址栏直开和外站引用返回 403。主题输出 `strict-origin-when-cross-origin` 策略，使同一站点配置的多个域名也能把来源域名交给 Web 服务器校验。两种方案都由 Web 服务器直接处理并保留一年静态缓存，不经过 PHP 图片代理。
