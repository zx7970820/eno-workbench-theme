---
title: "从 VNode 到 patch：Vue 2 虚拟 DOM 的一次更新是怎么落到真实节点的"
slug: "vue2-virtual-dom-diff"
category: "前端工程"
date: "2023-11-08"
tags: ["Vue", "Vue 2", "虚拟 DOM", "diff"]
excerpt: "沿着 createElm、sameVnode、patchVnode 和 updateChildren 看 Vue 2 的虚拟 DOM 更新，重点解释 key 到底参与了什么。"
---

# 从 VNode 到 patch：Vue 2 虚拟 DOM 的一次更新是怎么落到真实节点的

“虚拟 DOM 会自动做 diff”这句话太宽了。读 Vue 2 的 patch 源码时，可以先盯住一次列表更新：哪些节点被创建了，哪些节点被复用了，哪些节点只是换了位置？答案取决于 VNode 的类型、key、子节点形态和模块钩子。把调用链压缩成四个动作，会比较容易跟下去：生成 VNode、创建真实元素、判断能否复用、更新子节点。

## VNode 只是一份带身份信息的描述

VNode 里通常有 `tag`、`data`、`children`、`text`、`key` 和 `componentOptions` 等字段。它不是 DOM，也没有浏览器节点的方法。render 函数先产生一棵 VNode 树，patch 再决定如何把这棵树映射到 DOM。

```js
{
  tag: 'li',
  key: 'task-42',
  data: { attrs: { class: 'done' } },
  children: [{ text: '整理日志' }],
}
```

首次挂载时没有 oldVnode，`createElm` 递归创建元素、设置属性、插入子节点，再调用模块和组件钩子。更新时则尽量复用旧节点，而不是整棵树重新 `innerHTML`。

## sameVnode 先回答“能不能继续改这个节点”

Vue 2 的 `sameVnode` 不只是比较标签名，关键条件包括 key、输入元素类型、注释状态和组件约束。下面省略了异步占位等少数分支，只保留常见元素比较；两个 VNode 不满足这些条件时，patch 会销毁旧节点并创建新的：

```js
function sameVnode(a, b) {
  return a.key === b.key &&
    a.tag === b.tag &&
    a.isComment === b.isComment &&
    !!a.data === !!b.data &&
    sameInputType(a, b)
}
```

这就是为什么 key 不是“为了消除 warning”。它参与节点身份判断，决定旧组件实例和 DOM 能不能继续使用。稳定 key 应来自业务身份；数组索引只在列表永远不插入、不排序、不删除时才勉强安全。

## patchVnode 更新的是差异，不是整棵子树

两个 VNode same 后，`patchVnode` 会先执行 prepatch 和模块 update 钩子，再处理文本或 children：

1. 新旧 VNode 相同，直接返回。
2. 新节点有文本且文本不同，更新 `textContent`。
3. 新旧都有 children，进入 `updateChildren`。
4. 只有一边有 children 时，创建或删除对应节点。

属性、class、style、事件和指令通过模块钩子分别更新。这样修改一个 class，不会顺便重建整个 `li`。

## updateChildren 是一套双端比较

Vue 2 维护 oldStart、oldEnd、newStart、newEnd 四个指针，优先比较四种首尾相同的情况；匹配不到时通过 key 建立旧节点索引，尝试把可复用的节点移动到新位置：

```text
oldStart ↔ newStart
oldEnd   ↔ newEnd
oldStart ↔ newEnd
oldEnd   ↔ newStart
```

双端命中时可以少做一次索引查找；乱序严重时才使用 key map。复用意味着保留 DOM、组件实例和本地状态，移动只是调整位置。没有 key 时，Vue 只能按位置猜测，输入框焦点和组件内部状态就容易跟错行。

## 为什么“虚拟 DOM”不等于一定更快

diff 本身也要执行 JavaScript、维护 VNode 和比较 children。如果更新很小、模板稳定，编译器和静态树提升能减少工作；如果每次 render 都创建大量全新对象，patch 仍然需要走完比较流程。真正的优化通常来自稳定的 key、合理的组件边界、减少无意义的响应式依赖和避免在 render 中做昂贵计算。

排查 Vue 2 列表问题时，可以按这个顺序看：VNode 的 key 是否稳定；sameVnode 是否命中；是属性模块更新慢，还是 children diff 慢；最后才考虑“要不要换成另一个渲染方案”。
