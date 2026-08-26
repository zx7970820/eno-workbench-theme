---
title: "script 标签的加载顺序：async、defer、module 和资源提示"
slug: "script-loading-attributes"
category: "前端工程"
date: "2026-08-22"
tags: ["浏览器", "JavaScript", "性能", "HTML"]
excerpt: "把 script 的执行时机、模块脚本、CSP 和优先级属性拆开，再说明 dns-prefetch、preconnect、preload、prefetch 到底属于哪一种资源提示。"
---

# script 标签的加载顺序：async、defer、module 和资源提示

看到 head 里一串 script，最容易掉进属性背诵：async 是什么，defer 是什么，preload 要不要加。实际排查白屏或加载顺序时，我只先画一条时间线：HTML 解析在哪停，脚本什么时候执行，以及它和旁边的脚本有没有先后关系。

比如下面三行，下载都可能和 HTML 解析重叠，但执行时机完全不同：

```html
<script defer src="/runtime.js"></script>
<script async src="/analytics.js"></script>
<script type="module" src="/main.js"></script>
```

还有一个经常被混在一起的问题：dns-prefetch、preconnect、preload 和 prefetch 并不是 script 标签上的属性，它们是 link 的 rel 提示。它们只影响资源准备或请求时机，不会替你执行 JavaScript。

## 先把 link 提示和 script 属性拆开

```html
<link rel="dns-prefetch" href="//cdn.example.com">
<link rel="preconnect" href="https://cdn.example.com">
<link rel="preload" href="/app.js" as="script">
<link rel="prefetch" href="/next-page.js" as="script">
```

dns-prefetch 只提前做域名解析，成本最低，但后面仍然要建立连接。preconnect 会进一步准备连接，可能包含 TCP 和 TLS；如果这个域名最终没有真正请求，提前握手就是浪费。

preload 表示“当前页面很快就会用到这个资源”。它会把资源提前放进加载队列，但不会执行脚本。as、URL、crossorigin 等信息必须和真正的 script 请求匹配，否则会出现重复请求或预加载警告。

prefetch 更像“下一页也许会用到”。它通常使用较低优先级，适合用户下一步很可能访问的路由，不适合拿来抢当前首屏资源。模块入口还可以用 modulepreload，它针对的是模块图的加载。

## 不写属性的经典 script 会挡住解析

```html
<script src="/app.js"></script>
<main id="app"></main>
```

HTML 解析器读到这个外链脚本时，通常要等脚本下载、编译并执行，才能继续读到后面的 main。脚本可以调用 document.write，也可以读取前面已经创建的节点，所以浏览器不能把它当作普通图片那样只管下载。

内联经典脚本没有下载阶段，但执行仍然发生在解析器当前位置：

```html
<script>
  document.documentElement.classList.add('js')
</script>
```

它适合很短、必须尽早执行的初始化逻辑；业务代码通常应该显式选择加载时机。

## async：下载不挡解析，谁先到谁先执行

```html
<script async src="/analytics.js"></script>
<script async src="/ads.js"></script>
```

async 让外链脚本在 HTML 继续解析时并行下载。脚本一旦下载完成，就会暂停解析并立即执行；两个 async 脚本的执行顺序取决于谁先准备好，不由它们在 HTML 中的排列顺序决定。

因此 async 适合彼此独立的脚本，例如统计、广告或只向全局发送一次事件的代码。它不适合下面这种依赖关系：

```html
<script async src="/config.js"></script>
<script async src="/app.js"></script>
```

app.js 可能比 config.js 更早执行。把两个 async 换成 defer，或者把它们放进一个模块依赖图，才是在表达真正的先后关系。

## defer：并行下载，解析完成后按顺序执行

```html
<script defer src="/runtime.js"></script>
<script defer src="/app.js"></script>
```

经典外链脚本上的 defer 也不会挡住 HTML 解析，但执行会等文档解析完成，再按它们在文档中的顺序执行。defer 脚本执行完后，浏览器才会触发 DOMContentLoaded。

这使它很适合依赖 DOM 的应用入口，也比“把 script 放到 body 尾部”更明确：下载可以尽早开始，执行仍然有稳定顺序。defer 只对经典外链脚本有这个语义，内联脚本不能靠它获得同样的下载行为。

## type="module"：默认就是延后执行的模块

```html
<script type="module" src="/src/main.js"></script>
```

模块脚本有自己的模块作用域，不会把顶层变量直接放到 window 上；它默认采用类似 defer 的时机，不会因为放在 head 里就立刻挡住 HTML 解析。浏览器还要根据 import 语句继续加载模块依赖，等模块图满足执行条件后再运行入口。

模块内部可以使用静态 import 和 export：

```js
import { mount } from './app.js'

mount(document.querySelector('#app'))
```

模块脚本的依赖请求是异步的，但这不等于“模块里的所有代码都自动并发执行”。模块图仍然有依赖顺序，顶层 await 还会让依赖它的模块等待异步结果。

给模块脚本加 async 后，含义会改变：它不再等待整个文档解析结束，而是当自身模块图准备好就执行。多个 async 模块同样没有相互的执行顺序，除非它们通过 import 建立依赖。

## nomodule：给不支持模块的旧浏览器留后路

```html
<script type="module" src="/app.js"></script>
<script nomodule defer src="/legacy-app.js"></script>
```

支持 ES modules 的浏览器会跳过带 nomodule 的经典脚本；不支持模块的旧浏览器则执行它。现在多数项目不再专门维护这套 fallback，但在需要覆盖旧环境的页面里，它仍然是一个明确的能力分流点。

## 其他属性分别解决什么问题

### src 和 type

src 指向外部脚本地址；没有 src 时，script 的文本内容就是要执行的代码。type 决定脚本如何解释：省略或写经典 JavaScript 类型是普通脚本，module 是 ES module，importmap 和 speculationrules 则是浏览器识别的声明性数据格式，不是普通可执行脚本。

### crossorigin、integrity 和 referrerpolicy

crossorigin 决定跨源脚本请求是否使用 CORS 模式以及是否发送凭据。跨源脚本没有合适的 CORS 响应时，加载会失败。

integrity 是 Subresource Integrity 校验值。浏览器下载脚本后会计算哈希，内容和声明不一致就不会执行。它适合固定版本的 CDN 资源，动态文件不能随意复用旧 hash。

referrerpolicy 控制请求中的 Referer 信息，例如只发送 origin，或在跨源降级时不发送完整路径。它不改变脚本执行顺序，只改变请求携带的来源信息。

### fetchpriority

fetchpriority="high|low|auto" 是给浏览器的相对优先级提示，不是强制命令。它可以帮助浏览器在很多资源竞争时更早或更晚考虑某个脚本，但不能把一个本来阻塞解析的脚本变成非阻塞脚本，也不能代替 async 或 defer。

### nonce 和 CSP

启用 Content Security Policy 后，内联脚本通常需要一个服务端生成的 nonce：

```html
<script nonce="server-generated-value">
  boot()
</script>
```

nonce 不能写成固定字符串，也不要把它放进可被缓存到公共页面里的源码。它解决的是“这段内联代码是否被策略允许”，不是“这段代码什么时候执行”。

### blocking

现代浏览器还提供 blocking="render" 这样的声明，用来明确某个 head 中的资源是否阻塞首次渲染。它的支持范围和使用场景比 async、defer 更窄，不能把它当成所有脚本的通用加速开关。先把脚本依赖和首屏必要性理清，再考虑这种精细提示。

charset 和 language 这类旧属性已经没有必要添加。HTML 文档的编码应该由响应头和文档本身声明，JavaScript 类型也不需要靠 language 属性识别。

## 最后通常只留下这些声明

- 独立统计或广告：async。
- 依赖 DOM、又不想阻塞解析的经典入口：defer。
- 现代应用入口：type="module"，让 import 表达依赖。
- 当前页面马上要用的关键脚本：谨慎使用 preload as="script"，并保证后续请求能复用它。
- 下一路由可能用到的代码：考虑 prefetch，但先确认带宽和缓存成本。
- 只是提前连接第三方域名：preconnect；只想提前解析：dns-prefetch。

属性不是越多越专业。真正有用的是让 HTML 里的声明和脚本之间的依赖、执行时机、错误策略保持一致。否则一边给 app.js 加 preload，一边又让它用 async 乱序执行，网络面板看起来很忙，页面却不一定更快。
