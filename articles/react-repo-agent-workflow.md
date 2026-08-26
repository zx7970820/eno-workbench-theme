---
title: "我把 React 仓库拉下来，翻了一遍它的 AI 协作文件"
slug: "react-repo-agent-workflow"
category: "前端工程"
date: "2026-08-18"
tags: ["React", "AI 协作", "工程流程", "开源协作"]
excerpt: "随手翻一遍 React 官方仓库里的 CLAUDE.md、SKILL.md 和 CI 配置，看看成熟项目怎样把 AI 协作放进日常开发，而不是停在口号上。"
---

# 我把 React 仓库拉下来，翻了一遍它的 AI 协作文件

我把 React 官方仓库拉到本地后，没有先从源码入口读起，而是先看根目录的协作说明。这里没有想象中的 `AGENTS.md`，而是 `CLAUDE.md`、`.claude/instructions.md` 和 `.claude/settings.json`。

这几个文件没有什么“让 AI 自动变聪明”的魔法，更像是给刚进项目的人准备的工作手册。它们真正做的事，是把范围、命令和不能做的动作写具体，减少协作者自己猜的空间。

## 根目录先把边界画出来

根目录的 `CLAUDE.md` 很短，主要说 React 的代码在哪里，React Compiler 为什么要单独看。它还提醒，进入 `/compiler/` 后会有另一套说明。这个提醒看起来普通，但对一个有很多子项目的仓库很重要：改 `react-dom` 和改 compiler，本来就不该用同一套排查办法。

然后我打开 `.claude/instructions.md`。这里写的东西更像团队约定：包管理器只用 Yarn；构建默认交给 CI；需要验证时使用 `/verify`。它还列出几个常用动作：`/test` 跑测试，`/flow` 跑 Flow，`/flags` 看 feature flag，`/fix` 做格式化和 lint。

我喜欢这种写法。它没有泛泛地说“请遵循项目规范”，而是直接告诉 AI 该调用哪个命令。命令错了，结果很快就会暴露，不需要靠模型自己猜。

## SKILL.md 更像一张值班手册

`.claude/skills/` 里现在有 `test`、`flow`、`fix`、`flags`、`feature-flags`、`extract-errors` 和 `verify`。每个文件都不长，但会说清楚什么时候用、怎么用，以及容易踩什么坑。

比如 `test/SKILL.md` 不只写一句“运行测试”，它先要求判断 channel，再拼出对应的 Yarn 命令，还规定使用 `--silent --no-watchman`。`feature-flags` 则把 `@gate` 和 `gate()` 的区别写出来，顺便提醒带 `__VARIANT__` 的 flag 要把 true 和 false 都跑一遍。

`verify` 是这里最像流程编排的文件。它先跑格式化和 `linc`，通过之后再做 Flow、source test 和 www test。看起来有点笨，但这正是它有用的原因：每次改动都按同一个顺序走，不依赖当时帮忙的 AI 记不记得所有检查。

## 进入 compiler，说明文件会换一套

我继续看 `compiler/CLAUDE.md`，感觉像进了另一间办公室。它要求改某个 compiler pass 之前，先读对应的 pass 文档；测试主要用 `yarn snap` 跑 fixture，需要时可以用 `yarn snap minimize` 把失败样例缩小。

这份文件还记录了 HIR、aliasing effects、feature flag 和错误容忍机制。它不是把源码重新抄一遍，而是把“这次改动最容易误解的概念”集中放在一起。对 AI 来说，这比一份全仓库通用的长规则更有用：只在真正需要的目录加载真正相关的背景。

## settings 里写着哪些事情能做

`.claude/settings.json` 有一个 SessionStart hook。只要当前目录不在 `/compiler/`，它就会自动把 `.claude/instructions.md` 打出来。进入 compiler 后则由子目录的说明接手，避免两套规则叠在一起。

权限部分也很直白：允许常用的 Skill 和 Yarn 测试、lint、Flow、build 命令；拒绝 `npm`、`pnpm`、`bun`、`npx`，也不允许随便下载构建产物。它没有试图限制所有事情，只挡住几条最容易绕过项目约定的路。

读完以后真正值得记住的不是一长串命令，而是这几个顺序：进入子目录先找局部说明，跑测试先确认适用的 channel，改动完成后按仓库规定的验证流程走一遍。

这套安排很朴素：AI 可以帮忙找文件、拼命令、解释失败日志，但不能替代测试和代码审查。规则写在仓库里，协作者就不用每次从自己的记忆里猜项目习惯。
