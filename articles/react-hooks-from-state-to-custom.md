---
title: "React Hooks 从 useState 到自定义 Hook：先把闭包和规则弄明白"
slug: "react-hooks-from-state-to-custom"
category: "前端工程"
date: "2021-10-20"
tags: ["React", "Hooks", "useState", "自定义 Hook"]
excerpt: "从 useState 的更新队列讲到 useRef、useReducer 和自定义 Hook，顺便解释闭包快照与 Hooks 规则为什么不能随便破坏。"
---

# React Hooks 从 useState 到自定义 Hook：先把闭包和规则弄明白

Hooks 里最容易让人困惑的不是 API 名字，而是闭包。按钮连续点两次，日志里却可能一直是旧的 `count`；把 `setCount(count + 1)` 改成函数形式后，才会发现 React 保存的是一次 render 的快照，不会在当前函数执行中把变量“变回新值”。

## useState 保存的是更新队列，不是可变变量

每次函数组件 render，`count` 都是这次调用对应的值。连续更新时，如果每个更新都捕获同一个快照，结果只能加一：

```jsx
function Counter() {
  const [count, setCount] = useState(0)

  function addTwice() {
    setCount(count + 1)
    setCount(count + 1)
  }
}
```

需要基于前一个状态计算时，使用函数更新，让 React 依次消费队列：

```jsx
function addTwice() {
  setCount(value => value + 1)
  setCount(value => value + 1)
}
```

这不是“异步 state”的神秘行为，而是 React 不承诺在事件处理函数中立刻重新调用组件。把 state 当作不可变快照，更容易理解日志、事件回调和异步任务为什么读到旧值。

## useRef 适合保存身份，不适合绕过渲染

`useRef(initialValue)` 返回一个在多次 render 之间保持同一身份的对象。修改 `ref.current` 不会触发 render：

```jsx
function SearchInput() {
  const inputRef = useRef(null)
  const lastRequestId = useRef(0)

  function focus() {
    inputRef.current?.focus()
  }
}
```

DOM 引用、定时器 id、上一次请求的序号适合放进 ref；界面上需要展示的值必须放进 state。用 ref 存可见状态，页面不会自动跟着变化，最后只会得到一份“内存里的真相”和一份“屏幕上的旧真相”。

## 状态转移复杂时，useReducer 更容易测试

当一个动作会同时改变多个字段，散落的 `setXxx` 调用很快变成难以追踪的顺序依赖。`useReducer` 把状态转移集中到纯函数：

```jsx
function reducer(state, action) {
  switch (action.type) {
    case 'start':
      return { ...state, status: 'loading', error: null }
    case 'success':
      return { status: 'ready', data: action.data, error: null }
    case 'failure':
      return { ...state, status: 'error', error: action.error }
    default:
      return state
  }
}
```

Reducer 不应该发请求、写 localStorage 或修改传入对象。副作用放在 effect 或事件处理函数中，状态转移才能被单独测试，也更容易回放一次错误流程。

## Hooks 的两条规则其实是在保护状态槽位

React 不是靠变量名识别 Hook，而是按调用顺序把它们挂在当前 Fiber 的链表上：第一次调用对应第一个槽位，第二次调用对应第二个槽位。因此下面的写法会让后续 state 全部错位：

```jsx
// 不要这样做
if (enabled) {
  const [value] = useState(0)
}
```

Hooks 必须在函数组件或自定义 Hook 的顶层调用，不能放进条件、循环和普通函数。自定义 Hook 只是把一段 Hook 调用组合成可复用逻辑，并不会共享 state：每个组件调用 `useOnlineStatus()`，都有自己的状态槽位。

```jsx
function useOnlineStatus() {
  const [online, setOnline] = useState(navigator.onLine)

  useEffect(() => {
    const update = () => setOnline(navigator.onLine)
    window.addEventListener('online', update)
    window.addEventListener('offline', update)
    return () => {
      window.removeEventListener('online', update)
      window.removeEventListener('offline', update)
    }
  }, [])

  return online
}
```

## 抽自定义 Hook 前先把边界写直

先把 state 和事件写直，不急着抽 Hook；当同一段“状态 + 订阅 + 清理”在两个组件中重复出现，再抽成自定义 Hook。只有性能证据出现后，才考虑 `useMemo` 或 `useCallback`。抽象的边界应该由重复的行为决定，而不是由文件长度决定。
