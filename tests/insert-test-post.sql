INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_type, comment_status, ping_status, post_excerpt, post_content_filtered, to_ping, pinged, post_modified, post_modified_gmt)
VALUES (1, NOW(), UTC_TIMESTAMP(),
'<h2>宽表格测试</h2>
<p>下面是一个很宽的表格，修复前会在手机上溢出卡片：</p>
<table>
<thead>
<tr><th>角色</th><th>乐队职务</th><th>就读学校</th><th>身高</th><th>生日</th><th>代表色</th><th>备注说明（很长的列）</th></tr>
</thead>
<tbody>
<tr><td>丰川祥子</td><td>键盘手</td><td>羽丘女子学园</td><td>155cm</td><td>2月14日</td><td>#7799CC</td><td>Oblivionis，Ave Mujica 的键盘手与领袖</td></tr>
<tr><td>三角初华</td><td>吉他手/主唱</td><td>花咲川女子学园</td><td>160cm</td><td>6月26日</td><td>#F2B8C6</td><td>Doloris，sumimi 成员，负责主唱</td></tr>
<tr><td>八幡海铃</td><td>贝斯手</td><td>花咲川女子学园</td><td>158cm</td><td>4月7日</td><td>#71A6D2</td><td>Timoris，冷静可靠的贝斯手</td></tr>
<tr><td>祐天寺若麦</td><td>鼓手</td><td>羽丘女子学园</td><td>150cm</td><td>12月25日</td><td>#B68EC9</td><td>Amoris，网络偶像喵梦</td></tr>
<tr><td>若叶睦</td><td>吉他手</td><td>羽丘女子学园</td><td>156cm</td><td>10月8日</td><td>#81C784</td><td>Mortis，沉默寡言的吉他手</td></tr>
</tbody>
</table>
<h2>超宽表格（十列）</h2>
<table>
<tbody>
<tr><td>列一列一列一</td><td>列二列二列二</td><td>列三列三列三</td><td>列四列四列四</td><td>列五列五列五</td><td>列六列六列六</td><td>列七列七列七</td><td>列八列八列八</td><td>列九列九列九</td><td>列十列十列十</td></tr>
<tr><td>aaaaaaaaaaaaaaaaaaaa</td><td>bbbbbbbbbbbbbbbbbbbb</td><td>cccccccccccccccccccc</td><td>dddddddddddddddddddd</td><td>eeeeeeeeeeeeeeeeeeee</td><td>ffffffffffffffffffff</td><td>gggggggggggggggggggg</td><td>hhhhhhhhhhhhhhhhhhhh</td><td>iiiiiiiiiiiiiiiiiiii</td><td>jjjjjjjjjjjjjjjjjjjj</td></tr>
</tbody>
</table>
<h2>长英文串与长链接</h2>
<p>SupercalifragilisticexpialidociousAntidisestablishmentarianismPneumonoultramicroscopicsilicovolcanoconiosis</p>
<p>https://example.com/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa</p>
<h2>窄表格（不应被拉宽）</h2>
<table>
<tbody>
<tr><td>键</td><td>值</td></tr>
<tr><td>foo</td><td>bar</td></tr>
</tbody>
</table>
<p>结尾段落。</p>',
'移动端文章内容溢出测试', 'publish', 'mobile-overflow-test', 'post', 'closed', 'closed', '', '', '', '', NOW(), UTC_TIMESTAMP());
SELECT ID FROM wp_posts WHERE post_name='mobile-overflow-test';
