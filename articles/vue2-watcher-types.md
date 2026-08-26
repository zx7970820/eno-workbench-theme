---
title: "Vue 2 的三类 Watcher：渲染、computed 和用户 watch 分别在等什么"
slug: "vue2-watcher-types"
category: "前端工程"
date: "2023-02-14"
tags: ["Vue", "Vue 2", "Watcher", "源码"]
excerpt: "用源码里的 lazy、user、deep 和 render 标记区分三类 watcher，说明它们的创建时机、触发方式与实际场景。"
---

# Vue 2 的三类 Watcher：渲染、computed 和用户 watch 分别在等什么

看到 `Watcher` 这个类时，先别把它理解成“watch API 的实现”。Vue 2 至少有三种常见用途：渲染 watcher 负责把模板变成 VNode，computed watcher 负责按需缓存派生值，user watcher 负责在变化后执行回调。它们共用依赖收集和队列，却有完全不同的调度语义。

## 1. 渲染 watcher：把组件更新接到视图

组件挂载时，Vue 会创建一个 render watcher。它的 getter 会执行 `updateComponent`，里面调用 render 函数并把新的 VNode 交给 patch：

```js
new Watcher(vm, updateComponent, noop, {
  before() {
    if (vm._isMounted && !vm._isDestroyed) {
      callHook(vm, 'beforeUpdate')
    }
  },
}, true)
```

最后一个参数把它标记成当前实例的 render watcher。模板读取的每个响应式字段都会把这个 watcher 加进 dep；字段 setter 通知后，它进入异步队列，下一轮 flush 时重新执行 render，再比较 VNode。

渲染 watcher 不应该承担请求和写 localStorage。render 需要尽量纯，副作用放在 `mounted`、`updated` 或明确的用户 watcher 中，否则一次更新可能被重复触发。

## 2. computed watcher：lazy + dirty 的缓存

computed watcher 创建时带 `lazy: true`，初始化不求值。组件读取 computed 时，如果 `dirty` 为 true 才执行 getter；依赖变化时只把 dirty 标记回来，等下一次读取：

```js
if (watcher.dirty) {
  watcher.evaluate()
}
if (Dep.target) {
  watcher.depend()
}
```

它适合“由当前状态派生一个值”，例如筛选后的列表、金额合计和格式化标签。computed 没有读取就不会计算，依赖不变就直接复用缓存。把请求放进 computed 是反模式，因为求值时机和次数并不等于用户动作。

## 3. user watcher：变化后执行副作用

`vm.$watch` 和选项里的 `watch` 会创建 `user: true` 的 watcher。默认情况下它保存旧值，在队列 flush 后调用用户回调；如果显式设置 `sync`，则会绕过这个队列：

```js
vm.$watch('query', (next, prev) => {
  saveToHistory(next)
})
```

`immediate` 让创建时先回调一次；`deep` 会递归读取对象，让嵌套字段也成为依赖；`sync` 让更新不进入异步队列。user watcher 可以做请求、持久化和第三方组件同步，但要处理竞态：新的 query 到来时，旧请求必须取消或通过序号丢弃结果。

## 三种 watcher 可能观察同一个字段

假设模板、computed 和 `$watch('query')` 都读取 `query`，同一个 dep 会有三个订阅者。setter 只负责通知，真正的执行顺序由 watcher id 和队列决定：父组件的渲染通常先于子组件，重复加入的 watcher 会被去重。不要用“setter 调用了几次”推断“页面 render 了几次”。

遇到重复请求时，不要先假设 watch 被触发了两次。更常见的路径是：用户 watcher 在回调里修改了另一个被 render 读取的字段，于是多出第二轮渲染。把请求前的状态修改移出回调，并给请求加取消控制，才能把两条更新链分开。

## 看到参数就能猜场景

- `lazy: true`：通常是 computed watcher，按需求值。
- `user: true`：来自 `$watch` 或 `watch` 选项，回调由用户提供。
- `deep: true`：需要递归 traverse，适合小型配置对象，不适合无边界的大树。
- `sync: true`：同步执行，只有明确知道队列合并会造成问题时才使用。
- `before`：render watcher 在更新前调用，常用于 `beforeUpdate` 生命周期。

这些标记不是 API 装饰，而是在改变 watcher 的成本和时序。读源码时先确认 watcher 是谁创建的，再看它的 getter 读取了什么，通常比从 `notify()` 一路单步更快。
