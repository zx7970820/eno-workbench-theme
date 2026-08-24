---
title: "深入 Vue 3 响应式系统：依赖追踪、调度器与性能边界"
slug: "vue3-reactivity-in-depth"
category: "前端工程"
tags: ["Vue", "响应式", "运行时", "性能优化"]
excerpt: "从 targetMap 的依赖图开始，沿着 track、trigger、scheduler、computed 与 watch 拆开一次更新，并讨论分支清理、深层代理和外部状态集成的性能边界。"
---

# 深入 Vue 3 响应式系统：依赖追踪、调度器与性能边界

理解 Vue 响应式，不能只停在“Vue 3 使用 Proxy”。Proxy 只是拦截入口，真正决定正确性与性能的是三件事：读操作如何建立依赖，写操作如何找到订阅者，以及多个更新如何被调度和合并。

## 1. 响应式系统本质上是一张反向索引

假设渲染函数读取了 `state.user.name`。未来 `name` 被修改时，运行时必须快速找到需要重新执行的副作用。典型结构可以抽象为：

```ts
type Dep = Set<ReactiveEffect>

const targetMap = new WeakMap<object, Map<PropertyKey, Dep>>()
```

第一层用 `WeakMap` 关联原始对象，避免响应式系统阻止对象被垃圾回收；第二层按属性区分依赖；最后的 `Set` 保存需要重跑的 effect。它不是“数据指向视图”，而是“被读取的属性反向索引到所有消费者”。

```ts
let activeEffect: ReactiveEffect | undefined

function track(target: object, key: PropertyKey) {
  if (!activeEffect) return
  let depsMap = targetMap.get(target)
  if (!depsMap) targetMap.set(target, (depsMap = new Map()))
  let dep = depsMap.get(key)
  if (!dep) depsMap.set(key, (dep = new Set()))
  dep.add(activeEffect)
}

function trigger(target: object, key: PropertyKey) {
  const effects = targetMap.get(target)?.get(key)
  effects?.forEach(effect => effect.scheduler ? effect.scheduler() : effect.run())
}
```

真实实现还要处理数组长度、`Map`/`Set` 迭代、只读代理、嵌套 effect、自触发保护等边界，但这个模型已经解释了大多数现象。

## 2. 为什么需要 effect 栈和依赖清理

effect 可能嵌套：组件渲染过程中会读取 computed，computed 内部又执行自己的 effect。单一全局变量不足以恢复父级上下文，因此运行时需要栈。

更容易被忽略的是分支依赖：

```ts
watchEffect(() => {
  result.value = enabled.value ? sourceA.value : sourceB.value
})
```

当 `enabled` 从 `true` 变为 `false`，`sourceA` 不应继续触发这个 effect。每次执行前需要清理旧依赖，再依据本次实际读取重新收集。否则依赖集合只增不减，既造成无效更新，也形成隐性的内存压力。

## 3. trigger 不等于立即重渲染

如果一次同步操作连续修改多个状态，逐次渲染会浪费大量工作。Vue 把组件更新包装为 job，交给调度器去重，再在微任务中统一刷新。

```ts
state.firstName = 'Ada'
state.lastName = 'Lovelace'
// 两次 trigger，通常只产生一次组件更新
```

这解释了为什么修改状态后立刻读取 DOM 可能仍是旧值，也解释了 `nextTick()` 的用途：它等待当前更新队列完成，而不是让 JavaScript “暂停一帧”。

调度时机同样影响 watcher：`flush: 'pre'` 适合在组件 DOM 更新前处理派生逻辑，`flush: 'post'` 适合读取更新后的 DOM，`flush: 'sync'` 则绕过去重队列，只有在确实需要同步语义时才应使用。

## 4. computed 为什么既像值又像 effect

computed 内部维护一个惰性 effect。依赖变更时，它首先把自己标记为 dirty，而不是立即重新计算；下一次读取 `.value` 时才执行 getter，并缓存结果。

这带来两个结论：

1. computed 适合纯粹的派生数据，不适合包含网络请求或写状态等副作用。
2. 一个从未被读取的 computed，即使上游频繁变化，也不会重复做无用计算。

当 computed 链很长时，优化方向通常不是手动缓存，而是减少无意义的上游失效，并避免 getter 每次创建新的大对象。

## 5. 常见“失去响应式”并不是 Proxy 失效

```ts
const state = reactive({ count: 0 })
let { count } = state
count++
```

解构得到的是普通局部变量，后续读写不再经过源对象的代理陷阱。对于基本类型，可使用 `toRef`/`toRefs` 保留容器语义。另一个常见问题是混用原始对象和代理对象：两者身份不同，应尽量只在业务层使用代理版本。

## 6. 深层响应式不是免费的

大型不可变数据、编辑器文档树或外部状态机通常不需要 Vue 递归代理每一层。可以用 `shallowRef` 把边界缩到 `.value`，在外部状态变化时整体替换引用：

```ts
const documentState = shallowRef(loadLargeDocument())

function applyPatch(patch) {
  documentState.value = externalStore.apply(patch)
}
```

这不是“关闭响应式”，而是明确告诉运行时：内部结构由另一个系统负责，Vue 只观察根引用。

## 7. 调试应观察“谁追踪了谁”

遇到意外重渲染，不要先堆 `computed` 或 `watch`。在开发环境使用 `onRenderTracked`、`onRenderTriggered`，记录依赖的 target、key 和操作类型。重点回答：

- 渲染时意外读取了哪个大对象？
- 是 `set`、`add`、`delete` 还是迭代依赖触发？
- 某个 watcher 是否同时写回了自己的上游？
- 是否因每次创建新引用而使下游失效？

响应式优化的核心不是让 effect 更快，而是让依赖图更准确、让无关节点不进入更新路径。

## 参考资料

- [Vue 官方：Reactivity in Depth](https://vuejs.org/guide/extras/reactivity-in-depth.html)
- [Vue 官方：Reactivity API Core](https://vuejs.org/api/reactivity-core.html)
- [Vue 官方：Performance](https://vuejs.org/guide/best-practices/performance)
