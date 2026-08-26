---
title: "React Hooks 怎么分工：从 state、effect 到稳定引用"
slug: "react-effect-memo-callback"
category: "前端工程"
date: "2024-02-26"
tags: ["React", "Hooks", "useEffect", "性能优化"]
excerpt: "不把 Hook 当生命周期清单，从一个搜索页面的更新链开始，分清 state、ref、effect、memo、transition 和自定义 Hook 各自该管什么。"
---

# React Hooks 怎么分工：从 state、effect 到稳定引用

最容易把 Hook 写乱的场景，是一个页面同时有输入框、请求、筛选列表和一两个需要记住的值。输入一变，组件重新 render；render 里又创建了新对象和新函数；effect 发现依赖变了，再发一次请求；请求回来又 setState。最后大家开始给每一行都加 `useMemo`，但没人能说清楚到底在阻止哪一次工作。

我更愿意先把问题拆成两类：这段代码是在描述当前 render 应该显示什么，还是在和 React 之外的东西同步？前一类通常属于 state、reducer 或直接计算，后一类才轮到 effect。

## 先理解 state：每次 render 都是一张快照

函数组件里的 `count` 不是一个会在当前函数执行中改变的变量。它属于这一次 render 的快照，调用 setter 只是把下一次 render 的工作排进队列：

```jsx
function Counter() {
  const [count, setCount] = useState(0)

  function addTwice() {
    setCount(count + 1)
    setCount(count + 1)
  }

  return <button onClick={addTwice}>{count}</button>
}
```

点击一次，上面的按钮通常只加 1，因为两个调用都读到了同一张快照。需要基于前一个值连续更新时，要把更新写成函数：

```jsx
setCount(previous => previous + 1)
setCount(previous => previous + 1)
```

这也是为什么把 `setState` 理解成“立刻改变量”会产生很多误判。state setter 会排队，React 再按自己的调度时机合并和处理这些更新；事件处理函数里看到的旧值，并不代表 React 丢了更新。

当状态变化不只是几个字段，而是由一组动作推动时，我会换成 `useReducer`，把状态转移写在一个纯函数里：

```jsx
function reducer(state, action) {
  switch (action.type) {
    case 'edited':
      return { ...state, text: action.text, dirty: true }
    case 'saved':
      return { ...state, dirty: false }
    default:
      throw new Error(`Unknown action: ${action.type}`)
  }
}

const [editor, dispatch] = useReducer(reducer, {
  text: '',
  dirty: false,
})
```

`useReducer` 不会让更新绕过 render，也不等于状态管理库。它只是让“收到什么动作，变成什么状态”更集中，特别适合表单、编辑器和有明确事件流的组件。

## ref 保存过程中的东西，但不会触发 render

`useRef` 返回的是一个长期存在的 `{ current }` 容器。修改 `current` 不会触发重新渲染，所以它适合存 DOM 节点、定时器 id、上一次请求的控制器这类“不直接决定画面”的东西：

```jsx
function SearchInput() {
  const inputRef = useRef(null)
  const timerRef = useRef(null)

  function focusInput() {
    inputRef.current?.focus()
  }

  function scheduleSave(value) {
    clearTimeout(timerRef.current)
    timerRef.current = setTimeout(() => saveDraft(value), 300)
  }

  return <input ref={inputRef} onChange={event => scheduleSave(event.target.value)} />
}
```

如果这个值需要显示在 JSX 里，就不能藏在 ref 里；改了 `ref.current`，React 不知道页面需要更新。还有一个容易踩的边界：不要在 render 过程中随意读写 ref，事件处理函数和 effect 才是合适的位置。

`useContext` 解决的是跨层传值，不是“所有状态都放一个全局对象”。读取某个 context 的组件会订阅 provider 的 `value`；前后 value 用 `Object.is` 比较，provider 每次 render 都新建一个对象时，下面的消费者就会跟着更新：

```jsx
const SettingsContext = createContext(null)

function SettingsProvider({ children }) {
  const [locale, setLocale] = useState('zh-CN')
  const value = useMemo(() => ({ locale, setLocale }), [locale])

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>
}
```

这里的 `useMemo` 只是在 provider 的 value 确实需要稳定时有意义。它不能阻止真正读取了 context 的组件在 value 变化后更新，也不能替代把 context 拆成更小边界。

## effect 是“和外部系统同步”的出口

`useEffect` 的 setup 在组件提交到 DOM 后运行。它可以连接订阅、网络、计时器、浏览器 API 或第三方 widget；它不是“函数组件版的 componentDidMount”。第二个参数有三种常见形态：

```jsx
// 没有第二个参数：每次 commit 后都执行
useEffect(() => {
  document.title = title
})

// 空数组：第一次 commit 后执行，卸载时清理
useEffect(() => {
  const connection = connect('general')
  return () => connection.close()
}, [])

// roomId 变化后重新建立连接
useEffect(() => {
  const connection = connect(roomId)
  return () => connection.close()
}, [roomId])
```

依赖不是执行次数开关，而是 setup 闭包读取的 reactive values。React 用 `Object.is` 比较依赖；漏掉依赖会让 setup 继续使用旧的 props 或 state，放进一个每次 render 都新建的对象则会让 effect 反复重跑。遇到 eslint 的 exhaustive-deps 提示，先改代码让依赖变得合理，不要先把规则关掉。

清理函数和 setup 必须成对出现。切换搜索词时，旧请求不应该把结果覆盖回来：

```jsx
function Results({ query }) {
  const [rows, setRows] = useState([])
  const [error, setError] = useState(null)

  useEffect(() => {
    const controller = new AbortController()
    setError(null)

    fetch(`/api/search?q=${encodeURIComponent(query)}`, {
      signal: controller.signal,
    })
      .then(response => response.json())
      .then(nextRows => setRows(nextRows))
      .catch(error => {
        if (error.name !== 'AbortError') setError(error)
      })

    return () => controller.abort()
  }, [query])

  return (
    <>
      {error && <p role="alert">加载失败，请稍后重试。</p>}
      <ResultList rows={rows} />
    </>
  )
}
```

不要直接把 `async` 函数交给 `useEffect`。`async` 函数返回的是 Promise，而 effect 的返回值只能是同步 cleanup 函数。开发环境的 Strict Mode 还可能在第一次真正运行前执行一遍 setup → cleanup → setup，这正好能暴露“建立了连接却没有关闭”的问题。Effect 只在客户端运行，服务端渲染不能依赖它生成初始 HTML。

如果只是把两个 props 拼成一个字符串，或者根据数组过滤出一份展示数据，就在 render 里直接算：

```jsx
// 不必要的额外 render
const [fullName, setFullName] = useState('')
useEffect(() => setFullName(`${first} ${last}`), [first, last])

// 当前 render 已经知道答案
const fullName = `${first} ${last}`
```

把可计算的数据绕进 effect，会多出一次 state 更新，也会制造第二个真相来源。

## useLayoutEffect 只处理“必须赶在绘制前”的事

普通 effect 通常允许浏览器先画出页面。需要先测量 DOM、再调整 tooltip 位置，或者在绘制前同步滚动位置时，才考虑 `useLayoutEffect`：

```jsx
function Tooltip({ anchorRef, children }) {
  const tooltipRef = useRef(null)

  useLayoutEffect(() => {
    const anchor = anchorRef.current
    const tooltip = tooltipRef.current
    if (!anchor || !tooltip) return

    const rect = anchor.getBoundingClientRect()
    tooltip.style.top = `${rect.bottom + 8}px`
    tooltip.style.left = `${rect.left}px`
  })

  return <div ref={tooltipRef}>{children}</div>
}
```

它会阻塞浏览器绘制，写多了反而让页面变慢；而且服务端没有布局信息，使用服务端渲染时还要处理 `useLayoutEffect` 的告警。绝大多数请求、订阅和数据同步仍然应该放在 `useEffect`。

## useMemo 和 useCallback：缓存结果，不是修复工具

`useMemo` 缓存计算结果，`useCallback` 缓存函数本身。下面两段的效果等价，区别只是后者少写一层返回函数：

```jsx
const handleSelectA = useMemo(() => {
  return id => onSelect(id)
}, [onSelect])

const handleSelectB = useCallback(id => onSelect(id), [onSelect])
```

真正值得缓存的通常是昂贵的派生计算，或者要传给 `memo` 子组件、要作为另一个 Hook 依赖的稳定引用：

```jsx
const visibleRows = useMemo(
  () => rows.filter(row => row.name.includes(keyword)),
  [rows, keyword]
)

const handleSelect = useCallback(
  id => onSelect(id),
  [onSelect]
)

return <ResultList rows={visibleRows} onSelect={handleSelect} />
```

如果 `ResultList` 没有用 `memo`，或者父组件每次都传入一个新的 `onSelect`，上面的缓存可能没有收益。`useMemo` 和 `useCallback` 都是性能优化，不是语义保证；代码去掉缓存后不应该变得“不正确”。先用 Profiler 找到重复 render 和真正耗时的计算，再决定要不要承担缓存的依赖和维护成本。

## React 18 之后：把“不急的更新”说清楚

`useTransition` 和 `useDeferredValue` 解决的不是防抖，也不是让 CPU 变快，而是告诉 React 哪些更新可以晚一点完成：

```jsx
function SearchPage({ rows }) {
  const [query, setQuery] = useState('')
  const deferredQuery = useDeferredValue(query)
  const visibleRows = useMemo(
    () => expensiveFilter(rows, deferredQuery),
    [rows, deferredQuery]
  )

  return (
    <>
      <input value={query} onChange={event => setQuery(event.target.value)} />
      <ResultList rows={visibleRows} />
    </>
  )
}
```

输入框的 `query` 立即更新，列表可以暂时使用旧的 `deferredQuery`。它没有承诺固定延迟，也不会替你取消网络请求。`useTransition` 则适合把一次切换页面、筛选结果这类非紧急 state 更新包起来；受控输入本身仍应该保持同步，否则输入会感觉发黏。

它的写法大概是这样，`isPending` 只用来告诉用户列表还在切换：

```jsx
const [isPending, startTransition] = useTransition()
const [tab, setTab] = useState('all')

function selectTab(nextTab) {
  startTransition(() => setTab(nextTab))
}

return <button onClick={() => selectTab('archived')} disabled={isPending}>已归档</button>
```

## 自定义 Hook 只是复用逻辑，不会共享 state

当一段订阅或请求逻辑在多个组件重复出现时，可以提取成 `useOnlineStatus`、`useDebouncedValue` 这样的自定义 Hook。它必须在组件或另一个 Hook 的顶层调用，不能放进条件、循环和普通工具函数里；每个组件调用一次，就有自己独立的 state 和 effect。

如果数据来自 React 之外的 store，不要随手用“effect 订阅 + setState”拼一个半成品。React 18 的 `useSyncExternalStore` 把 `subscribe` 和 `getSnapshot` 的契约写清楚，能让外部 store 在并发渲染下有一致的读取方式。`useId` 也有类似的边界：它适合生成服务端和客户端一致的 DOM id，不适合拿来当列表的业务 key。

我现在判断一个 Hook 是否该留下，会先看它保护的边界：state 负责画面，ref 负责过程，effect 负责外部系统，memo 只负责已经测出来的重复工作，transition 负责更新的优先级。边界清楚后，代码里反而不需要到处出现 Hook。
