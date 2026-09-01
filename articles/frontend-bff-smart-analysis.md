---
title: "BFF 不是多套一层接口：从智能分析项目看前端服务边界"
slug: "frontend-bff-smart-analysis"
category: "前端工程"
date: "2026-08-30"
tags: ["BFF", "前端工程", "Node.js", "Egg.js", "接口设计"]
excerpt: "一个分析详情页为什么会越来越依赖后端细节？从智能分析机器人项目里的多服务数据编排，重新认识 BFF 该做什么、又不该做什么。"
---

# BFF 不是多套一层接口：从智能分析项目看前端服务边界

我印象比较深的一类页面，是智能分析机器人的任务详情页。

页面上展示的东西并不算特别多：任务状态、创建人、分析算子、结果摘要、权限和一些页面配置。可打开页面时，前端要知道的事情却越来越多：任务从哪里取，创建人要去哪个服务查，算子能不能批量拿，结果接口什么时候才允许调用，某个请求失败后页面还能不能继续显示。

刚开始把这些请求写在页面里，通常还能接受。项目再往前走一点，组件里就会出现一串 service 调用，接口之间还夹着 ID 传递和状态转换。页面不只是渲染数据，已经在编排后端服务了。

这就是我后来理解 BFF 的起点：不是为了少写几个请求，而是给某一种前端建立一个清楚的数据边界。

## 页面为什么会开始“知道太多”

最初的代码可能很直接：

```js
const task = await getTaskDetail(id)
const creator = await getUser(task.creatorId)
const operators = await getOperators(task.operatorIds)
const result = await getAnalysisResult(task.resultId)
```

很快就会发现，这段代码有两个问题。第一，四个请求不一定都要串行；第二，页面知道了每个后端服务的名字、字段和调用顺序。为了并行，代码大概会改成这样：

```js
const task = await getTaskDetail(id)

const [creator, operators, result, permission] = await Promise.all([
  getUser(task.creatorId),
  getOperators(task.operatorIds),
  getAnalysisResult(task.resultId),
  getPermission(id)
])
```

`Promise.all` 本身没有问题，真正值得警惕的是它放在了页面组件里。以后只要后端把 `creatorId` 改名，或者结果服务需要先换一次 token，前端页面就得跟着改。多个页面如果各自写一遍，改动还会成倍出现。

前端开始承担的其实是服务编排：

```text
浏览器
 ├─ 任务服务
 ├─ 用户服务
 ├─ 算子服务
 ├─ 分析结果服务
 └─ 权限服务
```

这种结构在服务少、页面少的时候很方便。它的问题不会在第一个接口出现时爆出来，而是在同一组数据被第二个、第三个页面复用时慢慢变明显。

## 后端模型和页面模型不是一回事

每个后端服务只看自己的领域，返回下面这样的数据很合理：

```json
{
  "task_id": 1024,
  "creator_id": 10086,
  "operator_ids": [1, 2, 5],
  "status": 2
}
```

用户服务返回用户，算子服务返回算子列表。可页面真正要表达的是：

```json
{
  "id": 1024,
  "creator": {
    "id": 10086,
    "name": "Eno"
  },
  "operators": [
    { "id": 1, "name": "敏感词" },
    { "id": 2, "name": "静音" }
  ],
  "status": "running"
}
```

页面关心“谁创建了任务”“启用了哪些算子”“现在是否还在分析”，并不关心 `status = 2` 是哪个服务的约定，也不想在模板里到处出现 `creator_id`。

这里有两种模型：后端服务使用的是领域模型，页面消费的是 UI 模型。BFF 做的第一件重要的事，就是把两者隔开：

```text
后端领域模型
      ↓ 聚合、转换、裁剪
      BFF
      ↓
前端 UI 模型
```

这层转换不是为了把所有字段改成好看的名字，而是明确“这个页面需要什么”。如果一个字段只是数据库表里存在、页面永远用不到，就不必穿过 BFF 继续暴露给浏览器。

## BFF 先做接口聚合，再做数据适配

BFF 的实现并不神秘。以任务详情为例，先取任务本身，再并发请求没有依赖关系的服务：

下面的 `statusMap` 只是项目接口契约里的状态映射，不是 BFF 自带的东西；真实项目里应该从服务约定出发，不能凭页面文案临时猜一个含义。

```js
async function getAnalysisDetail(id, request) {
  const task = await taskService.getDetail(id, {
    authorization: request.headers.authorization
  })

  const [creator, operators, result, permission] = await Promise.all([
    userService.getUser(task.creator_id),
    operatorService.getList(task.operator_ids),
    analysisService.getResult(task.result_id),
    permissionService.getForTask(id)
  ])

  return {
    id: task.task_id,
    creator: {
      id: creator.id,
      name: creator.nickname
    },
    operators: operators.map(item => ({
      id: item.id,
      name: item.name
    })),
    result,
    canEdit: permission.can_edit,
    status: statusMap[task.status]
  }
}
```

浏览器最后只请求一个页面需要的接口：

```js
const detail = await fetch(`/api/analysis/${id}`).then(res => res.json())
```

在追一科技参与智能分析机器人时，我接触到的正是这种前端和多个业务服务挨在一起的场景。Node.js / Egg.js 这类技术栈放在中间很顺手，因为前端团队可以用熟悉的 JavaScript 处理接口聚合、字段转换和鉴权信息透传。不过，Node.js 只是实现方式，BFF 这个词描述的是职责，不是语言。

鉴权也不能简单理解成把浏览器里的 token 原样转发给所有下游。到底由 BFF 校验、交给网关校验，还是换成内部服务凭证，要按现有的安全边界和服务契约来定；BFF 只负责把这件事放在一个可观察、可测试的位置。

## 失败时，BFF 不能只返回一个 500

把请求收拢到 BFF 后，错误处理也要重新想一遍。

任务基础信息失败，页面通常没有办法继续；权限失败，可能应该把编辑按钮收起来；结果摘要失败，任务和算子列表也许仍然值得显示。把所有请求都塞进 `Promise.all`，其中一个失败就全部 reject，页面只得到一个笼统的错误，这通常过于粗糙。

需要保留部分结果时，可以显式区分必需数据和可选数据：

```js
const [taskResult, optionalResult] = await Promise.allSettled([
  taskService.getDetail(id),
  analysisService.getResult(id)
])

if (taskResult.status === 'rejected') {
  throw new Error('任务不存在或暂时无法读取')
}

return {
  task: taskResult.value,
  result: optionalResult.status === 'fulfilled'
    ? optionalResult.value
    : null,
  resultUnavailable: optionalResult.status === 'rejected'
}
```

这不意味着 BFF 要替页面决定所有交互。它只把后端错误翻译成页面能处理的结果，页面再决定显示空状态、重试按钮还是降级内容。超时、日志和 trace ID 也应该在这一层统一补上，否则前端只能看到“请求失败”，却不知道是哪一个下游服务慢了。

## BFF 和 API Gateway 经常一起出现，但不是同一个东西

API Gateway 更关心统一入口：路由、鉴权、限流、流量和服务发现。BFF 关心的是某个前端真正怎么消费数据。

```text
浏览器
   ↓
前端 BFF（页面模型、聚合、展示相关兼容）
   ↓
API Gateway（路由、鉴权、限流）
   ↓
任务 / 用户 / 分析 / 权限服务
```

同一个后端系统可以有 Web BFF、移动端 BFF，也可以没有 BFF，直接让客户端调用网关。是否增加这一层，应该看客户端需要的模型是否已经和后端服务边界错开，而不是看到微服务就机械地加一个 Node 服务。

## BFF 最容易长成“第二个后端”

BFF 的边界大概可以这样画：

适合放在这里的，是接口聚合、字段转换、页面专属的裁剪、前端需要的兼容层，以及展示相关的权限组合。

不适合放在这里的，是订单状态的核心规则、复杂事务、领域数据的最终写入和另一套独立数据库。要是 BFF 开始保存自己的业务状态、决定核心规则，它就不再只是前端服务端，而是在重新实现一个后端。

我更愿意用一个问题来判断边界：这段逻辑是为了让某个前端拿到合适的数据，还是无论有没有这个前端，业务都必须这样运行？前者可以考虑 BFF，后者应该回到领域服务。

## 多一跳不一定更慢，但一定多一种责任

引入 BFF 后，链路从：

```text
浏览器 → 多个后端服务
```

变成：

```text
浏览器 → BFF → 多个后端服务
```

这确实多了一跳。可是原来浏览器需要在公网或跨网络环境里发起多次请求，BFF 和下游服务在同一内网时，服务间调用的成本可能更低；BFF 还可以把有依赖的请求串起来，把没有依赖的请求并发起来。

另一方面，BFF 也会成为新的故障面。它自己的超时、连接池、缓存和发布都要有人负责。如果所有页面都经过同一个 BFF，BFF 挂了就可能比一个下游接口挂掉影响更大。所以是否引入它，不能只看接口数量，还要看团队能不能承担这层服务的运行责任。

## 我现在会先问“页面需要什么”

回头看这类项目，我学到的并不是多会写几个 Egg.js controller，而是接口设计的出发点变了。

以前看到页面需求，我会先问“这个按钮调用哪个接口”；现在更常先列出页面需要的状态，再去确认这些状态分散在哪些服务里。这样才能判断：应该让页面自己请求，还是需要一个 BFF 把数据拼成一个真正适合阅读和交互的模型。

BFF 不会让后端服务变简单，也不会自动让页面变快。它解决的是边界问题：页面不必把服务拓扑、内部字段和调用顺序背在身上，而后端领域服务也不必为每个页面长出一套接口。这个边界一旦画清楚，后面再改服务，至少不用每次都从一个组件里的请求链开始排查。
