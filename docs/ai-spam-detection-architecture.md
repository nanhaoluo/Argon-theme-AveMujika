# AI 垃圾评论检测系统架构

## 系统概述

本文档展示 Argon 主题的 AI 垃圾评论检测系统的完整架构和流程。

## 主流程图

```mermaid
flowchart TD
    Start[用户提交评论]
    PreProcess[预处理评论]
    CheckEnabled{启用 AI 检测?}
    DirectSave[直接保存评论]
    CheckMode{检测模式?}
    CheckKeyword[检查关键字]
    CheckSample[智能抽样]
    NeedCheck[需要检测]
    KeywordMatch{匹配关键字?}
    SampleDecision{抽中?}
    SetPending[设置待审核状态]
    SaveWithFlag[保存评论并标记]
    TriggerDetect[触发 AI 检测]
    CallAPI[调用 AI API]
    ParseResult[解析结果]
    CheckConfidence{置信度?}
    AutoHandle[自动处理]
    ManualReview[人工审核]
    MarkNormal[标记正常]
    CheckAction{处理方式?}
    MoveTrash[移入回收站]
    KeepPending[保持待审核]
    JustMark[仅标记]
    NotifyAdmin[通知管理员]
    SaveResult[保存结果]
    AILearn{启用学习?}
    ExtractKeywords[提取关键词]
    UpdateDB[更新词库]
    End[结束]
    
    Start --> PreProcess
    PreProcess --> CheckEnabled
    CheckEnabled -->|否| DirectSave
    CheckEnabled -->|是| CheckMode
    CheckMode -->|manual| DirectSave
    CheckMode -->|keyword| CheckKeyword
    CheckMode -->|sample| CheckSample
    CheckMode -->|all| NeedCheck
    CheckKeyword --> KeywordMatch
    KeywordMatch -->|是| NeedCheck
    KeywordMatch -->|否| DirectSave
    CheckSample --> SampleDecision
    SampleDecision -->|是| NeedCheck
    SampleDecision -->|否| DirectSave
    NeedCheck --> SetPending
    SetPending --> SaveWithFlag
    SaveWithFlag --> TriggerDetect
    TriggerDetect --> CallAPI
    CallAPI --> ParseResult
    ParseResult --> CheckConfidence
    CheckConfidence -->|高| AutoHandle
    CheckConfidence -->|中| ManualReview
    CheckConfidence -->|低| MarkNormal
    AutoHandle --> CheckAction
    CheckAction -->|trash| MoveTrash
    CheckAction -->|hold| KeepPending
    CheckAction -->|mark| JustMark
    MoveTrash --> NotifyAdmin
    KeepPending --> NotifyAdmin
    JustMark --> SaveResult
    ManualReview --> SaveResult
    MarkNormal --> SaveResult
    NotifyAdmin --> SaveResult
    SaveResult --> AILearn
    AILearn -->|是| ExtractKeywords
    AILearn -->|否| End
    ExtractKeywords --> UpdateDB
    UpdateDB --> End
    DirectSave --> End
    
    style Start fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
    style End fill:#e1f5e1,stroke:#2e7d32,stroke-width:2px
    style MoveTrash fill:#ff6b6b,stroke:#c62828,stroke-width:2px
    style MarkNormal fill:#95e1d3,stroke:#2e7d32,stroke-width:2px
    style ManualReview fill:#ffa500,stroke:#ff8f00,stroke-width:2px
```

## 检测模式详解

```mermaid
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
```

## AI 检测流程

```mermaid
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
```

## 数据库结构

```mermaid
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
```

## 系统状态机

```mermaid
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
```

## 性能优化策略

```mermaid
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
```

## 关键技术点

### 1. 异步检测机制
- 评论提交后立即返回，不阻塞用户
- 后台异步调用 AI API 进行检测
- 使用 WordPress Cron 或队列系统

### 2. 智能抽样算法
- 基于用户历史行为的动态抽样率
- 新用户抽样率更高
- 可信用户抽样率降低

### 3. 置信度评分系统
- 0.9-1.0：非常确定，可自动处理
- 0.7-0.9：比较确定，建议自动处理
- 0.5-0.7：中等确定，建议人工审核
- 0.0-0.5：不太确定，必须人工审核

### 4. 学习优化机制
- 记录管理员的审核决策
- 分析误判模式
- 自动调整检测策略
- 提取高频垃圾关键词

### 5. 容错与降级
- API 失败时自动降级
- 保证评论系统正常运行
- 记录错误日志供后续分析

## 配置建议

### 小型博客（< 100 评论/天）
- 检测模式：Sample（30%）
- Prompt 模式：Standard
- 自动处理阈值：0.9
- 异步检测：启用

### 中型博客（100-500 评论/天）
- 检测模式：Sample（40%）
- Prompt 模式：Standard
- 自动处理阈值：0.85
- 异步检测：启用
- 批量扫描：每日一次

### 大型博客（> 500 评论/天）
- 检测模式：Keyword + Sample（50%）
- Prompt 模式：Minimal
- 自动处理阈值：0.8
- 异步检测：启用
- 批量扫描：每日两次
- 启用缓存和队列优化
