DELETE FROM wp_posts WHERE post_name = 'code-highlight-test';

INSERT INTO wp_posts (
    post_author,
    post_date,
    post_date_gmt,
    post_content,
    post_title,
    post_status,
    post_name,
    post_type,
    comment_status,
    ping_status,
    post_excerpt,
    post_content_filtered,
    to_ping,
    pinged,
    post_modified,
    post_modified_gmt
) VALUES (
    1,
    NOW(),
    UTC_TIMESTAMP(),
    '<h2>1. 经典与系统编程（Rust / Zig / Go / C++）</h2>
<p>测试底层语言的高亮与关键字识别：</p>
<pre><code class="language-rust">// Rust：并发与模式匹配
fn main() {
    let numbers = vec![1, 2, 3, 4, 5];
    let sum: i32 = numbers.iter().sum();
    println!("Total sum: {}", sum);
}
</code></pre>

<pre><code class="language-zig">// Zig：系统编程与错误集处理
const std = @import("std");

pub fn main() !void {
    const stdout = std.io.getStdOut().writer();
    try stdout.print("Hello, {s}!\\n", .{"Zig Language"});
}
</code></pre>

<pre><code class="language-golang">// Go：并发 Channel
package main

import (
    "fmt"
    "time"
)

func worker(id int, ch chan string) {
    time.Sleep(time.Millisecond * 50)
    ch <- fmt.Sprintf("worker %d done", id)
}

func main() {
    ch := make(chan string)
    go worker(1, ch)
    fmt.Println(<-ch)
}
</code></pre>

<pre><code class="language-c++">// C++20：概念约束与模板
#include &lt;iostream&gt;
#include &lt;concepts&gt;

template&lt;typename T&gt;
concept Numeric = std::integral&lt;T&gt; || std::floating_point&lt;T&gt;;

template&lt;Numeric T&gt;
T add(T a, T b) {
    return a + b;
}

int main() {
    std::cout &lt;&lt; add(10, 20) &lt;&lt; std::endl;
    return 0;
}
</code></pre>

<h2>2. 现代脚本与 Web 栈（Python / TypeScript / Lua）</h2>
<pre><code class="language-python"># Python：异步协程与数据类
from dataclasses import dataclass
import asyncio

@dataclass
class User:
    name: str
    active: bool = True

async def fetch_user(name: str) -> User:
    await asyncio.sleep(0.1)
    return User(name=name)

asyncio.run(fetch_user("Argon"))
</code></pre>

<pre><code class="language-typescript">// TypeScript：泛型接口与异步拉取
interface ApiResponse&lt;T&gt; {
    code: number;
    data: T;
    message?: string;
}

async function request&lt;T&gt;(url: string): Promise&lt;ApiResponse&lt;T&gt;&gt; {
    const res = await fetch(url);
    return res.json();
}
</code></pre>

<pre><code class="language-lua">-- Lua：表与面向对象原型
local Vector = {}
Vector.__index = Vector

function Vector.new(x, y)
    return setmetatable({ x = x or 0, y = y or 0 }, Vector)
end

function Vector:length()
    return math.sqrt(self.x * self.x + self.y * self.y)
end
</code></pre>

<h2>3. 配置、查询与构建语言（GraphQL / Protobuf / CMake / Docker）</h2>
<pre><code class="language-graphql"># GraphQL 查询与变更定义
query GetUserProfile($userId: ID!) {
    user(id: $userId) {
        id
        username
        email
        posts(limit: 10) {
            title
            createdAt
        }
    }
}
</code></pre>

<pre><code class="language-proto">// Protocol Buffers 定义
syntax = "proto3";
package argon.preview;

message AttachmentInfo {
    string uuid = 1;
    string file_name = 2;
    int64 file_size = 3;
    bool previewable = 4;
}
</code></pre>

<pre><code class="language-cmake"># CMake 构建配置
cmake_minimum_required(VERSION 3.20)
project(ArgonCore VERSION 2.0 LANGUAGES CXX)

set(CMAKE_CXX_STANDARD 20)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

add_executable(argon_app src/main.cpp)
</code></pre>

<pre><code class="language-dockerfile"># Dockerfile 构建镜像
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
</code></pre>

<h2>4. 行内代码与代码块混排对比测试</h2>
<p>在正文段落中穿插行内代码，例如 <code>const app = express()</code>、<code>npm run build</code>、<code>win32kfull.sys</code>、<code>$value</code> 以及 <code>SELECT * FROM wp_posts WHERE post_status = ''publish''</code>。测试行内小色块的浅色背景圆角框是否正常，且<strong>绝不污染</strong>上方或下方的多行代码块内部文字。</p>

<pre><code class="language-bash"># Shell / Terminal 控制台输出测试
win32kfull.sys
Status    : Valid
Signer    : CN=Microsoft Windows, O=Microsoft Corporation
IsOSBinary: True
</code></pre>',
    '多语言代码块高亮与渲染功能测试',
    'publish',
    'code-highlight-test',
    'post',
    'open',
    'open',
    '',
    '',
    '',
    '',
    NOW(),
    UTC_TIMESTAMP()
);
