---
title: "React class 组件里的性能优化：shouldComponentUpdate 什么时候值得写"
slug: "react-class-lifecycle-scu"
category: "前端工程"
date: "2019-01-15"
tags: ["React", "生命周期", "shouldComponentUpdate", "性能优化"]
excerpt: "项目里的列表开始卡之后，我沿着 class 生命周期和 shouldComponentUpdate 查了一遍，最后发现比较本身也有成本。"
---

# React class 组件里的性能优化：shouldComponentUpdate 什么时候值得写

项目里的列表开始卡之后，我先盯着 class 组件的生命周期看了一遍。真正容易写错的不是 API，而是把生命周期当成副作用入口，或者把浅比较当成免费的优化。要看清它们的边界，先按挂载、更新、卸载的顺序走一遍，再看 `shouldComponentUpdate` 到底跳过了哪些工作。

## 先把一次更新按时间顺序排开

一个 class 组件第一次出现，大致会经历：

1. `constructor` 初始化 state 和实例字段。
2. `componentWillMount`（旧版本生命周期，不适合放请求、订阅这类副作用）。
3. `render` 返回元素描述。
4. DOM 提交后执行 `componentDidMount`。

父组件更新 props 或调用 `setState` 时，会重新进入 `render`。常见的更新生命周期是 `componentWillReceiveProps`、`shouldComponentUpdate`、`componentWillUpdate` 和 `componentDidUpdate`。React 16.3 之后，`componentWillMount`、`componentWillReceiveProps` 和 `componentWillUpdate` 都提供了 `UNSAFE_` 别名；`shouldComponentUpdate` 没有改名。看到这些旧的 `will*` 生命周期时，我会把它们当成“准备 render 的通知”，不会把一次性的外部操作塞进去。

更危险的写法是把请求放进 `componentWillReceiveProps`：父组件每次把新的 props 传下来，都可能再次进入这里，网络请求很容易重复，而且旧响应回来时未必还是当前参数。真正需要同步 DOM 或订阅外部资源的工作，应该放在提交后的 `componentDidMount` / `componentDidUpdate`，并在 `componentWillUnmount` 里清理。

## shouldComponentUpdate 到底跳过了什么

React 默认会调用当前组件的 `render`，然后继续协调它的子树。`shouldComponentUpdate(nextProps, nextState)` 返回 `false` 时，通常会跳过这个组件及其子树的 render 工作：

```jsx
class ResultRow extends React.Component {
  shouldComponentUpdate(nextProps) {
    return nextProps.item !== this.props.item
  }

  render() {
    return <li>{this.props.item.title}</li>
  }
}
```

这里依赖的是不可变更新。父组件修改某条记录时创建新对象，没改的记录保留原引用：

```js
this.setState(({ items }) => ({
  items: items.map(item =>
    item.id === id ? { ...item, selected: true } : item
  ),
}))
```

如果直接 `item.selected = true`，再把同一个数组传下来，子组件看见的仍是同一个引用，优化判断会把真实变化挡掉。性能优化不能改变数据更新的正确性；宁可多 render 一次，也不要显示旧数据。

`PureComponent` 做的是 props 和 state 的浅比较，本质上和一个通用版 `shouldComponentUpdate` 类似：

```jsx
class ResultRow extends React.PureComponent {
  render() {
    return <li>{this.props.item.title}</li>
  }
}
```

它只适合边界清楚的纯展示组件。嵌套对象、数组或函数只要每次 render 都新建，浅比较就会失效；如果为了让它返回 true 又写一份深比较，比较本身可能比 render 更贵。

## 一次列表卡顿为什么不是生命周期本身的错

把这个问题放回列表，Profiler 通常会看到父组件每次输入都重新计算过滤、格式化和分页，然后为每一行创建新的 `viewModel`。行组件即使有 `shouldComponentUpdate`，也会因为 `nextProps.row !== this.props.row` 而全部更新。

排查时先做三件事：把输入关键字和昂贵过滤拆开；把不变字段保持成稳定引用；给列表使用真实的业务 id，而不是数组索引：

```jsx
{rows.map(row => (
  <ResultRow key={row.id} item={row} />
))}
```

`key` 解决的是同级节点的身份，不会直接减少组件执行次数。索引 key 在插入、排序和过滤时会把状态错配到另一行，哪怕页面看起来“更快”，也可能把选中状态保错。

## 生命周期优化的边界

- `componentDidUpdate` 里根据 props 请求数据时，要比较前后值，并在需要时取消旧请求，否则会出现响应乱序。
- `setState` 是批量的，下一行代码里直接读 `this.state` 不一定是刚写入的值；依赖旧值时使用函数形式。
- `forceUpdate` 会绕过当前组件的 `shouldComponentUpdate`，它通常意味着状态边界设计出了问题，不是通用加速按钮。
- 先用 Profiler 找到重复 render 和真正耗时的计算，再决定是否加比较。每一道比较都有维护成本。

我最后留下的判断很简单：先用 Profiler 找到重复 render 和真正耗时的计算，再决定是否加比较。数据引用要稳定，副作用要位于提交之后，比较函数也要算进总成本里；没有测量就堆生命周期优化，通常只是把问题藏起来。
