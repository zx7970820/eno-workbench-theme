---
title: "HTTP/1.0、1.1、2、3 到底差在哪：从一条请求看协议演进"
slug: "http-version-evolution"
category: "前端工程"
date: "2026-08-24"
tags: ["HTTP", "网络", "性能", "浏览器"]
excerpt: "不把 HTTP/1.0、1.1、2、3 写成一张特性表，从请求格式、连接复用、多路复用、队头阻塞、头部压缩和 QUIC 看它们各自解决了什么。"
---

# HTTP/1.0、1.1、2、3 到底差在哪：从一条请求看协议演进

在浏览器 Network 面板里，同一个站点的请求可能标成 `http/1.1`、`h2` 或 `h3`。最容易写错的版本对比，是把它们当成“换了一套 fetch API”。实际上，GET、POST、状态码、缓存和 Cookie 这些 HTTP 语义并没有换一套；变化主要发生在消息如何编码、连接如何复用，以及丢包时哪些请求会一起等。

下面把大家口中的“HTTP/1”具体按 HTTP/1.0 来讲，再和 HTTP/1.1、HTTP/2、HTTP/3 放在同一条演进线上。重点不是背版本号，而是看每次升级到底在补哪一个缺口。

## 先把一条 HTTP/1.1 请求放在桌上

先看一条很普通的请求。它没有任何框架味道，浏览器、curl、Node 的 HTTP 客户端最终都要表达出类似的信息：

```http
GET /api/profile HTTP/1.1
Host: example.com
Accept: application/json
Cookie: session=abc123

```

请求行说明方法、目标和版本，后面是字段，空行表示头部结束，必要时再跟消息体。响应也有自己的状态行、字段和消息体：

```http
HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: 27

{"name":"eno","ready":true}
```

HTTP/2 和 HTTP/3 仍然要表达同一组方法、目标、字段和内容，只是不会把这些字符原样放在线路上。它们会把头部和数据拆进二进制帧，再交给各自的连接和流处理。理解这一点，后面就不会把“协议格式变了”误解成“业务接口也要重写”。

## HTTP/1.0：先把资源送过来，连接用完就关

HTTP/1.0 的典型请求可以很短：

```http
GET /index.html HTTP/1.0
User-Agent: old-browser

```

在默认行为下，一次请求和响应结束，TCP 连接也跟着关闭。页面里的 CSS、脚本和图片通常要重新建立连接，每次都可能重复经历 TCP 握手；如果外面还有 TLS，还要再加上 TLS 握手的等待。

这里有个容易被一句话带偏的地方：HTTP/1.0 并不是“绝对不能复用连接”。`Connection: keep-alive` 在当时被很多客户端和服务器当作扩展使用，只是它不是 HTTP/1.0 的默认模型，双方还要约定好如何判断响应结束。没有可靠的消息长度时，关闭连接本身也常被用来表示响应已经结束。

HTTP/1.0 的消息是文本格式，头部字段直接可读；消息体通常靠 `Content-Length` 或连接关闭来划界。`Transfer-Encoding: chunked` 不是它的标准解法，所以服务端在不知道完整长度时，处理空间比后来小很多。

它解决了“不同机器之间传输文档”的基本问题，但没有为后来那种一个页面几十个资源的加载方式准备好连接模型。

## HTTP/1.1：连接复用成为默认，Host 和 chunked 补上缺口

HTTP/1.1 首先改变了连接的默认态：只要没有明确写 `Connection: close`，客户端和服务器就可以在同一条 TCP 连接上继续传下一个请求。连接变暖以后，握手成本不用每个资源都付一遍。

```http
GET /app.js HTTP/1.1
Host: example.com
Accept: */*

GET /app.css HTTP/1.1
Host: example.com
Accept: text/css

```

`Host` 也成为 HTTP/1.1 请求的必需字段之一。这样同一个 IP 上可以托管多个域名，服务器能根据 Host 选择站点。HTTP/1.0 客户端可能也会发送 Host，但那不是它的必需语义。

另一个实用变化是分块传输。响应暂时不知道完整大小时，可以用 `Transfer-Encoding: chunked`，每个块先写十六进制长度，最后用长度为 0 的块收尾：

```http
HTTP/1.1 200 OK
Transfer-Encoding: chunked

4
Wiki
5
pedia
0

```

这样服务端可以边生成边发送，同时保持连接继续复用。它和压缩不是一回事：chunked 解决消息边界，gzip 或 br 解决内容编码，两个概念不要混在一起。

HTTP/1.1 还定义了 pipelining：客户端可以在同一条连接上先发出多个请求，不必每次都等前一个响应的全部字节。但响应必须按请求顺序返回。如果排在最前面的响应很慢，后面的响应即使已经准备好，也不能越过它送到客户端，这就是 HTTP 层面的队头阻塞。

主流浏览器没有把 pipelining 当成常规方案，而是打开多条 TCP 连接来并行拉资源。这样能把一个慢请求的影响隔离到某条连接上，但连接数增加也会带来更多握手、拥塞控制和服务器资源开销。HTTP/1.1 的根本限制仍然是：一条连接上没有真正的独立流，文本消息也没有统一的多路复用层。

## HTTP/2：消息拆成帧，多条流共用一条 TCP

HTTP/2 没有重新发明 GET 或 200。它在 HTTP 语义和传输之间加了一层二进制 framing：

```text
HTTP/2 connection
  ├─ stream 1: HEADERS + DATA   /index.html
  ├─ stream 3: HEADERS + DATA   /app.css
  └─ stream 5: HEADERS + DATA   /app.js
```

一个请求/响应交换对应一个 stream，stream 里的数据再拆成 HEADERS、DATA 等帧。不同 stream 的帧可以交错发送，所以一条连接就能同时承载多个请求，不需要靠“开六条连接”来制造并行度。

HTTP/2 还带来了 HPACK 头部压缩。浏览器连续请求资源时，`accept-encoding`、Cookie 或用户代理等字段会反复出现；静态表和动态表可以把重复字段换成更短的索引，减少线上重复字节。压缩的是字段表示，不是 JSON 响应体；响应体是否 gzip 或 br 仍由内容编码协商决定。

HTTP/2 的线速格式是二进制，不能再像 HTTP/1.1 那样手写一段文本请求。但应用层并没有因此失去兼容性，服务器框架拿到的仍然是方法、路径、字段和 body。HTTP/2 规范还定义过 Server Push，只是它在浏览器里很难正确处理缓存和重复资源，主流浏览器已经基本移除或默认关闭，今天不该把它当成启用 HTTP/2 的主要理由。

需要留意的是，HTTP/2 的多路复用建立在一条 TCP 连接上。TCP 提供的是有序字节流：如果某个 TCP 包丢了，后面的字节即便属于其他 stream，也要等重传的数据补齐后才能交给上层。于是 HTTP/2 消除了 HTTP/1.1 那种“一个响应排在队首”的阻塞，却仍然会受到 TCP 层队头阻塞的影响。

另外，`Connection`、`Keep-Alive` 这类逐跳字段不是拿到 h2 连接后继续照抄的配置。HTTP/2 和 HTTP/3 对连接级字段有更严格的限制，代理也可能因此直接拒绝请求。

## HTTP/3：把多路复用放进 QUIC

HTTP/3 继续使用 HTTP/2 证明过的帧和流思路，但底层不再是 TCP，而是 QUIC。QUIC 建在 UDP 之上，却不是“把 TCP 可靠性删掉”：它提供加密、确认、重传、拥塞控制、流量控制和按流的有序字节流，HTTP/3 只需要把一个请求映射到一个 QUIC stream。

```text
HTTP/3
  └─ QUIC connection
      ├─ stream 0: control
      ├─ stream 4: request / response A
      ├─ stream 8: request / response B
      └─ stream 12: request / response C
```

同一条 QUIC 连接里的 stream 可以交错传输。某个包丢失时，受影响的 stream 需要等自己的数据补齐，但其他 stream 不必因为 TCP 的全连接有序交付而一起停住。这是 HTTP/3 对 HTTP/2 最重要的性能改动。

QUIC 的安全握手由 TLS 1.3 完成，而不是先建立 TCP 再额外套一层 TLS。恢复连接时，QUIC 还可以使用 0-RTT 发送早期数据，但这不是每次请求都能享受的快捷方式：服务端可以拒绝，早期数据还有重放风险，不能把非幂等操作不加判断地塞进去。

HTTP/3 使用 QPACK 压缩字段。它保留 HPACK 的静态表和动态表思路，但把压缩表更新放到独立的单向流里，适应 QUIC 的乱序到达。QPACK 能降低压缩造成的队头等待，却不等于所有字段都永远不会阻塞；如果某个 stream 引用了尚未到达的动态表项，它仍可能暂时等着解码条件满足。

## “队头阻塞”其实被解决了三次

把三个版本放在一起，队头阻塞至少要分三层看：

1. HTTP/1.1 的一条连接上，响应按顺序排队。pipelining 只是提前发请求，没有改变响应顺序；多开几条连接是工程上的绕开办法。
2. HTTP/2 把请求拆到不同 stream，应用层可以交错帧，解决了上一层的排队。但一条 TCP 连接丢包时，所有 stream 都可能被 TCP 的有序交付拖住。
3. HTTP/3 把独立 stream 下沉到 QUIC，单个 stream 的丢包恢复不再自动卡住其他 stream。不过连接级拥塞控制和连接级流量窗口仍然共享，网络真的拥堵时，所有请求还是会变慢。

所以“HTTP/3 完全没有队头阻塞”是过度简化；更准确的说法是，它消除了 TCP 多路复用带来的跨 stream 阻塞，把影响范围缩小到了受损 stream 和共享连接的实际瓶颈。

## 浏览器到底怎么选 h1、h2 还是 h3

协议版本通常由客户端和服务器协商，不是业务代码在 `fetch` 里传一个 `version: 3`。常见的 HTTPS 连接里，TLS 的 ALPN 会让双方选择 `http/1.1` 或 `h2`。HTTP/3 使用 QUIC 自己的 TLS 1.3 握手，并通过 `h3` 这样的 ALPN 标识协议。

服务器可以通过 `Alt-Svc` 或其他服务发现机制告诉浏览器某个 origin 还支持 h3：

```http
alt-svc: h3=":443"; ma=86400
```

浏览器随后会尝试建立 QUIC。如果 UDP 被防火墙拦截、握手失败或服务端不可用，客户端应回退到基于 TCP 的 HTTP 版本。实际线上经常出现这种情况：浏览器到 CDN 使用 h3，CDN 到源站却使用 h1.1；协议版本是逐跳协商的，不是一个从浏览器一直传到应用进程的全局标签。

HTTP/2 也存在不加 TLS 的 h2c 模式，但普通浏览器页面不会把它当作 HTTPS 导航的默认路径。看到“HTTP/2 通常和 HTTPS 一起出现”，不要反推成“HTTP/2 规范要求必须使用 TLS”；两者是协议部署方式和浏览器策略的区别。

## 在工具里确认，而不是凭感觉猜

浏览器里直接看 Network 面板的 Protocol 列，常见值是 `http/1.1`、`h2` 和 `h3`。页面里的 JavaScript 通常拿不到这个协商结果，`fetch` 返回的 `Response` 也没有一个标准的 HTTP 版本字段。

curl 可以用来做有边界的对照，但前提是本机的 curl 编译时带了对应协议支持：

```bash
curl -sSI --http1.1 https://example.com/
curl -sSI --http2 https://example.com/
curl -sSI --http3 https://example.com/
```

输出第一行可能分别是 `HTTP/1.1 200`、`HTTP/2 200` 和 `HTTP/3 200`。如果 `--http2` 或 `--http3` 报“不支持该选项”，先检查 curl 和 TLS/QUIC 库，不要据此判断服务器没有 h2 或 h3。还要记住，curl 看到的是它到目标端点这一跳的结果；如果中间有 CDN，它不代表浏览器到 CDN 或 CDN 到源站的每一跳。

## 最后把差异压成一张表

| 版本 | 线上主要形态 | 连接与并发 | 主要解决的问题 | 仍要面对的限制 |
| --- | --- | --- | --- | --- |
| HTTP/1.0 | 文本消息 + TCP | 默认短连接，一次交互后关闭 | 让客户端和服务器能交换带字段的资源 | 重复握手，资源并发成本高 |
| HTTP/1.1 | 文本消息 + TCP | 持久连接；pipelining 有序 | 连接复用、Host、chunked 和更完整的缓存/协商 | 一条连接缺少真正的独立流，存在 HTTP 层队头阻塞 |
| HTTP/2 | 二进制帧 + TCP | 一条连接多 stream，可交错帧；HPACK | 减少连接数量，解决 HTTP 层排队，压缩重复字段 | TCP 丢包会影响同一连接上的多个 stream |
| HTTP/3 | HTTP/3 帧 + QUIC/UDP | QUIC stream 独立恢复；QPACK | 缩小丢包时的阻塞范围，减少连接建立与迁移成本 | UDP 可达性、部署复杂度、拥塞和共享流量窗口 |

如果只是维护一个前端站点，通常不需要在业务代码里区分四套 HTTP API。更实际的排查顺序是：先看浏览器实际协商到了哪一跳，再看连接建立、TTFB、丢包和资源大小，最后才判断 h2/h3 是否带来了真实收益。协议升级能改变传输的上限，但不会替服务器慢查询、过大的 JavaScript 或错误的缓存策略背锅。
