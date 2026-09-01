import { existsSync } from "node:fs";
import { spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const envFile = resolve(root, "infra/.env");

if (!existsSync(envFile)) {
  console.error("缺少 infra/.env。请先复制 infra/.env.example 为 infra/.env，并仅填写本机开发配置。");
  process.exit(1);
}

const result = spawnSync("docker", ["compose", "--file", "docker-compose.yml", "up", "--detach"], {
  cwd: resolve(root, "infra"),
  stdio: "inherit",
});

if (result.error) {
  console.error(`无法启动 Docker Compose：${result.error.message}`);
  process.exit(1);
}

if (result.status !== 0) process.exit(result.status ?? 1);

const sleep = (milliseconds) => new Promise((resolvePromise) => setTimeout(resolvePromise, milliseconds));
let ready = false;
for (let attempt = 1; attempt <= 90; attempt += 1) {
  try {
    const response = await fetch("http://localhost:8080/index.php?rest_route=/wp/v2/posts/&per_page=100&_fields=slug");
    const posts = await response.json();
    if (response.ok && Array.isArray(posts) && posts.length >= 20) {
      ready = true;
      break;
    }
  } catch {
    // The containers may still be booting or the database may still be initializing.
  }
  if (attempt % 10 === 0) console.log(`等待本地 WordPress 就绪（${attempt}/90）...`);
  await sleep(2000);
}

if (!ready) {
  console.error("本地 WordPress 未在 180 秒内完成启动；可运行 pnpm run local:logs 查看容器日志。");
  process.exit(1);
}

console.log("本地 WordPress 开发版：http://localhost:8080/");
console.log("本地后台：http://localhost:8080/wp-admin/");
console.log("已检测到至少 20 篇文章；验收完成后再上传主题包和导入插件到线上。");
