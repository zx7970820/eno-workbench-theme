---
title: "React 渲染的两阶段模型：Fiber 如何把工作变成可中断的任务"
slug: "react-rendering-fiber-concurrency"
category: "前端工程"
date: "2021-02-11"
tags: ["React", "Fiber", "并发渲染", "性能优化"]
excerpt: "从触发、Render、Commit 到浏览器绘制，沿着 Fiber 节点、workLoop 和 shouldYield 看 React 如何暂停低优先级工作。"
---

# React 渲染的两阶段模型：Fiber 如何把工作变成可中断的任务

遇到 React 输入卡顿时，先加一个 `memo` 往往看不出变化。组件函数可能仍然在执行，父组件也可能每次都创建新的 props。要把这个问题看清楚，得先把一次更新拆成几个阶段，再去看 Fiber 到底在哪个阶段提供了“可以停一下”的机会。

Fiber 解决的不是“虚拟 DOM 比较更快”，而是把 Render 工作拆成可以保存和恢复的单元。

## 先把一次屏幕更新拆成四段

React 的一次更新可以按这个顺序理解：

1. **Trigger**：首次挂载，或某个 state / props 更新进入队列。
2. **Render**：调用组件函数或 class 的 render，构造 work-in-progress 树并计算差异。
3. **Commit**：把必要的 DOM、ref 和生命周期变化一次性提交。
4. **Browser**：浏览器再进行样式计算、布局、绘制和合成。

Render 阶段必须保持纯。并发模式下它可能被暂停、重启，甚至整棵丢弃；如果在组件函数里发请求或修改外部对象，副作用可能发生多次却一次都没有提交。Effect 和 `componentDidMount` / `componentDidUpdate` 位于提交之后，正是为了把这条边界说清楚。

## Fiber 把递归调用栈拆成链表节点

传统递归渲染一旦进入深层子树，很难在中途把控制权还给浏览器。Fiber 节点把每个组件、原生元素和文本工作表示成一个可恢复的单元，并用 `child`、`sibling`、`return` 连接成树：

```js
fiber.child   // 第一个子节点
fiber.sibling // 下一个兄弟节点
fiber.return  // 父节点
```

每个 Fiber 还保存 `alternate`，指向当前树和 work-in-progress 树中对应的另一份节点。Render 阶段在 work-in-progress 上计算，只有完整完成后才在 Commit 阶段把它切成 current。用户不会看到一半新、一半旧的 DOM。

## beginWork 和 completeWork 是一趟可停的遍历

可以把 Render 阶段简化成深度优先遍历：

```js
function performUnitOfWork(unit) {
  const next = beginWork(unit.alternate, unit)
  unit.memoizedProps = unit.pendingProps

  if (next) return next
  let node = unit
  while (node) {
    completeWork(node.alternate, node)
    if (node.sibling) return node.sibling
    node = node.return
  }
}
```

`beginWork` 会执行组件、协调 children，决定下一个 child；没有 child 时向上回溯，`completeWork` 准备 DOM、flags 和副作用链。真正的 React 代码还要处理 context、Suspense、错误边界和多种 Fiber tag，但“向下展开、向上完成”的结构没有变。

## 可打断的关键是 workLoopConcurrent

并发 Render 不会让 JavaScript 在多个线程同时跑。React 只是把一个大任务切成许多 `performUnitOfWork`，在每个单元之间询问调度器是否应该让出主线程：

```js
function workLoopConcurrent() {
  while (workInProgress !== null && !shouldYield()) {
    workInProgress = performUnitOfWork(workInProgress)
  }
}
```

浏览器一帧里还有输入、布局和绘制要处理时，`shouldYield()` 变成 true，React 保存当前的 `workInProgress`，把控制权还回去。稍后调度器再次调用 root 的工作函数，从上次停下的 Fiber 继续。新的高优先级输入到来时，旧的低优先级树甚至可以被丢弃并重新计算。

这里的“可中断”有三个前提：

- 工作必须能在 Fiber 边界之间暂停，不能把一大段同步循环藏在 render 里。
- Render 不能依赖执行次数或执行顺序，否则暂停和重启会改变结果。
- Commit 不能半途而废，所以它仍然是相对短、不可中断的一段。

## lanes 表达“这次更新有多急”

React 18 用 lanes 位掩码表示更新优先级。离散输入、普通更新、Transition、空闲任务可以占据不同的 lane；root 会选择当前最紧急且尚未完成的 lanes。这个模型比“一个全局优先级数字”更适合合并多个来源的更新：同一批工作可以共享 lane，也可以让高优先级更新先完成。

```jsx
const [isPending, startTransition] = useTransition()

function selectTab(nextTab) {
  setInput(nextTab) // 保持输入及时
  startTransition(() => {
    setTab(nextTab) // 标记为可被打断的工作
  })
}
```

`startTransition` 不会让传入函数异步执行，函数会立即运行；它标记的是其中排队的状态更新。如果低优先级列表在计算时用户继续输入，React 可以先完成输入对应的 lane，再重新计算被打断的列表。

## 协调和 key 决定哪些 Fiber 能复用

React 不会求解两棵任意树的最小编辑距离，而使用可预测的启发式规则：类型不同通常替换子树，同类型元素尝试复用，列表通过稳定 key 建立身份。

```jsx
items.map(item => <Row key={item.id} item={item} />)
```

索引 key 把位置误当身份，插入或排序时会让组件 state 跟错行。稳定 key 不一定减少 render，但能让 Fiber 保留正确的实例、DOM 和待处理更新。

## Commit 为什么不能被随便暂停

Commit 会按 flags 执行 placement、update、deletion，更新 ref，并运行 layout effect。此时用户已经可以观察到 DOM，如果执行到一半就暂停，页面会进入一棵不存在于任何 React 树中的混合状态。因此 React 宁愿把 Render 拆得更细，也要让 Commit 尽量短：把昂贵计算放在可中断的 Render 之外没有意义，真正应该减少的是每个 Fiber 的工作量和无效更新。

## 遇到卡顿，我会先看哪里

1. 用 Profiler 区分慢在组件 Render、Commit，还是浏览器布局/绘制。
2. 看输入更新是否被错误地标成低优先级，或低优先级列表是否每次都从头计算。
3. 检查 props、对象和回调引用是否让 memo 边界失效。
4. 检查 render 中是否有不可拆分的大循环和副作用。
5. 最后再考虑 `memo`、`useMemo` 或 Transition，而不是先到处加缓存。

Fiber 的价值不是让每个组件都更快，而是让用户输入有机会在大更新中被优先处理。理解这条调度边界之后，性能优化才不会停留在“少 render 一次”的数字游戏里。
