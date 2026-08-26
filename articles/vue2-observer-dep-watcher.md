---
title: "Vue 2 响应式从 Observer 到 Watcher：一条属性是怎么通知到视图的"
slug: "vue2-observer-dep-watcher"
category: "前端工程"
date: "2021-06-09"
tags: ["Vue", "Vue 2", "Observer", "Watcher"]
excerpt: "沿着 Vue 2 的 Observer、Dep 和 Watcher 拆开一次属性读取与修改，理解 defineReactive 和异步更新队列。"
---

# Vue 2 响应式从 Observer 到 Watcher：一条属性是怎么通知到视图的

Vue 2 的响应式经常被概括成“用 `Object.defineProperty` 劫持 getter 和 setter”。这句话没错，但只说到了工具。读源码时更值得追的是一条完整链路：属性第一次被读取时，谁把 watcher 记下来；属性被写入时，谁找到这些 watcher；通知发出后，为什么不是立刻把所有组件都重新渲染。

## Observer 先把对象变成可观察的

实例初始化时，Vue 会递归观察 data。一个简化版的 `Observer` 大概这样：

```js
class Observer {
  constructor(value) {
    this.value = value
    def(value, '__ob__', this)
    if (Array.isArray(value)) {
      augmentArrayMethods(value)
      observeArray(value)
    } else {
      walk(value)
    }
  }
}
```

`walk` 遍历对象的已有 key，对每个属性调用 `defineReactive`。数组则通过替换 `push`、`splice` 等变异方法，在插入新元素时继续 observe；直接用索引赋值不会被捕获，修改 `length` 也不会触发更新。需要改索引时用 `Vue.set` / `$set`，删除或截断数组则用 `splice`。

## Dep 记录“谁读过这个属性”

每个响应式属性都有一个 `Dep`，内部是订阅者集合：

```js
function defineReactive(obj, key, value) {
  const dep = new Dep()
  let childOb = observe(value)

  Object.defineProperty(obj, key, {
    get() {
      if (Dep.target) {
        dep.depend()
        childOb && childOb.dep.depend()
      }
      return value
    },
    set(next) {
      if (next === value) return
      value = next
      childOb = observe(next)
      dep.notify()
    },
  })
}
```

这里的 `Dep.target` 不是全局永远指向一个 watcher，而是通过栈在求值期间临时设置。渲染 watcher 执行 render 时读取 `user.name`，getter 就把当前 watcher 加入 `dep.subs`；render 结束后恢复上一个 target，避免嵌套计算把依赖收错。

## Watcher 把依赖变成可执行的工作

Watcher 有一个 getter，执行它会触发依赖收集；也有 `update`、`run` 和去重相关的逻辑。属性 setter 调用 `dep.notify()` 时，并不是马上递归调用所有 render，而是先让 watcher `update`：

```js
update() {
  if (this.lazy) {
    this.dirty = true
  } else if (this.sync) {
    this.run()
  } else {
    queueWatcher(this)
  }
}
```

队列通过 id 去重，并在一次 tick 中批量 flush。浏览器环境优先使用 Promise，其次是 MutationObserver、setImmediate 或 setTimeout。`nextTick` 的意义不是“让代码变快”，而是把同一轮同步修改合并后再执行一次更新。

## 这套模型的边界

- 初始化后新增的对象属性没有 getter/setter，需要 `$set`。
- 深层对象会递归 observe，数据量大时初始化和修改都可能有成本。
- 计算属性、用户 `$watch` 和渲染各自创建 watcher，不能只用“一个依赖列表”解释所有更新。
- setter 只负责发通知，真正的执行顺序由 watcher 队列决定。

看懂这条链之后，调试 Vue 2 的更新问题会有一个很实用的顺序：先确认属性是否可观察，再确认 getter 是否真的被读取，最后检查 watcher 是否被排队和合并。不要一看到页面更新就先怪虚拟 DOM。
