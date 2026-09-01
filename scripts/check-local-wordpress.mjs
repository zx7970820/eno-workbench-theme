const baseUrl = process.env.WP_LOCAL_URL || "http://localhost:8080";
const routes = ["/", "/wp-admin/", "/?s=React", "/?cat=2"];

let failed = false;
for (const route of routes) {
  const url = new URL(route, baseUrl).href;
  try {
    const response = await fetch(url, { redirect: "manual" });
    const location = response.headers.get("location");
    const detail = location ? ` → ${location}` : "";
    console.log(`${response.status} ${url}${detail}`);
    if (response.status >= 400) failed = true;
  } catch (error) {
    failed = true;
    console.error(`无法访问 ${url}: ${error.message}`);
  }
}

try {
  const response = await fetch(new URL("/index.php?rest_route=/wp/v2/posts/&per_page=100&_fields=slug", baseUrl));
  const posts = await response.json();
  const slugs = Array.isArray(posts) ? posts.map((post) => post.slug) : [];
  console.log(`REST posts=${slugs.length}`);
  const retired = [
    "rust-ownership-cache-boundary",
    "rust-async-shutdown-order",
    "deployment-rollback-last-mile",
    "node-memory-growth-debugging",
    "http-timeout-boundaries",
    "mysql-index-slow-query-replay",
    "cache-breakdown-singleflight",
    "shell-ci-failure-modes",
    "linux-file-descriptors-practical",
    "observability-three-signals",
  ];
  const required = ["frontend-bff-smart-analysis", "home-wifi-rearrangement"];
  if (response.status >= 400 || slugs.length < 20 || required.some((slug) => !slugs.includes(slug)) || slugs.includes("hello-world") || retired.some((slug) => slugs.includes(slug))) failed = true;
} catch (error) {
  failed = true;
  console.error(`无法读取 WordPress 文章 API: ${error.message}`);
}

if (failed) process.exit(1);
