# AI 评论审核流程说明

## 流程概述

本文档通过 Mermaid 流程图详细展示用户提交评论后，系统进行 AI 审核的全流程，涵盖预处理、规则判断、AI 检测、结果处理、数据学习等核心环节。

## 流程图

::: mermaid
flowchart TD
Start([用户提交评论]) --> PreProcess[预处理评论<br/>钩子：preprocess_comment/post_comment_preprocessing]
PreProcess --> CheckEnabled{启用 AI 检测?}
CheckEnabled -->|否| SaveComment[保存评论]
CheckEnabled -->|是| CheckMode{检测模式?}
CheckMode -->|manual（关闭实时检测）| SaveComment
CheckMode -->|keyword/sample/all| CheckLoggedIn{已登录用户?}
CheckLoggedIn -->|是且跳过登录用户| SaveComment
CheckLoggedIn -->|否或不跳过| CheckWhitelist{在白名单?}
CheckWhitelist -->|是| SaveComment
CheckWhitelist -->|否| CheckKeyword[检查关键字触发]
CheckKeyword --> DecideCheck{决定是否检测}
DecideCheck -->|keyword模式且触发关键字| NeedCheck[需要检测]
DecideCheck -->|sample模式且触发关键字| NeedCheck
DecideCheck -->|sample模式且随机抽中| NeedCheck
DecideCheck -->|all模式| NeedCheck
DecideCheck -->|其他情况| SaveComment
NeedCheck --> SetPending[强制设置 comment_approved=0<br/>待审核状态]
SetPending --> SetFlag[设置 _argon_needs_spam_check=true]
SetFlag --> SaveKeywords[保存触发的关键字]
SaveKeywords --> SaveComment
SaveComment --> CommentPost[评论入库后处理<br/>钩子：comment_post/post_comment_updatemetas]
CommentPost --> SaveMeta[保存评论元数据]
SaveMeta --> CheckNeedFlag{有 _argon_needs_spam_check 标记?}
CheckNeedFlag -->|否| ShowPending[显示审核中状态]
CheckNeedFlag -->|是| SaveCheckMeta[保存检测标记到数据库]
SaveCheckMeta --> AutoDetect[触发自动检测<br/>钩子：comment_post/argon_auto_detect_spam_on_comment]
AutoDetect --> CheckSaved{有检测标记?}
CheckSaved -->|否| End1([结束])
CheckSaved -->|是| CheckDetected{已检测过?}
CheckDetected -->|是| End2([结束])
CheckDetected -->|否| CheckReason{检测原因?}
CheckReason -->|关键字触发| SyncDetect[立即同步检测]
CheckReason -->|其他| AsyncDetect[异步检测（延迟1秒）]
SyncDetect --> DetectHandler[检测处理函数<br/>argon_async_spam_detection_handler]
AsyncDetect --> DetectHandler
DetectHandler --> CallAI[调用 AI API<br/>argon_detect_spam_comment]
CallAI --> GetPrompt[根据 Prompt 模式获取提示词]
CallAI --> BuildContext[构建评论上下文（用户名+内容）]
BuildContext --> SendAPI[发送到 AI API]
SendAPI --> ParseResult[解析 AI 返回结果]
ParseResult --> SaveTime[记录检测时间]
SaveTime --> GenCode[生成识别码]
GenCode --> UpdateStats[更新用户统计]
UpdateStats --> CheckResult{检测结果?}
CheckResult -->|内容违规（高置信度）| CheckAction1{自动处理方式?}
CheckResult -->|内容违规（低置信度）| MarkLowConf[标记为疑似垃圾<br/>等待人工审核]
CheckResult -->|内容正常/用户名违规/无邮箱| TrashNoEmail[移入回收站]
CheckResult -->|内容正常/用户名违规/有邮箱| ChangeUsername[修改用户名]
CheckResult -->|都正常| MarkNormal[标记为正常评论]
CheckAction1 -->|trash| TrashComment[移入回收站]
CheckAction1 -->|hold| HoldComment[标记为待审核]
CheckAction1 -->|mark| MarkOnly[仅标记不处理]
TrashComment --> SendSpamEmail[发送垃圾评论通知]
HoldComment --> SendSpamEmail
MarkOnly --> SaveResult1[保存检测结果]
SendSpamEmail --> SaveResult1
MarkLowConf --> SaveResult2[保存检测结果]
TrashNoEmail --> SaveResult3[保存检测结果]
ChangeUsername --> GenNewName[生成新用户名]
GenNewName --> UpdateDB[更新数据库]
UpdateDB --> SendChangeEmail[发送用户名变更通知]
SendChangeEmail --> SaveResult4[保存检测结果]
MarkNormal --> SaveResult5[保存检测结果]
SaveResult1 --> AILearn{启用 AI 学习?}
SaveResult2 --> AILearn
SaveResult3 --> AILearn
SaveResult4 --> AILearn
SaveResult5 --> AILearn
AILearn -->|是| ExtractKeywords[提取关键词]
AILearn -->|否| DetectEnd([检测完成])
ExtractKeywords --> UpdateKeywords[更新学习关键词库]
UpdateKeywords --> DetectEnd
ShowPending --> UserView([用户看到：审核中状态])
DetectEnd --> AdminReview([管理员审核])

style Start fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
style End1 fill:#ffe1e1,stroke:#c62828,stroke-width:2px
style End2 fill:#ffe1e1,stroke:#c62828,stroke-width:2px
style DetectEnd fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
style UserView fill:#fff4e1,stroke:#ff8f00,stroke-width:2px
style AdminReview fill:#e1e8ff,stroke:#1565c0,stroke-width:2px
style NeedCheck fill:#ffcccc,stroke:#c62828,stroke-width:1px
style SetPending fill:#ffcccc,stroke:#c62828,stroke-width:1px
style TrashComment fill:#ff6b6b,stroke:#c62828,stroke-width:1px
style TrashNoEmail fill:#ff6b6b,stroke:#c62828,stroke-width:1px
style HoldComment fill:#ffa500,stroke:#ff8f00,stroke-width:1px
style MarkLowConf fill:#ffa500,stroke:#ff8f00,stroke-width:1px
style ChangeUsername fill:#4ecdc4,stroke:#00897b,stroke-width:1px
style MarkNormal fill:#95e1d3,stroke:#2e7d32,stroke-width:1px
:::

## 流程关键节点说明

### 1. 预处理阶段

- 触发 `preprocess_comment`/`post_comment_preprocessing` 钩子，完成评论基础清洗。
- 优先判断是否启用 AI 检测，未启用则直接保存评论。

### 2. 检测规则判断

- 检测模式分 4 类：manual（关闭）、keyword（关键字）、sample（抽样）、all（全量）。
- 白名单用户、已登录且配置跳过的用户，直接跳过检测。
- keyword/sample 模式下，仅关键字触发/随机抽中时才启动检测。

### 3. AI 检测执行

- 关键字触发：立即同步检测；其他情况：延迟 1 秒异步检测。
- 调用 `argon_detect_spam_comment` 接口，拼接用户名+评论内容作为上下文发送 AI。

### 4. 结果处理逻辑

| 检测结果                | 处理方式                          |
|-------------------------|-----------------------------------|
| 内容违规（高置信度）| 按配置自动处理（移入回收站/待审核/仅标记） |
| 内容违规（低置信度）| 标记疑似垃圾，等待人工审核        |
| 用户名违规（无邮箱）| 直接移入回收站                    |
| 用户名违规（有邮箱）| 自动生成新用户名并通知用户        |
| 内容+用户名均正常       | 标记为正常评论                    |

### 5. 后续环节

- 所有检测结果均保存至数据库，支持 AI 学习功能（提取关键词更新词库）。
- 用户侧仅展示"审核中"状态，最终结果需管理员复核。

## 技术说明

### 使用的钩子

- `preprocess_comment` - 评论预处理
- `post_comment_preprocessing` - 评论预处理（备用）
- `comment_post` - 评论入库后
- `post_comment_updatemetas` - 更新评论元数据
- `argon_auto_detect_spam_on_comment` - 自动检测触发

### 关键函数

- `argon_detect_spam_comment()` - AI 检测主函数
- `argon_async_spam_detection_handler()` - 异步检测处理
- `argon_get_spam_detection_prompt()` - 获取检测 Prompt
- `argon_build_comment_context()` - 构建评论上下文

### 数据库字段

- `comment_approved` - 评论审核状态（0=待审核，1=已通过）
- `_argon_needs_spam_check` - 是否需要 AI 检测标记
- `_argon_spam_detection_result` - AI 检测结果
- `_argon_spam_detection_time` - 检测时间戳
- `_argon_spam_keywords` - 触发的关键字

## 配置建议

### 小型博客（评论量 < 100/天）

- 检测模式：keyword（关键字触发）
- Prompt 模式：标准模式
- 自动处理阈值：置信度 > 0.9

### 中型博客（评论量 100-500/天）

- 检测模式：sample（智能抽样）
- Prompt 模式：标准模式
- 自动处理阈值：置信度 > 0.85

### 大型博客（评论量 > 500/天）

- 检测模式：sample（智能抽样 30-40%）
- Prompt 模式：极简模式
- 自动处理阈值：置信度 > 0.8
- 定期批量扫描待审核评论

## 注意事项

1. **API 成本控制**：合理设置检测模式和抽样比例
2. **误判处理**：始终保留人工审核入口
3. **隐私保护**：不要将敏感信息发送给 AI
4. **性能优化**：关键字触发使用同步检测，其他使用异步
5. **定期优化**：根据误判率调整 Prompt 和阈值
