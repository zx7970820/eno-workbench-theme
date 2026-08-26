---
title: "Vue 2 computed 和 watch 的分界：dirty 标记如何避免重复计算"
slug: "vue2-computed-watch-dirty"
category: "前端工程"
date: "2022-07-18"
tags: ["Vue", "Vue 2", "computed", "watch"]
excerpt: "从 lazy watcher 和 dirty 字段开始，比较 computed 与 watch 的触发方式、缓存边界和各自适合解决的问题。"
---

# Vue 2 computed 和 watch 的分界：dirty 标记如何避免重复计算

读 Vue 2 的 `computed` 源码时，我总会在 `dirty` 这个布尔值上停一会儿：它只是“脏/不脏”两种状态，为什么能让 computed 像普通字段一样被反复读取，又不会每次都重新执行 getter？

我后来不再背“computed 有缓存、watch 做副作用”这句结论，而是顺着一条更新链去看：一次读取创建了什么依赖，依赖变化时谁收到通知，下一次读取之前又是谁把值算回来。顺着这条链，两个 API 的分工会清楚很多。

先记一个 Vue 2 的边界：已经在 `data` 里的字段可以直接赋值；给已经观测的对象新增属性，要用 `this.$set(obj, key, value)`（也可以写成 `Vue.set`），否则这个新属性没有对应的 getter/setter。

## 先看一次 computed 读取

初始化 computed 时，Vue 2 会为每个属性创建一个 `Watcher`，并设置 `lazy: true`。这个 watcher 先不求值，真正读取 computed 时才开始工作：

```js
function createComputedGetter(key) {
  return function computedGetter() {
    const watcher = this._computedWatchers[key]
    if (watcher) {
      if (watcher.dirty) watcher.evaluate()
      if (Dep.target) watcher.depend()
      return watcher.value
    }
  }
}
```

第一次读取时，`evaluate` 执行 getter，把结果存进 `watcher.value`，再把 `dirty` 设回 `false`。只要依赖没有变化，下一次读取就直接返回这个值，不会重新遍历列表，也不会重新拼字符串。

这里的关键是：依赖变化时，lazy watcher 的 `update` 并不马上执行 getter，而只是把 `dirty` 改回 `true`。下一次真的有人读取 computed 时，才重新计算。一个暂时没有显示的面板因此不会为了“保持最新”白算一遍。

## computed 自己收集依赖，还要把依赖交给渲染 watcher

模板渲染读取 `fullName` 时，外层的 render watcher 是 `Dep.target`。进入 computed getter 后，computed watcher 会临时成为当前 target，读取 `firstName` 和 `lastName`。求值结束时，`watcher.depend()` 再把这些底层 dep 转交给外层 render watcher。

这一步很容易漏掉。只有 computed watcher 自己有依赖还不够：将来 `firstName` 变化，视图 watcher 也必须能收到通知，否则 computed 虽然已经变脏，页面却不会更新。

如果 computed getter 返回一个新对象，缓存只能保证“依赖不变时同一个对象”，不能替你避免对象内部被修改；如果 getter 里混入当前时间、随机数或隐式全局状态，依赖图就不再可靠。

## watch 关心的是“变化之后做什么”

`watch` 创建的是 user watcher。它不负责提供一个可读取的派生值，而是在 source 变化后调用回调，适合请求、持久化、日志和同步第三方库：

```js
watch: {
  query: {
    immediate: true,
    handler(next, prev) {
      this.loadResults(next)
    },
  },
}
```

默认情况下，user watcher 会在队列 flush 后执行回调；`immediate` 让它创建后先执行一次；`deep` 会递归 traverse，把嵌套字段也纳入依赖；`sync` 则绕过异步队列。普通 watch 回调不要承担“把 A 计算成 B”的工作，那通常应该交给 computed。

## 把两个场景放在一起看

```js
// 派生值：使用 computed
computed: {
  total() {
    return this.items.reduce((sum, item) => sum + item.price, 0)
  },
}

// 外部副作用：使用 watch
watch: {
  total(next) {
    document.title = `订单金额 ${next}`
  },
}
```

`total` 只回答“当前值是多少”，`watch` 才回答“值变化后我要通知谁”。把格式化、请求和写回状态都塞进 watch，往往会多出一层中间数据；把请求放进 computed，又会让求值时机和副作用混在一起。

## dirty 只说明“下次读取要不要重算”

`dirty` 不是数据变化的计数器，也不表示 watcher 已经执行。它只表达两种状态：缓存可以用，或者下一次读取前需要重新求值。真正的执行顺序还要经过 dep 通知、队列去重和 flush。

排查重复计算时，我会分开看三件事：computed getter 实际执行了几次，底层 setter 通知了几次，render watcher 又 flush 了几次。只盯着页面 render，很容易把缓存失效、队列合并和视图更新混成同一件事。
