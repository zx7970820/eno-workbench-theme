---
title: "React 列表卡顿的真正原因：不是把 key 换成 index"
slug: "react-list-performance-review"
category: "前端工程"
tags: ["React", "性能", "列表"]
date: "2026-02-07"
excerpt: "一次长列表评审里，真正的问题是无效重渲染和过大的上下文，而不是表面上的 key 警告。"
---

# React 列表卡顿的真正原因：不是把 key 换成 index

列表卡顿时，最显眼的往往是 `key={index}`。把它改成 `key={item.id}` 通常是正确修复，但它解决的是列表状态错位，不是输入掉帧。两个问题要分开测，不能因为 warning 消失，就认为性能问题也结束了。

这很正常。`key` 解决的是协调阶段的身份问题，不能替你减少组件函数执行，更不能替你减少浏览器布局和绘制。真正的排查要从一次输入事件开始，把状态更新、列表计算、行组件渲染和 DOM 变化分开测。

## 先把“慢”定位到一层

先用 React Profiler 录一次从输入到结果更新的过程，再用 Chrome Performance 看主线程。常见结果是：每次输入都重新构造完整的 `rows` 数组，父组件把过滤、格式化和高亮文本全部做完后，再把新的对象传给每一行。行组件虽然包了 `memo`，但 `item` 和 `onSelect` 每次都是新引用，所以没有一行能跳过。

```tsx
const rows = data
  .filter((item) => item.name.includes(keyword))
  .map((item) => ({
    ...item,
    label: formatLabel(item),
  }))

return rows.map((row) => (
  <ResultRow key={row.id} item={row} onSelect={() => select(row.id)} />
))
```

这段代码并不“错”，只是把三个成本叠在了每一次按键上：过滤、创建对象，以及所有行重新渲染。修复时不要先堆更多 `memo`，可以先让输入状态只属于搜索框，再把列表计算放到一个有明确依赖的边界里：

```tsx
const filteredIds = useMemo(
  () => index.search(keyword),
  [index, keyword],
)

const handleSelect = useCallback((id: string) => {
  onSelect(id)
}, [onSelect])

return filteredIds.map((id) => (
  <ResultRow key={id} id={id} onSelect={handleSelect} />
))
```

行组件根据 `id` 从稳定的数据源读取内容，传入的 props 也变得简单。Profiler 的结果比代码看起来更重要：输入组件保持及时响应，行组件的渲染次数从每次几百次降到真正变化的几十次。

## key 的工作是保存身份

把 index 换成 id 仍然是对的，只是理由不是“性能更好”。当列表会插入、排序或过滤时，数组下标会随着位置变化。一个正在编辑的行如果因为排序跑到另一条数据的位置，React 可能复用原来的组件实例，输入状态就跟着错位。

```tsx
// 只有顺序永久不变、也不增删的静态列表才勉强适用
items.map((item, index) => <Row key={index} item={item} />)

// 业务列表应该使用稳定且唯一的身份
items.map((item) => <Row key={item.id} item={item} />)
```

回归时专门测试三种操作：在顶部插入一条、删除当前编辑项、把筛选条件从“全部”切到一个子集。只要输入值、展开状态或选中状态跑到了别的行，key 就是不稳定的。这个测试和性能测试是两件事，不应混在一起。

## 什么时候才值得窗口化

修复引用稳定后，结果列表仍然有 8000 行。此时每次筛选虽然不再全部重渲染，但首次展示和滚动仍然超过预算，才有理由考虑窗口化。窗口化只保留可视区域附近的行，它降低了 DOM 数量，却也带来真实的产品约束：动态高度要测量，键盘导航要知道当前项，浏览器查找无法找到尚未挂载的文本，屏幕阅读器的集合语义也需要重新确认。

固定行高可以从一个很小的实现开始：

```tsx
const start = Math.max(0, Math.floor(scrollTop / ROW_HEIGHT) - 4)
const end = Math.min(items.length, start + visibleCount + 8)
const visible = items.slice(start, end)

return (
  <div style={{ height: items.length * ROW_HEIGHT }}>
    <div style={{ transform: `translateY(${start * ROW_HEIGHT}px)` }}>
      {visible.map((item) => <Row key={item.id} item={item} />)}
    </div>
  </div>
)
```

这个实现依赖固定高度，遇到换行标题就会产生跳动，所以可以先给标题设置可测量的上限，再决定是否引入成熟库。一个只显示几十行的页面不应该为了“看起来先进”而承担窗口化的复杂度。

## 用真实交互做回归

最后保留一组小型回归数据：长标题、缺少头像、重复更新、快速筛选和键盘上下移动。测试不仅记录每秒帧数，也检查筛选后焦点是否还在输入框、按 Enter 是否仍能打开当前项、列表更新时是否给辅助技术一个合理的状态提示。

这次修改最有价值的结论不是某个 Hook，而是排查顺序：先用稳定身份保证正确性，再用 Profiler 找到无效工作，最后根据真实数据量决定是否窗口化。性能优化只有在交互没有变坏、回退路径仍然存在时，才算完成。
