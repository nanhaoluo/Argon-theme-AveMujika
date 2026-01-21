# GPU 加速管理使用指南

## 概述

`ArgonRenderOptimizer` 类提供了 GPU 加速管理功能，包括：
- 启用/禁用 GPU 加速（will-change 属性）
- 限制同时运行的动画数量（最多 3 个）
- 自动管理动画队列
- 动画完成后自动清理资源

## 功能说明

### 1. GPU 加速管理

#### enableGPU(element)
为元素启用 GPU 加速，设置 `will-change: transform, opacity` 属性。

**参数：**
- `element` (Element): 要启用 GPU 加速的 DOM 元素

**示例：**
```javascript
const optimizer = new ArgonRenderOptimizer();
const element = document.querySelector('.animated-element');

// 启用 GPU 加速
optimizer.enableGPU(element);
```

#### disableGPU(element)
为元素禁用 GPU 加速，移除 `will-change` 属性。

**参数：**
- `element` (Element): 要禁用 GPU 加速的 DOM 元素

**示例：**
```javascript
// 禁用 GPU 加速
optimizer.disableGPU(element);
```

### 2. 动画数量限制

系统自动限制同时运行的动画数量不超过 3 个，超出的动画会进入等待队列。

#### startAnimation(element, animationFn)
启动一个动画。如果当前活动动画数量未达到上限（3 个），立即启动；否则加入等待队列。

**参数：**
- `element` (Element): 要动画的 DOM 元素
- `animationFn` (Function): 动画函数，接收 element 作为参数

**返回值：**
- `boolean`: 如果动画立即启动返回 true，否则返回 false

**示例：**
```javascript
const optimizer = new ArgonRenderOptimizer();
const element = document.querySelector('.box');

// 启动动画
const started = optimizer.startAnimation(element, (el) => {
	// 执行动画
	el.style.transform = 'translateY(-50px)';
	el.style.transition = 'transform 0.5s ease';
	
	// 动画完成后调用 endAnimation
	setTimeout(() => {
		el.style.transform = '';
		optimizer.endAnimation(el);
	}, 500);
});

if (started) {
	console.log('动画立即启动');
} else {
	console.log('动画加入等待队列');
}
```

#### endAnimation(element)
结束一个动画，自动禁用 GPU 加速并启动队列中的下一个动画（如果有）。

**参数：**
- `element` (Element): 动画完成的 DOM 元素

**示例：**
```javascript
// 动画完成后调用
optimizer.endAnimation(element);
```

### 3. 查询方法

#### getActiveAnimationCount()
获取当前活动的动画数量。

**返回值：**
- `number`: 当前活动的动画数量

**示例：**
```javascript
const activeCount = optimizer.getActiveAnimationCount();
console.log(`当前活动动画: ${activeCount} 个`);
```

#### getQueuedAnimationCount()
获取等待队列中的动画数量。

**返回值：**
- `number`: 等待队列中的动画数量

**示例：**
```javascript
const queuedCount = optimizer.getQueuedAnimationCount();
console.log(`等待队列: ${queuedCount} 个`);
```

### 4. 清理方法

#### clearAllAnimations()
清除所有动画，包括活动动画和等待队列。

**示例：**
```javascript
// 清除所有动画
optimizer.clearAllAnimations();
```

## 完整使用示例

### 示例 1: 基本动画管理

```javascript
// 初始化优化器
const optimizer = new ArgonRenderOptimizer();

// 获取要动画的元素
const boxes = document.querySelectorAll('.box');

// 为每个元素启动动画
boxes.forEach((box, index) => {
	optimizer.startAnimation(box, (element) => {
		// 应用动画
		element.style.transform = 'rotate(360deg) scale(1.2)';
		element.style.transition = 'transform 1s ease';
		
		// 1秒后结束动画
		setTimeout(() => {
			element.style.transform = '';
			optimizer.endAnimation(element);
		}, 1000);
	});
});
```

### 示例 2: 监听动画完成事件

```javascript
const optimizer = new ArgonRenderOptimizer();
const element = document.querySelector('.animated-box');

optimizer.startAnimation(element, (el) => {
	el.classList.add('animating');
	
	// 监听 CSS 动画完成事件
	el.addEventListener('animationend', function handler() {
		el.classList.remove('animating');
		optimizer.endAnimation(el);
		el.removeEventListener('animationend', handler);
	});
});
```

### 示例 3: 批量动画管理

```javascript
const optimizer = new ArgonRenderOptimizer();

function animateElements(elements) {
	let completedCount = 0;
	
	elements.forEach((element, index) => {
		optimizer.startAnimation(element, (el) => {
			// 执行动画
			el.style.opacity = '0';
			el.style.transform = 'translateY(20px)';
			el.style.transition = 'all 0.5s ease';
			
			setTimeout(() => {
				el.style.opacity = '1';
				el.style.transform = 'translateY(0)';
			}, 50);
			
			// 动画完成
			setTimeout(() => {
				optimizer.endAnimation(el);
				completedCount++;
				
				if (completedCount === elements.length) {
					console.log('所有动画完成');
				}
			}, 600);
		});
	});
	
	// 显示状态
	console.log(`活动动画: ${optimizer.getActiveAnimationCount()}`);
	console.log(`等待队列: ${optimizer.getQueuedAnimationCount()}`);
}

// 使用
const elements = document.querySelectorAll('.fade-in');
animateElements(elements);
```

### 示例 4: 页面切换时清理

```javascript
const optimizer = new ArgonRenderOptimizer();

// PJAX 页面切换前清理所有动画
$(document).on('pjax:beforeReplace', function() {
	optimizer.clearAllAnimations();
});
```

## 最佳实践

### 1. 始终调用 endAnimation()
确保在动画完成后调用 `endAnimation()`，以便：
- 释放 GPU 资源（移除 will-change）
- 允许队列中的下一个动画启动
- 避免内存泄漏

```javascript
// ✓ 正确
optimizer.startAnimation(element, (el) => {
	el.style.transform = 'scale(1.2)';
	setTimeout(() => {
		optimizer.endAnimation(el);  // 记得调用
	}, 500);
});

// ✗ 错误 - 忘记调用 endAnimation
optimizer.startAnimation(element, (el) => {
	el.style.transform = 'scale(1.2)';
	// 没有调用 endAnimation，资源不会释放
});
```

### 2. 使用 CSS 动画时监听事件

```javascript
optimizer.startAnimation(element, (el) => {
	el.classList.add('animate');
	
	// 监听动画完成
	el.addEventListener('animationend', function handler() {
		optimizer.endAnimation(el);
		el.removeEventListener('animationend', handler);
	}, { once: true });
});
```

### 3. 错误处理

```javascript
optimizer.startAnimation(element, (el) => {
	try {
		// 动画逻辑
		el.style.transform = 'translateX(100px)';
		
		setTimeout(() => {
			optimizer.endAnimation(el);
		}, 500);
	} catch (error) {
		console.error('动画错误:', error);
		// 确保即使出错也要清理
		optimizer.endAnimation(el);
	}
});
```

### 4. 页面卸载时清理

```javascript
// 页面卸载或组件销毁时
window.addEventListener('beforeunload', () => {
	optimizer.clearAllAnimations();
});

// 或在 PJAX 中
$(document).on('pjax:beforeReplace', () => {
	optimizer.clearAllAnimations();
});
```

## 性能优化建议

### 1. 仅对需要的元素启用 GPU 加速
不要过度使用 GPU 加速，只对真正需要动画的元素使用：

```javascript
// ✓ 好 - 仅在动画时启用
optimizer.startAnimation(element, (el) => {
	// GPU 加速自动启用
	el.style.transform = 'translateX(100px)';
	setTimeout(() => {
		optimizer.endAnimation(el);  // GPU 加速自动禁用
	}, 500);
});

// ✗ 不好 - 长期启用 GPU 加速
optimizer.enableGPU(element);
// 元素一直保持 GPU 加速状态，浪费资源
```

### 2. 利用动画队列
系统会自动管理动画队列，不需要手动控制：

```javascript
// 启动多个动画，系统自动排队
elements.forEach(el => {
	optimizer.startAnimation(el, (element) => {
		// 动画逻辑
		setTimeout(() => {
			optimizer.endAnimation(element);
		}, 500);
	});
});
```

### 3. 监控动画状态
在开发时监控动画状态，确保没有泄漏：

```javascript
setInterval(() => {
	console.log('活动动画:', optimizer.getActiveAnimationCount());
	console.log('等待队列:', optimizer.getQueuedAnimationCount());
}, 1000);
```

## 需求验证

本实现满足以下需求：

- ✅ **需求 5.2**: 动画时使用 `will-change` 提示浏览器创建合成层
- ✅ **需求 5.3**: 动画完成时移除 `will-change` 属性释放资源
- ✅ **需求 5.5**: 限制同时运行的动画数量不超过 3 个

## 测试

运行测试文件验证功能：

1. **交互式测试**: 打开 `test-gpu-acceleration.html` 在浏览器中测试
2. **自动化测试**: 打开 `test-gpu-simple.html` 查看自动化测试结果

## 常见问题

### Q: 为什么限制为 3 个动画？
A: 同时运行过多动画会导致 CPU 占用过高和性能下降。3 个是经过测试的最佳平衡点。

### Q: 如果我需要更多同时动画怎么办？
A: 可以修改 `maxAnimations` 属性，但不建议超过 5 个：
```javascript
optimizer.maxAnimations = 5;  // 不推荐
```

### Q: 动画队列是 FIFO 还是 LIFO？
A: FIFO（先进先出）。先加入队列的动画会先执行。

### Q: 如果忘记调用 endAnimation() 会怎样？
A: 会导致：
- GPU 资源不会释放（will-change 一直存在）
- 队列中的动画永远不会启动
- 活动动画计数不准确

建议始终在动画完成时调用 `endAnimation()`。
