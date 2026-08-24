---
title: "React 渲染的两阶段模型：Fiber、协调、提交与并发更新"
slug: "react-rendering-fiber-concurrency"
category: "前端工程"
tags: ["React", "Fiber", "并发渲染", "性能优化"]
excerpt: "把触发、Render、Commit 与浏览器绘制区分开，再用 Fiber 和优先级理解可中断更新、key、memo 与 Transition 的真实作用。"
---

# React 渲染的两阶段模型：Fiber、协调、提交与并发更新

React 性能讨论里最常见的误区，是把“组件函数重新执行”“虚拟 DOM 比较”“DOM 更新”和“浏览器绘制”混成同一件事。正确的心智模型应先划分阶段，再讨论 Fiber 如何让其中一部分工作可调度。

## 1. 一次界面更新经过什么

React 官方把屏幕更新概括为 Trigger、Render、Commit：

1. Trigger：首次挂载，或某个 state 更新进入队列。
2. Render：调用组件，递归计算下一棵 UI 描述。
3. Commit：把必要的变化应用到 DOM，并执行与提交相关的副作用。

浏览器随后才进行样式计算、布局、绘制与合成。组件重新 render 并不意味着对应 DOM 一定发生变化；Commit 也不等于浏览器必然重排整个页面。

Render 阶段必须保持纯：相同输入得到相同输出，不能修改外部对象。因为在并发能力下，Render 工作可能暂停、重启，甚至被丢弃。把副作用放进组件函数，意味着它可能发生多次却一次都没有提交。

## 2. Fiber 解决的是“工作如何被表示”

传统递归渲染一旦开始，调用栈很难主动让出。Fiber 把组件树中的工作拆成可连接、可恢复的单元，使 React 能记录当前处理位置、父子与兄弟关系，以及这次更新携带的优先级。

可以把它理解为两棵树：当前已提交的树，以及正在构建的 work-in-progress 树。Render 阶段在后者上计算变化；成功完成后，Commit 阶段把结果切换为当前树。这里的关键不是“Fiber 比虚拟 DOM 更快”，而是它让调度、暂停和复用中间工作成为可能。

## 3. 协调为什么依赖类型和 key

React 并不求解两棵任意树的最小编辑距离，而使用更可预测的启发式规则：

- 元素类型变化时，通常替换对应子树。
- 同类型元素可以复用实例与 DOM 节点。
- 同级列表通过 key 建立稳定身份。

```jsx
items.map(item => <Row key={item.id} item={item} />)
```

key 不是消除警告的装饰。使用数组索引，在插入、排序或过滤时会把“位置”误当成“身份”，导致组件状态跟着位置漂移。稳定 key 让协调器知道哪个节点是移动，哪个节点是真正新增或删除。

## 4. 优先级不是线程，而是更新语义

浏览器主线程仍然只有一条。并发渲染并不让两个组件真正同时执行，而是让 React 可以把低优先级 Render 工作切成片段，并优先处理输入、点击等更紧急的更新。

`useTransition` 用于标记非阻塞更新：

```jsx
const [isPending, startTransition] = useTransition()

function selectTab(nextTab) {
  startTransition(() => setTab(nextTab))
}
```

如果用户在低优先级列表渲染期间继续输入，React 可以中断旧工作，先处理输入，再重新开始相关的后台更新。Transition 不能用来控制文本输入，因为输入值本身必须立即同步。

这也说明 Transition 不是“让代码异步”，传入函数会立即调用；被标记的是其中排队的状态更新。

## 5. memo 的作用是缩小 Render 范围

默认情况下，父组件 render 会递归调用其子组件。`memo`、`useMemo`、`useCallback` 的价值，是在确认存在成本后维持输入稳定，让某些子树可以跳过计算。

但如果每次都创建新对象，memo 边界仍会失效：

```jsx
// 每次 render 都是新对象
<Chart options={{ theme: 'dark' }} />

const options = useMemo(() => ({ theme: 'dark' }), [])
<Chart options={options} />
```

不要为了“减少 render 次数”在所有地方加 memo。组件函数执行往往很便宜，比较 props、维护缓存和增加认知负担也有成本。先用 Profiler 确认慢在哪里。

## 6. Effect 属于提交后的同步

Effect 用来把 React 状态与外部系统同步，而不是表达可由 props/state 直接计算出的派生值。能在 Render 期间算出的值，不应再用 Effect 写回 state，否则会形成“提交 → Effect → 再次更新”的额外往返。

```jsx
// 不必要
useEffect(() => setFullName(first + last), [first, last])

// 直接派生
const fullName = first + last
```

当 Effect 订阅外部资源时，要确保清理逻辑与订阅一一对应，并避免依赖数组遗漏导致读取陈旧闭包。

## 7. 一套可操作的排查顺序

1. 先区分慢在 Render、Commit，还是浏览器布局/绘制。
2. 用 React Profiler 找到高耗时且重复出现的组件。
3. 检查列表 key 和组件边界是否稳定。
4. 检查 props 是否因新对象/函数导致 memo 失效。
5. 对确实不紧急的大更新使用 Transition，而不是延迟所有状态。
6. 检查 Effect 是否把可派生数据重复写回 state。

性能优化的目标不是让组件永不 render，而是让高优先级交互及时响应，并让昂贵工作只在输入真正变化时发生。

## 参考资料

- [React 官方：Render and Commit](https://react.dev/learn/render-and-commit)
- [React 官方：useTransition](https://react.dev/reference/react/useTransition)
- [React 官方：Preserving and Resetting State](https://react.dev/learn/preserving-and-resetting-state)
