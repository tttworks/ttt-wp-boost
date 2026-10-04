# TTT WP Boost — 开发报告 v1.1.0

> 开发周期: 2026-07-01 | 交付时间: ~6 小时
> 作者: Aloysius Luo @ TTTWorks
> 目录: `D:\workplace\plugins\ttt-wp-boost\`

---

## 一、交付总结

v1.1.0 在 MVP v1.0.0 基础上新增两个独立模块：**Dashboard Accelerator**（WordPress 原生后台加速）和 **Object Cache**（三层降级对象缓存）。

### 交付物

| 文件 | 行数 | 职责 |
|------|------|------|
| `ttt-wp-boost.php` | 72 | 主入口, 常规定义, 激活/停用钩子, v1.1 默认选项 |
| `includes/class-core.php` | 187 | 核心初始化, 三级子菜单, AJAX 处理, 工具函数 |
| `includes/class-page-cache.php` | 173 | HTML 页面缓存引擎, 缓存准入检查, 自动清理 |
| `includes/class-dashboard.php` | 215 | Dashboard Accelerator 引擎, 11 项优化钩子 |
| `includes/class-object-cache.php` | 320 | Object Cache 引擎, Redis/Memcached/Transients 三层降级 |
| `admin/settings-page.php` | 149 | Page Cache 设置页 |
| `admin/settings-page-dashboard.php` | 590 | Dashboard Accelerator 设置页, 实时状态徽章 |
| `admin/settings-page-object-cache.php` | 280 | Object Cache 设置页, 后端状态卡片 |
| `admin/assets/css/admin.css` | 15 | 后台设置页样式 |
| `readme.txt` | - | 插件说明 |
| `ROADMAP.md` | - | 开发路线图 |

**PHP 总行数: 约 1,786 lines + 15 CSS**

### 已实现功能

#### 页面缓存（v1.0）
- **HTML 页面缓存**: `parse_request` 钩子检查缓存文件，命中后直接 `readfile()` + `exit()`，跳过整个 WordPress 执行栈
- **缓存准入控制**: 排除登录管理员/编辑、POST 请求、后台、REST API、搜索、Elementor 编辑器、404、用户自定义 URL
- **缓存键生成**: `md5(URL路径 + 用户角色)`，保证不同角色的用户看到不同缓存
- **自动清理**: `transition_post_status` + `deleted_post` 钩子自动删除相关缓存
- **后台 UI**: 设置页含缓存统计卡片（文件数、总大小、最后更新）、一键清空按钮、启用开关、过期时间设置、排除 URL 列表
- **安全**: `wp_nonce` 验证、`manage_options` 权限检查、`.htaccess` + `index.php` 防止直接访问缓存文件

#### Dashboard Accelerator（v1.1）
- **Classic Editor 切换**: 解决 Gutenberg 在 1GB 内存服务器上 3-8 秒加载时间的问题
- **Gutenberg Widgets 禁用**: 避免 ~1.2MB JS 在非 widgets 页面加载
- **Block Directory 禁用**: 阻止编辑器内每次按键向 api.wordpress.org 发送 HTTP 请求
- **Block Widgets 注销**: 减少全局 admin JS/CSS 加载
- **Heartbeat API 控制**: 可完全禁用或减缓到 120s/300s，解决低配服务器 CPU 峰值
- **Emoji Scripts 移除**: 移除 ~10KB JS，节省 HTTP 请求
- **Admin Color Schemes 精简**: 节省 ~30-50KB CSS
- **Link Manager 隐藏**: 清理无用的菜单项
- **Post Revisions 限制**: 控制数据库膨胀
- **Autosave 间隔调整**: 减少数据库写入
- **实时状态徽章**: 每项优化的右侧直接显示 ✓ ACTIVE / ✗ INACTIVE

#### Object Cache（v1.1）
- **三层降级架构**: Redis → Memcached → WordPress Transients
- **自动后端检测**: 启动时自动探测最快可用后端
- **全环境兼容**: 无 Redis/Memcached 时降级到 Transients，保证任何主机都能使用
- **Elementor CSS 缓存**: 缓存 Elementor 动态生成的 CSS，减少重复编译
- **WordPress Query 缓存**: 缓存常见查询结果，减少数据库 SELECT
- **设置页**: 显示当前后端、统计信息、服务器状态、手动刷新

---

## 二、开发经验总结

### 2.1 技术决策经验

**页面缓存核心机制**
- `ob_start()` + `wp_footer` 捕获输出是最可靠的缓存生成方案，兼容性好于直接修改 .htaccess
- 缓存文件名用 `md5()` 避免路径遍历攻击，同时保证唯一性
- `filemtime()` 检查过期比 crontab 清理更轻量，不需要额外配置
- 硬编码排除 `/wp-admin`、`/wp-json`、搜索、Elementor 编辑器——这些不应该是用户配置项，用户不该为这些负责

**为什么不做 Critical CSS**
- CSS 扫描需要解析 `$wp_styles` 队列和文件系统，涉及权限和环境兼容问题
- Elementor 的动态 CSS 不可预读取，内联可能导致编辑器样式损坏
- 需要手动"生成"按钮，但生成过程可能耗时且失败，MVP 不做

**为什么不做 JS Defer**
- 识别延迟脚本需要维护名单，第三方脚本的句柄经常变化
- Elementor 的 JS 依赖关系复杂，延迟可能破坏编辑器
- 频繁的 `defer` 操作可能导致表单验证脚本失效，用户体验下降

### 2.2 架构决策

- **插件而非 Snippet**: 选择标准 WordPress 插件格式，因为需要 `register_activation_hook` 创建缓存目录，和 `register_deactivation_hook` 清理。功能覆盖面和生命周期要求超过了 Code Snippets 的能力范围
- **单例模式**: `TTT_WP_Boost_Core::instance()` 确保核心类只初始化一次，避免多个实例注册重复钩子
- **无外部依赖**: 只使用 WordPress 内置 API（`Filesystem`, `Options API`, `AJAX`），不引入任何第三方库

### 2.3 踩坑记录

| 问题 | 原因 | 修复 |
|------|------|------|
| `$image_url` 在 register_controls 中报 Warning | RAW_HTML 引用 render() 中的变量 | 移除 RAW_HTML 中的 PHP 变量引用 |
| ob_start 没有对应的 wp_footer 钩子 | 只写了 capture_and_store 方法但忘了注册钩子 | 在 __construct 中加 `add_action('wp_footer', ...)` |
| transition_post_status 钩子注册两次 | sed 替换导致的重复行 | 手动删除重复行 |
| WP Rocket 冲突 | 未检测其他缓存插件是否激活 | MVP 未处理，ROADMAP 标注 |
| role 为空时缓存键不一致 | `implode()` 对空数组返回 `''`，导致 `path.'||'` 而非 `path.'||guest'` | 添加空角色兜底：`empty($role) → $role = 'subscriber'` |
| plugins_loaded 时机太早 | `plugins_loaded` 时 REQUEST_URI 可能未准备好，且 enable=false 时也执行检查 | 改用 `parse_request` 钩子并加 `$enable` 条件守卫 |
| 缓存目录在 Nginx 下无保护 | `.htaccess` 只对 Apache 有效，Nginx 访问缓存文件无限制 | index.php 添加 `ABSPATH` 检查，阻止直接访问 |
| 内联 CSS 不可取消排队 | 在 `admin_enqueue_scripts` 中直接输出 `<style>` | 改为 `wp_enqueue_style()` + 独立 CSS 文件 |
| save_post 和 transition_post_status 重复清理 | 两个钩子都触发首页 + 文章页清理，造成多余 IO | 移除 `save_post`，只保留 `transition_post_status` + `deleted_post` |

---

## 三、未开发功能清单

### P1 — 建议下个迭代优先做

| 功能 | 工作量 | 说明 |
|------|--------|------|
| **关键 CSS 内联** (v1.1) | ~2h | 收集 CSS → 合并 → 内联到 `<head>` → 其他 CSS 异步加载。可提升 LCP 20-30% |
| **后台 Tab 页重构** | ~1h | 目前单 Tab，未来增加 CSS/JS/Object Cache 的 Tab。需要重构 `settings-page.php` 为 Tab 布局 |
| **缓存预热** | ~1h | 发布文章时自动生成首页和文章页的缓存，避免第一个访问者等待 |

### P2 — 中期规划

| 功能 | 工作量 | 说明 |
|------|--------|------|
| **JS 脚本延迟** (v1.2) | ~2h | 识别第三方脚本 → 延迟加载 → 3秒或交互触发。有 Elementor 兼容风险 |
| **缓存过期日志** | ~1h | 记录缓存生成和清理时间，方便排查 |
| **排除 URL 支持通配符** | ~0.5h | 目前只支持字符串前缀匹配，不支持 `*` 通配符 |
| **缓存细分** | ~1h | 按设备类型(桌面/手机)分缓存，提高移动端性能 |

### P3 — 远期规划

| 功能 | 工作量 | 说明 |
|------|--------|------|
| **Object Cache 集成** (v2.0) | ~1h | 检测 Redis/Memcached → 缓存 Elementor 查询。依赖 PHP 扩展 |
| **WP Rocket 检测与互斥** | ~0.5h | 检测 WP Rocket 激活时自动禁用本插件的页面缓存 |
| **多站点兼容** | ~1h | 目前 `WP_CONTENT_DIR` 可能在多站点环境下冲突 |
| **CDN 集成** | ~2h | 配合 Cloudflare 等 CDN 自动刷新缓存 |

### 推迟原因说明

- **Critical CSS**: MVP 不做。需要解析 `$wp_styles` + 文件系统读取，技术复杂且有 Elementor 兼容风险。预计 v1.1
- **JS Defer**: MVP 不做。第三方脚本句柄不稳定，延迟可能破坏表单和编辑器。需在专门版本中充分测试。预计 v1.2
- **Object Cache**: MVP 不做。依赖服务器端 Redis/Memcached，不是所有用户都有此环境。预计 v2.0

---

## 四、后续开发入口

要开始 v1.1 的开发，在新对话中引用:

```
D:\workplace\plugins\ttt-wp-boost\ROADMAP.md
```

直接说"开始 v1.1 Critical CSS 内联"即可。
