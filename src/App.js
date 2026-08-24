const topics = [
  ["全部文章", "ti-home"], ["系统设计", "ti-server"], ["后端与服务", "ti-database"],
  ["Rust", "ti-brand-rust"], ["Shell 与 Linux", "ti-terminal-2"],
  ["前端工程", "ti-code"], ["工具与效率", "ti-tool"],
];

const posts = [
  { title: "Rust 所有权：从值语义到借用检查器", excerpt: "沿着栈帧、移动语义和生命周期约束，理解编译器究竟在证明什么。", tags: ["Rust", "内存模型"], topic: "Rust", date: "2026-08-18", read: "24 min read", icon: "ti-brand-rust" },
  { title: "Shell 管道不是字符串：进程、文件描述符与退出码", excerpt: "从 fork、exec 与 pipe 开始，解释一条命令如何成为一组协作进程。", tags: ["Shell", "Linux"], topic: "Shell 与 Linux", date: "2026-08-15", read: "18 min read", icon: "ti-terminal-2" },
  { title: "深入 Vue 3 响应式系统", excerpt: "从 Proxy、依赖集合到调度队列，拆开一次更新的完整路径。", tags: ["Vue", "运行时"], topic: "前端工程", date: "2026-08-12", read: "26 min read", icon: "ti-code" },
  { title: "React 渲染：协调、提交与可中断更新", excerpt: "把组件调用、Fiber 工作循环和 DOM 提交放进同一张心智地图。", tags: ["React", "并发"], topic: "前端工程", date: "2026-08-09", read: "22 min read", icon: "ti-brand-react" },
  { title: "Webpack 编译流程全景", excerpt: "从 Entry 到 Chunk，理解 Loader、Plugin、缓存与增量构建如何协作。", tags: ["Webpack", "构建系统"], topic: "工具与效率", date: "2026-08-05", read: "23 min read", icon: "ti-package" },
  { title: "Vite HMR 边界与失效传播", excerpt: "为什么有的修改能热替换，有的修改必须沿依赖图回退到整页刷新。", tags: ["Vite", "开发体验"], topic: "工具与效率", date: "2026-08-01", read: "16 min read", icon: "ti-bolt" },
];

const hotPosts = [["Rust 异步运行时：任务、Waker 与 Reactor", "12.4k"], ["Linux 进程模型与 namespace", "10.1k"], ["React 渲染的两阶段模型", "9.8k"], ["一次 HTTP 请求的完整旅程", "8.2k"], ["Vite 依赖预构建与缓存", "7.6k"]];

const icon = (name, extra = "") => `<i aria-hidden="true" class="ti ${name} ${extra}"></i>`;
const tagList = (tags) => `<div class="tags">${tags.map((tag) => `<span>${tag}</span>`).join("")}</div>`;

function postCards(items) {
  if (!items.length) return `<div class="empty panel">${icon("ti-search")}<h3>没有匹配的文章</h3><p>换一个关键词，或者回到全部文章。</p><button data-clear>清除筛选</button></div>`;
  return `<div class="post-grid">${items.map((post) => `<article class="post-card panel"><div class="post-content"><h3><a href="#article">${post.title}</a></h3><p>${post.excerpt}</p>${tagList(post.tags)}<footer><span>${post.date}</span><span>·</span><span>${post.read}</span></footer></div><div class="post-icon">${icon(post.icon)}</div></article>`).join("")}</div>`;
}

export function mountApp(root) {
  let activeTopic = "全部文章";
  let query = "";
  root.innerHTML = `<div class="site-shell">
    <aside class="left-rail" aria-label="主题导航">
      <div class="brand-block"><a class="brand" href="#top"><span>&gt;_</span> eno的小黑屋</a><p>系统、编程与长期实践</p><button class="mobile-close" data-menu-close aria-label="关闭菜单">${icon("ti-x")}</button></div>
      <div class="author-card"><img src="/assets/author-avatar.png" alt="eno 的插画头像"><div><strong><i></i> eno</strong><span>Software Engineer</span><small>把问题写到可以复现</small></div></div>
      <nav class="topic-nav"><span class="rail-label">主题</span>${topics.map(([label, glyph]) => `<button data-topic="${label}" class="${label === activeTopic ? "active" : ""}">${icon(glyph)}<span>${label}</span></button>`).join("")}</nav>
      <div class="archive-list"><span class="rail-label">归档</span>${[["2026","18"],["2025","27"],["2024","31"],["2023","15"]].map(([year,count]) => `<a href="#archive"><span>${year}</span><b>${count}</b></a>`).join("")}</div>
      <div class="rail-footer"><div><a href="#github" aria-label="GitHub">${icon("ti-brand-github")}</a><a href="#mail" aria-label="邮件">${icon("ti-mail")}</a><a href="#archive" aria-label="归档">${icon("ti-archive")}</a></div><small>© 2026 eno的小黑屋</small></div>
    </aside>
    <main class="main-area" id="top">
      <header class="mobile-header"><button data-menu-open aria-label="打开菜单">${icon("ti-menu-2")}</button><a href="#top">eno的小黑屋</a>${icon("ti-moon")}</header>
      <section class="search-row" aria-label="文章搜索">${icon("ti-search")}<input placeholder="按 / 搜索文章、主题或标签" aria-label="搜索文章"><kbd>${icon("ti-command")} K</kbd></section>
      <section class="feature panel"><div class="feature-copy"><span class="eyebrow">精选深度</span><h1>一次请求如何穿过 Shell、服务、运行时与数据库</h1><p>不把技术栈切成孤岛：沿着一条真实请求，观察进程、协议、调度、缓存与持久化如何彼此影响。</p>${tagList(["系统设计","Linux","后端","深度阅读"])}</div><img class="feature-art" src="/assets/systems-topology.png" alt="请求穿过多个系统层的拓扑图"><div class="feature-meta"><span>${icon("ti-calendar")}2026-08-20</span><span>${icon("ti-clock")}32 min read</span><a href="#read">阅读全文 ${icon("ti-arrow-right")}</a></div></section>
      <section class="content-layout"><div class="feed-column"><div class="section-title"><i></i><h2 data-feed-title>最新文章</h2><span data-feed-count>${posts.length} 篇</span></div><div data-feed>${postCards(posts)}</div><a class="more-link" href="#archive">查看全部文章 ${icon("ti-arrow-right")}</a></div>
        <aside class="right-column"><section class="side-panel panel"><h2>${icon("ti-flame")}热门文章</h2>${hotPosts.map(([title,views]) => `<a class="hot-item" href="#article"><span>${title}</span><small>${views}</small></a>`).join("")}<a class="text-link" href="#archive">查看全部文章 ${icon("ti-arrow-right")}</a></section>
          <section class="side-panel panel research"><h2>正在研究</h2>${[["Rust async 运行时",72,"任务调度与 Waker"],["Linux I/O 多路复用",48,"epoll、事件循环与背压"],["数据库索引与查询计划",24,"从 B+Tree 到代价估算"]].map(([name,value,note],index) => `<div class="research-item"><span class="check ${index === 0 ? "active" : ""}">${index === 0 ? icon("ti-check") : ""}</span><div><strong>${name}</strong><small>${note}</small></div><div class="meter"><i style="width:${value}%"></i><b>${value}%</b></div></div>`).join("")}</section>
          <section class="side-panel panel series"><h2>系列进度 <b>3 / 6</b></h2>${["进程、线程与协程","网络请求的生命周期","运行时调度与背压","缓存一致性","持久化与恢复","可观测性与故障定位"].map((item,index) => `<div class="series-item ${index < 3 ? "done" : ""}"><span>${String(index+1).padStart(2,"0")}</span><p>${item}</p>${index < 2 ? icon("ti-check") : index === 2 ? "<i></i>" : "<b></b>"}</div>`).join("")}</section>
        </aside>
      </section>
      <footer class="site-footer"><a class="brand" href="#top"><span>&gt;_</span> eno的小黑屋</a><p>记录系统如何运行，也记录我如何理解它。</p><nav><a href="#about">关于</a><a href="#archive">归档</a><a href="#friends">友链</a><a href="#rss">RSS</a></nav></footer>
    </main><button class="scrim" data-menu-close aria-label="关闭菜单"></button>
  </div>`;

  const refreshFeed = () => {
    const keyword = query.trim().toLowerCase();
    const filtered = posts.filter((post) => (activeTopic === "全部文章" || post.topic === activeTopic) && (!keyword || `${post.title} ${post.excerpt} ${post.tags.join(" ")}`.toLowerCase().includes(keyword)));
    root.querySelector("[data-feed]").innerHTML = postCards(filtered);
    root.querySelector("[data-feed-title]").textContent = activeTopic === "全部文章" ? "最新文章" : activeTopic;
    root.querySelector("[data-feed-count]").textContent = `${filtered.length} 篇`;
    root.querySelectorAll("[data-topic]").forEach((button) => button.classList.toggle("active", button.dataset.topic === activeTopic));
  };
  const rail = root.querySelector(".left-rail");
  root.addEventListener("click", (event) => {
    const topic = event.target.closest("[data-topic]");
    if (topic) { activeTopic = topic.dataset.topic; rail.classList.remove("is-open"); refreshFeed(); }
    if (event.target.closest("[data-menu-open]")) rail.classList.add("is-open");
    if (event.target.closest("[data-menu-close]")) rail.classList.remove("is-open");
    if (event.target.closest("[data-clear]")) { activeTopic = "全部文章"; query = ""; root.querySelector(".search-row input").value = ""; refreshFeed(); }
  });
  root.querySelector(".search-row input").addEventListener("input", (event) => { query = event.target.value; refreshFeed(); });
  window.addEventListener("keydown", (event) => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") { event.preventDefault(); root.querySelector(".search-row input").focus(); } });
}
