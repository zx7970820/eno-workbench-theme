---
title: "Webpack 编译流程全景：模块图、Loader、Plugin、Chunk 与持久化缓存"
slug: "webpack-compilation-pipeline"
category: "工具与效率"
date: "2022-04-09"
tags: ["Webpack", "构建系统", "Loader", "Plugin", "性能优化"]
excerpt: "沿着一次 Compilation，从入口解析到模块图、Chunk 生成、代码优化和持久化缓存，建立排查构建问题的统一坐标系。"
---

# Webpack 编译流程全景：模块图、Loader、Plugin、Chunk 与持久化缓存

当终端只报“某个 loader 返回了空内容”时，先删缓存通常没有用。更有效的办法是问：错误发生在模块转换、依赖图生成、优化，还是产物写出？Webpack 配置看起来是一堆选项，实际却是在描述一次 Compilation 要经过的流水线。

Loader、Plugin、代码分割和缓存各自站在不同阶段。先找到阶段，再看配置，排查会快很多。

## 先分清长期实例和本次构建

Compiler 代表一份 Webpack 配置对应的长期编译器实例；Compilation 代表一次具体构建。在 watch 模式下，一个 Compiler 会产生多次 Compilation。

这一区别很重要：插件如果把本次构建状态错误地挂在 Compiler 上，增量构建时可能读取上一次残留；而需要跨构建复用的缓存如果只放在 Compilation 上，又会在每次重编译时丢失。

## 从 Entry 开始生成模块图

Webpack 从入口解析依赖请求，将每个文件转换为模块，再递归分析它的静态依赖。解析过程要回答：

- 请求对应哪个真实文件？
- 使用哪个 Loader 链？
- 这个模块依赖了谁？
- 哪些依赖是同步，哪些是异步边界？

最终得到的不是文件列表，而是带有依赖边的模块图。`import()` 等异步边界会影响后续 Chunk 划分。

## Loader 是单模块转换管道

Loader 接收某个模块的源内容，并返回 JavaScript 或能被下一个 Loader 继续处理的结果。规则通常从右到左执行：

```js
module.exports = {
  module: {
    rules: [
      { test: /\.css$/, use: ['style-loader', 'css-loader'] }
    ]
  }
}
```

`css-loader` 先把 CSS 中的依赖转成模块语义，`style-loader` 再生成把样式注入页面的运行时代码。

Loader 的边界应保持清晰：它最适合处理“一个模块如何转换”。修改产物列表、注入运行时、分析整张图或在构建结束写报告，通常属于 Plugin。

## Plugin 通过 Hook 参与整条流水线

Webpack Plugin 是带有 `apply(compiler)` 的对象，通过 Tapable hooks 介入初始化、模块构建、优化、产物生成等阶段。

```js
class BuildMetaPlugin {
  apply(compiler) {
    compiler.hooks.thisCompilation.tap('BuildMetaPlugin', compilation => {
      compilation.hooks.processAssets.tap(
        { name: 'BuildMetaPlugin', stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_ADDITIONS },
        () => { /* 添加构建元数据 */ }
      )
    })
  }
}
```

写插件时应选择最晚但仍满足需求的 Hook，避免过早读取尚未稳定的数据，也避免在优化完成后再修改会破坏哈希或缓存假设的结构。

## Module、Chunk 与 Bundle 不应混用

- Module：图上的源码单元。
- Chunk：Webpack 根据入口和异步边界形成的模块分组。
- Bundle：最终写出的文件，通常由一个或多个 Chunk 及运行时代码组成。

`splitChunks` 操作的是 Chunk 组织，不是简单地“把 node_modules 拆成 vendor.js”。合理目标应来自缓存命中和加载瀑布：稳定且跨页面共享的代码适合独立缓存；过度切分会增加请求和运行时协调成本。

## Tree Shaking 为什么依赖静态语义

ES Module 的导入导出可以静态分析，Webpack 才能标记哪些导出被使用。最终删除死代码通常由压缩器完成。`package.json` 的 `sideEffects` 告诉构建器哪些文件即使导出未使用也必须保留。

错误地写成 `"sideEffects": false` 可能删除全局样式、polyfill 或注册逻辑。更稳妥的做法是显式保留：

```json
{
  "sideEffects": ["*.css", "./src/polyfills.js"]
}
```

## 持久化缓存存的是什么

Webpack 5 的 filesystem cache 可以跨进程保存模块与计算结果。缓存是否可复用，取决于源码、配置、Loader/Plugin 版本和构建依赖等输入是否变化。

```js
cache: {
  type: 'filesystem',
  buildDependencies: {
    config: [__filename]
  }
}
```

如果自定义 Loader 读取了额外配置却没有声明依赖，缓存可能返回过期结果。Loader 应通过依赖 API 告知 Webpack 它读取的文件，而不是把所有缓存问题归咎于 `node_modules/.cache`。

## 构建性能怎么排查

1. 输出 `stats`，确认时间花在解析、Loader、优化还是产物处理。
2. 检查重复模块、过宽的 Loader `include` 范围和昂贵 source map。
3. 确认 filesystem cache 生效，配置文件和环境变量没有每次变化。
4. 检查并行工具是否真的减少关键路径，而非增加序列化和进程通信。
5. 用 Bundle Analyzer 观察 Chunk，而不是只盯单个文件大小。

把“慢”定位到阶段后，优化才会从调参变成工程判断。
