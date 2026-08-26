---
title: "从 IIFE 到 ESM：JavaScript 模块化是怎么走到今天的"
slug: "javascript-module-systems-history"
category: "前端工程"
date: "2026-08-20"
tags: ["JavaScript", "模块化", "ESM", "Vite"]
excerpt: "从早期的 IIFE 和 script 顺序，到 AMD、CMD、CommonJS，再到浏览器原生 ESM，沿着每一代模块方案解决的问题看它们的差别。"
---

# 从 IIFE 到 ESM：JavaScript 模块化是怎么走到今天的

同一个 request 模块，放在早期的页面、Node 服务和今天的浏览器项目里，写法会完全不同。变化不只是把 require 换成 import，而是“谁声明依赖、谁负责加载、什么时候执行”这三个问题换了答案。

先假设模块只做一件事：给请求方法包一层 load。下面沿着这个小功能，把几代写法放在一起看。历史顺序会保留，但重点放在每次迁移到底解决了什么，而不是把 API 名称挨个背一遍。

## IIFE：先把自己的变量藏起来

早期页面最直接的组织方式是多个 script：

```html
<script src="/jquery.js"></script>
<script src="/app.js"></script>
```

app.js 默认和页面共享全局作用域。文件多起来以后，变量名会互相覆盖，执行顺序也只能靠 HTML 排列保证。IIFE（Immediately Invoked Function Expression）先解决了“不要把局部变量漏到 window”：

```js
(function (global) {
  var cache = {}

  function get(key) {
    return cache[key]
  }

  global.appCache = { get: get }
}(window))
```

函数立即执行，cache 和 get 只留在函数内部，明确暴露的 API 才挂到 global 上。依赖仍然是隐含的：这段代码假设 window 上已经有它需要的对象，文件顺序写错就会得到 undefined。

IIFE 是隔离作用域的模式，不是模块加载器。它没有回答“依赖是谁”“什么时候下载”“循环依赖怎么办”，只是把全局污染的范围缩小了。

## AMD：把依赖写在模块入口

浏览器端的下一个问题是加载。RequireJS 推广的 AMD（Asynchronous Module Definition）用 define 声明模块，用 require 请求模块：

```js
define(['./request'], function (request) {
  return {
    load: function (url) {
      return request.get(url)
    }
  }
})
```

依赖数组在模块执行前就被加载器看见了。加载器可以异步发起多个请求，等依赖都准备好后再调用 factory；依赖顺序不再靠 script 标签的手写排列。

AMD 的形状很适合直接在浏览器工作，但它也有自己的代价：

- 依赖数组和 factory 参数要保持同一顺序，删一个名字就可能把参数错位。
- 文件里包着一层 define，调试时需要先理解加载器如何把模块映射到 URL。
- 依赖声明集中在函数外，模块很大时，声明和使用位置会拉开。

AMD 解决的是“浏览器如何异步加载依赖”，它不是 JavaScript 语言本身的标准。

## CMD：让依赖靠近使用位置

Sea.js 时代常说的 CMD（Common Module Definition）也是加载器约定，不是 ECMAScript 标准。它常见的写法是把 require 放到 factory 里：

```js
define(function (require, exports, module) {
  var request = require('./request')

  exports.load = function (url) {
    return request.get(url)
  }
})
```

CMD 的表达重点是“依赖跟着使用位置走”。需要时再调用 require，代码阅读起来更接近同步写法，也方便把一段逻辑的依赖放在它旁边。真正的下载和执行时机仍然由 Sea.js 这样的 loader 决定，不能把 CMD 代码里的 require 直接等同于 Node 的同步文件读取。

AMD 和 CMD 的差别，更多是模块定义和加载器的组织方式：

- AMD 通常把依赖数组放在 define 的第一个参数。
- CMD 通常在 factory 内用 require 获取依赖。
- 两者都需要额外的 loader，浏览器并不认识 define。

项目里遇到 AMD 或 CMD 时，先确认它用的是 RequireJS、Sea.js 还是构建工具的兼容层。只看 define 这一个词，无法判断完整行为。

## CommonJS：把模块运行时放到 Node

CommonJS 选择了另一条路：模块接口用 require、exports 和 module.exports 表达，加载默认是同步的：

```js
var request = require('./request')

module.exports = {
  load: function (url) {
    return request.get(url)
  }
}
```

这套模型很适合 Node 的文件系统和服务器启动过程：读取一个模块，执行它，把导出的值交给调用方。模块通常只初始化一次，后续 require 会命中缓存；条件分支里动态拼出来的 require 也因此成为构建工具分析的难点。

CommonJS 最容易踩的坑是 exports 和 module.exports 的关系：

```js
exports.load = load
// 等价于给 module.exports 增加属性

module.exports = load
// 直接替换整个导出值
```

把 exports 重新赋值，并不会替换 module.exports。需要导出一个函数本身时，应明确写 module.exports。

CommonJS 不是浏览器原生模块。Webpack、Browserify 等工具可以读取 require 和 module.exports，把它们转换成浏览器可执行的 bundle；这一步也是传统构建工具需要先遍历依赖图的原因之一。

## ESM：让模块成为语言和浏览器的一部分

ESM（ECMAScript Modules）把模块语法正式写进 JavaScript：

```js
import { get } from './request.js'

export function load(url) {
  return get(url)
}
```

它和前几种方案的关键差别不只是关键字换了：

- import 和 export 的结构是静态的，工具可以在不执行代码的情况下建立依赖图。
- 导出是 live binding，导出方的绑定变化时，导入方看到的是同一个绑定，而不是初始化时复制一份普通值。
- 模块有自己的作用域，顶层变量不会自动成为 window 属性，并且模块代码按严格模式执行。
- 浏览器可以用 script type="module" 直接加载模块，依赖通过 URL 继续请求。
- import() 返回 Promise，可以在运行时按需加载；top-level await 会让依赖它的模块等待异步结果。

浏览器原生 ESM 也不是“完全不需要网络规划”。每个 import 都可能对应一个请求；生产环境如果把几千个小模块原样发给浏览器，会产生大量往返。因此 ESM 解决了模块表达和加载语义，打包仍然有它的实际价值。

## 把同一个 request 模块一路改写

同一个 request 模块，在不同体系下大概会长这样：

```text
IIFE
  window.request = ...
  依赖靠 script 顺序和全局变量

AMD
  define(['./request'], function (request) { ... })
  依赖由 loader 异步加载

CMD
  define(function (require) { var request = require('./request') })
  依赖写在 factory 内，loader 决定实际加载

CommonJS
  var request = require('./request')
  module.exports = ...
  运行时同步加载，适合 Node

ESM
  import { get } from './request.js'
  export function load() { ... }
  语法是标准，浏览器和工具都能理解依赖图
```

这不是简单的“旧的不好、新的好”。IIFE 在一个很小的页面里可能最直接；AMD 解决了当时浏览器的异步加载；CMD 让依赖更靠近使用位置；CommonJS 让 Node 拥有了稳定的模块边界；ESM 则把静态模块图交给了语言和运行时。

## 为什么开发时 Vite 往往更快，生产环境又是另一回事

这里要先把一句常见说法改准确：Vite 的开发服务器是基于浏览器原生 ESM 的，不等于 Vite 的生产构建永远不打包，也不等于“只要用了 ESM 就一定比 Webpack 快”。

传统的 bundle-first 开发服务器通常要先从入口爬完整个依赖图，经过 loader 和 plugin，再产出浏览器能加载的 bundle；项目越大，第一次启动和改动后的等待越容易变长。

Vite 的开发路径把工作拆成两类：

1. 业务源码按浏览器请求按需转换，并以 ESM URL 提供。当前页面没访问到的模块，不必阻塞开发服务器启动。
2. node_modules 里的依赖先做预构建，把 CommonJS/UMD 转成 ESM，也把内部模块很多的依赖合成更少的请求。

文件修改后，Vite 可以沿着原生 ESM 的依赖边界做 HMR，不必每次都重新生成整份开发 bundle。启动快和热更新快，主要来自这条开发阶段的路径差异。

生产构建则是另一件事：Vite 仍会用 bundler 做代码分割、压缩、tree-shaking 和资源优化。Webpack 也支持 ESM、缓存和增量构建，所以不能把两者粗暴归纳成“ESM 快、Webpack 慢”。更准确的结论是：Vite 把浏览器已经能做的模块加载工作留给浏览器，把开发阶段不必要的全量打包推迟了；它在大型项目里的启动和 HMR 因此通常更轻。
