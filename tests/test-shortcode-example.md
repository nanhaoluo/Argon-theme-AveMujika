# AI 垃圾评论检测系统架构

## 系统概述

本文档展示 Argon 主题的 AI 垃圾评论检测系统的完整架构和流程。

## 主流程图

[mermaid theme="default" width="100%" align="center"]
flowchart TD
    Start([用户提交评论]) --> PreProcess[预处理评论]
    PreProcess --> CheckEnabled{启用 AI 检测?}
    
    CheckEnabled -->|否| DirectSave[直接保存评论]
    CheckEnabled -->|是| CheckMode{检测模式?}
    
    CheckMode -->|manual| DirectSave
    CheckMode -->|keyword| CheckKeyword[检查关键字]
    CheckMode -->|sample| CheckSample[智能抽样]
    CheckMode -->|all| NeedCheck[需要检测]
    
    CheckKeyword --> KeywordMatch{匹配关键字?}
    KeywordMatch -->|是| NeedCheck
    KeywordMatch -->|否| DirectSave
    
    CheckSample --> SampleDecision{抽中?}
    SampleDecision -->|是| NeedCheck
    SampleDecision -->|否| DirectSave
    
    NeedCheck --> SetPending[设置待审核状态]
    SetPending --> SaveWithFlag[保存评论并标记]
    SaveWithFlag --> TriggerDetect[触发 AI 检测]
    
    TriggerDetect --> CallAPI[调用 AI API]
    CallAPI --> ParseResult[解析结果]
    
    ParseResult --> CheckConfidence{置信度?}
    CheckConfidence -->|高| AutoHandle[自动处理]
    CheckConfidence -->|中| ManualReview[人工审核]
    CheckConfidence -->|低| MarkNormal[标记正常]
    
    AutoHandle --> CheckAction{处理方式?}
    CheckAction -->|trash| MoveTrash[移入回收站]
    CheckAction -->|hold| KeepPending[保持待审核]
    CheckAction -->|mark| JustMark[仅标记]
    
    MoveTrash --> NotifyAdmin[通知管理员]
    KeepPending --> NotifyAdmin
    JustMark --> SaveResult[保存结果]
    ManualReview --> SaveResult
    MarkNormal --> SaveResult
    NotifyAdmin --> SaveResult
    
    SaveResult --> AILearn{启用学习?}
    AILearn -->|是| ExtractKeywords[提取关键词]
    AILearn -->|否| End([结束])
    ExtractKeywords --> UpdateDB[更新词库]
    UpdateDB --> End
    
    DirectSave --> End
    
    style Start fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
    style End fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
    style MoveTrash fill:#ff6b6b,stroke:#c62828,stroke-width:2px
    style MarkNormal fill:#95e1d3,stroke:#2e7d32,stroke-width:2px
    style ManualReview fill:#ffa500,stroke:#ff8f00,stroke-width:2px
[/mermaid]

## 检测模式详解

[mermaid theme="default" width="80%" align="center"]
flowchart LR
    subgraph Manual[Manual 模式]
        M1[关闭实时检测]
        M2[仅手动扫描]
    end
    
    subgraph Keyword[Keyword 模式]
        K1[关键字触发]
        K2[精准检测]
        K3[低成本]
    end
    
    subgraph Sample[Sample 模式]
        S1[智能抽样]
        S2[平衡准确性]
        S3[控制成本]
    end
    
    subgraph All[All 模式]
        A1[全量检测]
        A2[最高准确性]
        A3[高成本]
    end
    
    style Manual fill:#e3f2fd
    style Keyword fill:#fff3e0
    style Sample fill:#f3e5f5
    style All fill:#fce4ec
[/mermaid]

## AI 检测流程

[mermaid theme="default" width="90%"]
sequenceDiagram
    participant User as 用户
    participant WP as WordPress
    participant Argon as Argon 主题
    participant AI as AI API
    participant DB as 数据库
    
    User->>WP: 提交评论
    WP->>Argon: 触发 preprocess_comment
    Argon->>Argon: 检查检测规则
    
    alt 需要检测
        Argon->>DB: 保存评论（待审核）
        Argon->>AI: 发送检测请求
        AI-->>Argon: 返回检测结果
        Argon->>Argon: 解析结果和置信度
        
        alt 高置信度垃圾评论
            Argon->>DB: 移入回收站
            Argon->>WP: 发送通知邮件
        else 中等置信度
            Argon->>DB: 标记待人工审核
        else 正常评论
            Argon->>DB: 标记为正常
        end
        
        Argon->>DB: 保存检测记录
        
        opt 启用 AI 学习
            Argon->>AI: 提取关键词
            AI-->>Argon: 返回关键词
            Argon->>DB: 更新学习词库
        end
    else 跳过检测
        Argon->>DB: 直接保存评论
    end
    
    WP-->>User: 显示提交结果
[/mermaid]

## 数据库结构

[mermaid theme="default" width="85%"]
erDiagram
    COMMENT ||--o{ COMMENT_META : has
    COMMENT {
        bigint comment_ID PK
        bigint comment_post_ID FK
        text comment_content
        varchar comment_author
        varchar comment_author_email
        varchar comment_author_IP
        datetime comment_date
        varchar comment_approved
    }
    
    COMMENT_META {
        bigint meta_id PK
        bigint comment_id FK
        varchar meta_key
        longtext meta_value
    }
    
    SPAM_DETECTION ||--|| COMMENT : detects
    SPAM_DETECTION {
        bigint id PK
        bigint comment_id FK
        varchar result
        float confidence
        text reason
        text keywords
        datetime detected_at
        varchar detection_code
    }
    
    LEARNED_KEYWORDS ||--o{ SPAM_DETECTION : learns_from
    LEARNED_KEYWORDS {
        bigint id PK
        varchar keyword
        int weight
        varchar category
        datetime created_at
        datetime updated_at
    }
[/mermaid]

## 系统状态机

[mermaid theme="default" width="70%"]
stateDiagram-v2
    [*] --> Submitted: 用户提交
    
    Submitted --> Pending: 需要检测
    Submitted --> Approved: 跳过检测
    
    Pending --> Detecting: 开始检测
    Detecting --> Analyzed: 检测完成
    
    Analyzed --> Spam: 高置信度垃圾
    Analyzed --> Suspicious: 中等置信度
    Analyzed --> Clean: 低置信度/正常
    
    Spam --> Trash: 自动处理
    Spam --> Hold: 保持待审核
    Spam --> Marked: 仅标记
    
    Suspicious --> ManualReview: 等待人工审核
    Clean --> Approved: 自动通过
    
    ManualReview --> Approved: 管理员批准
    ManualReview --> Trash: 管理员拒绝
    
    Hold --> Approved: 管理员批准
    Hold --> Trash: 管理员拒绝
    
    Marked --> Approved: 管理员批准
    Marked --> Trash: 管理员拒绝
    
    Trash --> [*]
    Approved --> [*]
    
    note right of Detecting
        调用 AI API
        解析返回结果
        计算置信度
    end note
    
    note right of ManualReview
        显示在后台
        等待管理员操作
    end note
[/mermaid]

## 性能优化策略

[mermaid theme="default" width="95%"]
flowchart TD
    subgraph Input[输入优化]
        I1[关键字预过滤]
        I2[白名单用户]
        I3[智能抽样]
    end
    
    subgraph Process[处理优化]
        P1[异步检测]
        P2[批量处理]
        P3[缓存结果]
    end
    
    subgraph API[API 优化]
        A1[Prompt 优化]
        A2[Token 控制]
        A3[超时处理]
    end
    
    subgraph Storage[存储优化]
        S1[索引优化]
        S2[定期清理]
        S3[归档历史]
    end
    
    Input --> Process
    Process --> API
    API --> Storage
    
    style Input fill:#e8f5e9
    style Process fill:#e3f2fd
    style API fill:#fff3e0
    style Storage fill:#f3e5f5
[/mermaid]

## 配置建议矩阵

[mermaid theme="default" width="100%"]
graph TB
    subgraph Small[小型博客 < 100评论/天]
        S1[检测模式: keyword]
        S2[Prompt: 标准模式]
        S3[置信度阈值: > 0.9]
        S4[自动处理: trash]
    end
    
    subgraph Medium[中型博客 100-500评论/天]
        M1[检测模式: sample 30%]
        M2[Prompt: 标准模式]
        M3[置信度阈值: > 0.85]
        M4[自动处理: hold]
    end
    
    subgraph Large[大型博客 > 500评论/天]
        L1[检测模式: sample 40%]
        L2[Prompt: 极简模式]
        L3[置信度阈值: > 0.8]
        L4[自动处理: mark]
        L5[定期批量扫描]
    end
    
    style Small fill:#c8e6c9
    style Medium fill:#fff9c4
    style Large fill:#ffccbc
[/mermaid]

## 错误处理流程

[mermaid theme="default" width="85%"]
flowchart TD
    Start([检测开始]) --> CallAPI[调用 AI API]
    
    CallAPI --> CheckResponse{响应状态?}
    
    CheckResponse -->|成功| ParseJSON[解析 JSON]
    CheckResponse -->|超时| Timeout[超时处理]
    CheckResponse -->|错误| APIError[API 错误]
    
    ParseJSON --> ValidateData{数据有效?}
    ValidateData -->|是| ProcessResult[处理结果]
    ValidateData -->|否| DataError[数据错误]
    
    Timeout --> RetryCheck{重试次数?}
    RetryCheck -->|< 3| Retry[重试请求]
    RetryCheck -->|>= 3| FallbackPending[降级：待审核]
    
    Retry --> CallAPI
    
    APIError --> LogError[记录错误日志]
    DataError --> LogError
    
    LogError --> NotifyAdmin[通知管理员]
    NotifyAdmin --> FallbackPending
    
    ProcessResult --> SaveResult[保存结果]
    FallbackPending --> SaveResult
    
    SaveResult --> End([结束])
    
    style Timeout fill:#ffccbc
    style APIError fill:#ffccbc
    style DataError fill:#ffccbc
    style FallbackPending fill:#fff9c4
    style ProcessResult fill:#c8e6c9
[/mermaid]

## 总结

通过以上流程图，我们可以清晰地看到：

1. **主流程**：从用户提交到最终处理的完整流程
2. **检测模式**：四种模式的特点和适用场景
3. **时序交互**：各组件之间的交互顺序
4. **数据结构**：数据库表关系和字段定义
5. **状态机**：评论的各种状态转换
6. **性能优化**：多层次的优化策略
7. **配置建议**：不同规模博客的推荐配置
8. **错误处理**：完善的异常处理机制

这个系统设计充分考虑了：
- ✅ 准确性：多级置信度判断
- ✅ 性能：异步处理、智能抽样
- ✅ 成本：灵活的检测模式
- ✅ 可靠性：完善的错误处理
- ✅ 可维护性：清晰的架构设计
