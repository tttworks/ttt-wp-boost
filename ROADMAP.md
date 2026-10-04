# TTT WP Boost — 开发路线图

> 版本: 1.1.0
> 日期: 2026-07-01
> 作者: TTTWorks · Aloysius Luo

---

## v1.0.0 MVP (已完成)

| 文件 | 功能 |
|------|------|
| ttt-wp-boost.php | 主文件, 激活/停用钩子, 常量定义 |
| includes/class-core.php | 核心初始化, 后台菜单, AJAX handlers, 统计函数 |
| includes/class-page-cache.php | HTML 页面缓存引擎, 自动清理 |
| admin/settings-page.php | 后台设置页, 统计卡片, 清空缓存按钮 |
| readme.txt | 插件说明 |

---

## v1.1.0 (已完成: Dashboard Accelerator + Object Cache)

### Dashboard Accelerator — WordPress 原生后台优化

| 文件 | 功能 |
|------|------|
| `includes/class-dashboard.php` | Dashboard Accelerator 引擎，包含所有优化钩子 |
| `admin/settings-page-dashboard.php` | 独立设置页，含每项实时状态徽章 |

优化项:
- Switch to Classic Editor（解决 Gutenberg 在 1GB 内存服务器慢的问题）
- Disable Gutenberg Widgets
- Disable Block Directory（阻止编辑器内 api.wordpress.org 请求）
- Disable Block Widgets（注销所有内置 block widgets）
- Disable / Slow Heartbeat API
- Remove Emoji Scripts
- Remove Admin Color Schemes
- Disable Link Manager
- Limit Post Revisions
- Autosave Interval
- Show Status Badges（实时验证每项优化是否生效）

### Object Cache — 三层降级缓存

| 文件 | 功能 |
|------|------|
| `includes/class-object-cache.php` | Object Cache 引擎，三层降级 (Redis → Memcached → Transients) |
| `admin/settings-page-object-cache.php` | 独立设置页，显示后端状态和统计信息 |

---

## 待开发 v1.2 ~ v2.0

### v1.2 — 关键CSS内联 (Critical CSS)

| 文件 (新增) | 功能 |
|------------|------|
| includes/class-critical-css.php | CSS扫描, 合并, 内联到 `<head>` |
| admin/css-tab-section.php | 后台 "CSS优化" 标签页 |

开发要点:
- 在 wp_enqueue_scripts 钩子中收集所有已注册 CSS
- 合并CSS内容限制在50KB以内
- 生成 `<style id='ttt-wp-boost-css'>` 内联
- 原 `<link>` 改为 `<link rel='preload' ...>` + `media='print' onload`
- 需要"重新生成"按钮 (非自动, 让用户手动触发)
- 注意 Elementor 的动态CSS不可内联

### v1.3 — JS 脚本延迟

| 文件 (新增) | 功能 |
|------------|------|
| includes/class-js-defer.php | JS识别, 队列管理, 延迟引擎 |
| assets/js/defer-scripts.js | 前端延迟触发逻辑 |

开发要点:
- NEVER_DEFER: elementor, elementor-frontend, jquery, jquery-core (硬编码)
- 触发条件: 3秒后或用户交互 (三者之一满足)
- wp_footer 注入 defer 脚本
- 注意 Elementor 编辑器环境 (is_edit_mode 时不延迟)
- 注意和其他 JS 插件 (表单验证等) 的冲突

### v2.0 — 进阶功能

| 功能 | 说明 |
|------|------|
| WP Rocket 检测与互斥 | 检测 WP Rocket 激活时自动禁用本插件的页面缓存 |
| 多站点兼容 | 目前 `WP_CONTENT_DIR` 可能在多站点环境下冲突 |
| CDN 集成 | 配合 Cloudflare 等 CDN 自动刷新缓存 |
| 缓存预热 | 发布文章时自动生成首页和文章页的缓存 |

---

## 如何告诉我后期补充

在新对话开头引用这个文件路径:
- `D:\workplace\plugins\ttt-wp-boost\ROADMAP.md`

直接说: "参考 ROADMAP, 开始开发 v1.2 Critical CSS 部分"

我会根据历史记录理解上下文, 继续开发。
