---
title: "Vue Next beta 的响应式：顺着 effect、computed 和 scheduler 追一次更新"
slug: "vue3-reactivity-in-depth"
category: "前端工程"
date: "2020-06-18"
tags: ["Vue", "Vue 3", "响应式", "运行时"]
excerpt: "2020 年 6 月，Vue 3 还在 beta。我从一个只有 count 和 computed 的小例子开始，顺着 vue-next 的 effect、computed 和 scheduler 看一次更新到底经过哪些函数。"
---

# Vue Next beta 的响应式：顺着 effect、computed 和 scheduler 追一次更新

2020 年 6 月我翻 `vue-next` 源码时，最先卡住的不是 `Proxy`，而是一个很小的问题：`state.count++` 之后，到底是谁知道该重新执行？那时 Vue 3 还没有正式发布，仓库里的核心代码仍然是 beta 版本，下面的函数名和调用关系也按那一版来讲。

我从四个文件开始看：`packages/reactivity/src/effect.ts` 负责依赖收集，`computed.ts` 负责惰性计算，`runtime-core/src/apiWatch.ts` 负责 `watch`，`scheduler.ts` 负责把组件更新排进队列。把这条线走完，响应式就不再只是“Proxy 拦截了 get 和 set”这句概括。

## 先把一次 `count++` 拆开

先不用组件，直接写一个最小例子：

```ts
const state = reactive({ count: 0 })

effect(() => {
  console.log('render:', state.count)
})

state.count++
```

`effect` 创建后会先执行一次，所以控制台先打印 `render: 0`。执行回调时，当前 effect 会暂存在 `activeEffect` 里。读到 `state.count`，代理的 `get` 陷阱调用 `track(target, GET, 'count')`，把这个 effect 放进 `count` 对应的依赖集合。

`state.count++` 随后经过代理的 `set` 陷阱，调用 `trigger(target, SET, 'count')`。`trigger` 从同一个依赖集合里取出 effect，再决定直接执行它，还是交给 effect 自己提供的 `scheduler`：

```ts
type Dep = Set<ReactiveEffect>
type KeyToDepMap = Map<any, Dep>

const targetMap = new WeakMap<any, KeyToDepMap>()

function track(target, type, key) {
  if (!activeEffect) return

  let depsMap = targetMap.get(target)
  if (!depsMap) targetMap.set(target, (depsMap = new Map()))

  let dep = depsMap.get(key)
  if (!dep) depsMap.set(key, (dep = new Set()))

  if (!dep.has(activeEffect)) {
    dep.add(activeEffect)
    activeEffect.deps.push(dep)
  }
}

function trigger(target, type, key) {
  const depsMap = targetMap.get(target)
  const effects = depsMap && depsMap.get(key)

  effects && effects.forEach(effect => {
    if (effect.scheduler) effect.scheduler()
    else effect()
  })
}
```

这段代码是源码的缩写，但数据结构和方向没有变：对象和属性反向找到“谁读过我”。`WeakMap` 的第一层不会因为响应式系统一直持有对象而阻止回收，`Map` 用属性区分依赖，最后的 `Set` 用来去重 effect。

这里还有一个容易被忽略的边界：`effect` 本身并不会自动批量更新。没有传 `scheduler` 时，`trigger` 就会同步调用它。批量、去重和微任务队列是在组件运行时那一层加上的，不能把两层混成“Proxy 自带异步更新”。

## `activeEffect` 为什么不是一个普通全局变量

effect 可以嵌套。渲染一个组件时读到 computed，computed 又会执行自己的 getter effect；如果只有一个 `activeEffect`，内层执行结束后就不知道该把谁恢复回来。

`effect.ts` 里实际用了 `effectStack`。每次运行 effect，大致会做四件事：先清掉上一次留下的依赖，把自己压栈，设置成 `activeEffect`，执行函数；函数结束后再出栈，并恢复外层 effect。

```ts
function run(effect) {
  if (!effect.active) return effect.fn()
  if (effectStack.includes(effect)) return

  cleanup(effect)
  try {
    enableTracking()
    effectStack.push(effect)
    activeEffect = effect
    return effect.fn()
  } finally {
    effectStack.pop()
    resetTracking()
    activeEffect = effectStack[effectStack.length - 1]
  }
}
```

清理依赖是另一半。假设 effect 里有一个分支：

```ts
const state = reactive({ ok: true, left: 'A', right: 'B' })

effect(() => {
  console.log(state.ok ? state.left : state.right)
})
```

第一次执行会订阅 `ok` 和 `left`。如果之后 `ok` 变成 `false`，这次重新执行前要把旧的依赖删掉，再收集 `ok` 和 `right`。否则 `left` 以后即使不再被读取，修改它还是会把这个 effect 叫醒。`cleanup` 做的就是遍历 `effect.deps`，把当前 effect 从旧的 `Set` 里移除，然后清空这个数组。

这也是为什么依赖收集不能简单理解成“读过一次就永远订阅”。订阅关系是每次执行时根据实际读取重新建立的。

## `trigger` 处理的不只是 `set`

源码里的 `trigger` 接收操作类型，不只是一个属性名。给已有属性赋值是 `SET`，给对象增加新属性是 `ADD`，删除属性是 `DELETE`；数组长度和 `Map`、`Set` 的迭代还要额外通知对应的依赖。

例如下面的 effect 依赖的是对象的键集合，而不是某个固定字段：

```ts
effect(() => {
  console.log(Object.keys(state))
})

state.extra = true
```

新增 `extra` 时，`trigger` 不能只找 `extra` 这一项，因为上面的代码根本没有读取它。它还要找到“遍历依赖”，把这个 effect 一起通知。数组的 `length`、`for...of`，以及 `Map` 的 `keys()` 也走类似的特殊依赖。

读到这里就能看出，响应式系统真正维护的是一张依赖图，而不是给每个字段挂一个简单的回调。代理只是把读写动作接进这张图。

## scheduler 负责把更新从“现在”挪到“稍后”

组件的 render effect 不会采用上面那个同步执行的默认行为。运行时会给它传一个 scheduler，把要更新的组件包装成 job，再交给 `runtime-core/src/scheduler.ts`。

```ts
state.firstName = 'Ada'
state.lastName = 'Lovelace'
```

这两次赋值仍然会各自进入 `trigger`，但同一个组件 job 只需要排一次。beta 代码里的调度器做了几件很朴素的事：

- `queueJob` 先检查队列里是否已经有相同 job，避免重复加入。
- `queueFlush` 用一个已经 resolved 的 Promise 安排 `flushJobs`，所以刷新发生在当前同步代码结束后的微任务里。
- 队列按 job 的 id 排序，父组件通常先于子组件更新。
- `nextTick` 返回当前这次 flush 使用的 Promise，调用方可以等队列完成后再读 DOM。

所以这段代码里：

```ts
state.count++
console.log(element.textContent) // 这里可能还是旧内容

await nextTick()
console.log(element.textContent) // 组件 job flush 后再读
```

`nextTick` 不是让浏览器等一帧，也不是让 JavaScript 线程睡眠；它只是等 Vue 已经排好的那次微任务刷新完成。`flush: 'pre'`、`'post'` 和 `'sync'` 的 watcher 也都建立在同一个调度器上：默认在组件更新前排队，`post` 放到更新后，`sync` 则直接执行，不进入这个去重队列。

## computed 的 `_dirty` 到底在挡什么

`computed.ts` 里的 computed 是一个 lazy effect。它创建时不执行 getter，第一次读取 `.value` 才求值；依赖变化时也不马上重新求值，只把 `_dirty` 设回 `true`。

beta 代码的核心可以缩成这样：

```ts
class ComputedRefImpl {
  private _value
  private _dirty = true
  public effect

  constructor(getter) {
    this.effect = effect(getter, {
      lazy: true,
      scheduler: () => {
        if (!this._dirty) {
          this._dirty = true
          trigger(toRaw(this), SET, 'value')
        }
      },
    })
  }

  get value() {
    if (this._dirty) {
      this._value = this.effect()
      this._dirty = false
    }
    track(toRaw(this), GET, 'value')
    return this._value
  }
}
```

这里的 `_dirty` 不是“数据改了几次”，只表示缓存还能不能直接用。第一次读完之后它是 `false`；上游依赖变更，scheduler 把它改成 `true`，但 getter 仍然没有执行。下一次有人读 `.value`，才真正重新计算并把标记改回 `false`。

还有一层依赖转发：computed 自己的 effect 会订阅 `firstName`、`lastName` 等源字段，外层的 render effect 订阅的是 computed 的 `value`。上游变化时，computed scheduler 先变脏，再触发 `value` 这条依赖，外层 render 才知道要更新。只让 computed 自己变脏而不触发外层，页面不会跟着变。

这解释了两个常见现象。一个 computed 如果从来没人读取，上游变化不会反复执行它的 getter；同一轮里上游连续改几次，computed 也只会从“干净”切到“脏”一次。另一个现象是，computed getter 最好只做派生计算，不要在里面发请求或改别的状态，因为求值时机本来就是由读取者决定的。

## `watch` 和 `watchEffect` 已经不在同一层

看到 `watch` 时要从 `runtime-core/src/apiWatch.ts` 继续往下看，而不是把它当成 `effect.ts` 的另一个名字。

`watchEffect` 把传入函数本身当成 effect：函数立即执行，执行期间读到什么就订阅什么，依赖变化后再次执行。它适合“这段逻辑用到了哪些状态，就跟着这些状态变化”的场景：

```ts
const stop = watchEffect(onInvalidate => {
  const controller = new AbortController()
  onInvalidate(() => controller.abort())
  fetch(`/api/users/${userId.value}`, { signal: controller.signal })
})
```

`watch` 则先把 source 变成一个 getter，再比较这次 getter 的结果，变化后才调用回调：

```ts
watch(
  () => route.query.q,
  (next, prev, onInvalidate) => {
    // next 和 prev 是被观察值；这里适合做请求、日志或同步外部库
  },
)
```

beta 版本支持 ref、computed、getter、reactive 对象和 source 数组。直接 watch 一个 reactive 对象时会走深度遍历；`deep`、`immediate` 和 `flush` 会改变观察的边界或执行时机。回调里的 `onInvalidate` 用来取消旧请求或清理上一次副作用，`watch` 停止时也会执行清理函数。

这两个 API 的差别不是“一个新一个旧”：`watchEffect` 的依赖由函数读取决定，`watch` 的依赖由 source 明确指定，回调本身读取的其他状态不会偷偷变成 watch 的触发条件。

## 代理有边界，解构就会把边界切断

响应式失效有时不是依赖图的问题，而是读写绕开了代理：

```ts
const state = reactive({ count: 0 })
const { count } = state
```

`count` 在解构时只是取出一个普通值，之后再读它不会经过 `state` 的 `get` 陷阱。需要保留响应式容器时，使用 `toRef(state, 'count')` 或 `toRefs(state)`，让读取仍然落到原对象的属性上。

同样要注意 raw 对象和 proxy 的身份不是一回事。把 raw 对象存进一个集合、再拿 proxy 去查，可能得到不同结果；在业务代码里尽量沿着同一条边界使用 proxy，只有和第三方库交接时才明确转换。

## 我会怎样在源码里定位一次多余更新

现在再遇到“改一个字段却重跑很多东西”，我会按下面的顺序下断点，而不是先加几个 `computed` 试运气：

1. 在代理的 `get` / `set` 处确认实际读写的是哪个 target 和 key。
2. 进入 `track`，看当前 `activeEffect` 被放进了哪个 dep；进入 `trigger`，看它为什么被取出来。
3. 如果 effect 带了 scheduler，继续跟到 `queueJob`，确认队列里是否已经有同一个 job。
4. 最后在 `flushJobs` 和组件的 render effect 处确认到底执行了几次，而不是只看 DOM 结果。

这条路径能把“依赖收集过宽”“computed 反复变脏”和“队列里重复排 job”区分开。它们最后都表现成页面重新执行，但修法完全不同。

回头看 Vue Next beta 的这套设计，最有意思的并不是 `Proxy` 替换了 `Object.defineProperty`，而是它把三件事拆得很干净：`effect` 记录谁读了什么，`computed` 控制派生值何时重新算，scheduler 决定这些工作什么时候落地。2020 年 9 月 18 日 Vue 3.0 才正式发布；在那之前读 `vue-next`，先把这条调用链读通，比背一串 Composition API 名字更有用。
