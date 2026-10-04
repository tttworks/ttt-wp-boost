# TTT WP Boost 插件系统性检查报告

> 检查维度：架构设计 · 功能完整性 · 安全性 · 性能优化 · 兼容性 · 文档与可维护性

---

## 一、架构与模块化

### 1.1 代码结构

**当前状态**

- `class-object-cache.php` 集中实现缓存逻辑，Redis/Memcached/Transients 三层后端在同一类中
- `dashboard-accelerator.php` 处理前端状态，`class-page-cache.php` 处理页面缓存，`class-core.php` 是统一入口
- 共享配置通过 `shared-config.php` 和 `tab-header.php` 复用

**可优化点**

- **单一职责原则**：`class-object-cache.php` 同时承担了"后端检测连接"+"缓存读写"+"统计持久化"+"服务器信息获取"四项职责，建议拆分为 `ObjectCache_Engine.php`（纯缓存操作）和 `ObjectCache_Stats.php`（统计持久化）
- **依赖注入**：缓存类直接 `new` 了 Redis/Memcached 连接，建议通过构造函数注入，方便单元测试时 mock
- **shared-config.php 与 class-core.php 的职责重叠**：`shared-config.php` 定义的 `ttt_wp_boost_get_sub_pages()` 只在 `tab-header.php` 中使用，但 `class-core.php` 已经用显式 `add_submenu_page` 硬编码了三行，`shared-config.php` 的数据源价值没有体现出来——要么彻底用 `shared-config.php` 驱动菜单（解决重复），要么删掉它

### 1.2 文件组织

| 文件 | 职责 | 备注 |
|------|------|------|
| `ttt-wp-boost.php` | 入口、激活钩子 | 职责清晰 |
| `includes/class-core.php` | 核心单例、菜单注册、速度徽章 | 较臃肿，可拆分 |
| `includes/class-page-cache.php` | 页面缓存引擎 | 职责清晰 |
| `includes/class-object-cache.php` | 对象缓存引擎 | 过于集中 |
| `includes/class-dashboard.php` | Dashboard 优化过滤器 | 职责清晰 |
| `includes/shared-config.php` | 子页面配置 | 价值未体现 |
| `admin/settings-page.php` | Page Cache 设置页 | |
| `admin/dashboard-accelerator.php` | Dashboard Accelerator 设置页 | |
| `admin/object-cache.php` | Object Cache 设置页 | |
| `admin/parts/tab-header.php` | 共享 Tab 头部 | |

**建议**：长期看应增加 `includes/class-stats.php`（统一统计持久化）、`includes/class-cache-stats.php`（页面缓存统计），避免每个缓存类重复写 transient 操作逻辑。

---

## 二、功能完整性

### 2.1 Object Cache — 核心问题

**当前状态**

- `get()` / `set()` / `delete()` / `flush()` 方法已完整实现
- 统计改为 transient 持久化
- `cache_elementor_css()` / `cache_query()` 等专用方法已定义

**缺失**：没有任何 WordPress 钩子实际调用这些方法。`pre_get_posts`、`posts_results`、`wp_head` 等位置都没有接入缓存逻辑，导致：
- 每次页面访问，Object Cache 的 `get()` 从不被调用
- 统计数字永远为 0
- 用户无法感知缓存是否生效

**建议方案（三选一）**

1. **轻量方案**：在 `class-core.php` 的 `__construct()` 中，对已登录用户的查询结果手动调用 `cache_query()`，拦截 `get_posts()` / `WP_Query` 结果
2. **适中方案**：实现一个 `ObjectCache_WP_Hooks` 辅助类，hook `posts_pre_query` / `posts_results`，在结果非空时自动缓存
3. **完整方案**：参考 `WP Object Cache` 类的思路，用 `wp_cache_get()` / `wp_cache_set()` 替代 `get_transient()`，但这需要大量重构

**当前优先推荐方案 2**，工作量适中且效果可控。

### 2.2 Page Cache — Speed Badge 逻辑

**当前状态**：Badge 通过 `str_replace('</body>', $badge."\n</body>")` 嵌入缓存 HTML，速度指标记录请求开始时的 `$wpdb->num_queries` 和时间。

**潜在问题**

- `WP_START_TIMESTAMP` 在某些 WordPress 版本/配置下可能未定义，当前 fallback 是 `'N/A'`，不够友好
- 查询数的差值计算（`$wpdb->num_queries - $this->queries_at_request`）在 `capture_and_store()` 执行时已代表完整请求的查询数，但如果是缓存命中路径（`maybe_serve_cache` 直接 `exit`），则不会经过 `capture_and_store()`，此时 Badge 无法被嵌入

**结论**：当前设计下，**只有首次生成缓存的 miss 页面**才会嵌入 Badge。已缓存的 hit 页面因为 `maybe_serve_cache()` 直接 `exit`，不经过 `wp_footer`，所以 hit 页面没有 Badge。这与"永远显示"的需求存在矛盾，需要重新设计。

### 2.3 Dashboard Accelerator

- Current Status 已改为从数据库设置读取，但还需要确认：保存后是否需要手动刷新？页面是否应该在 `init` 钩子中直接生效而不依赖用户重新访问？
- 状态徽章（每项优化右侧的绿色/红色标记）依赖 `TTT_WP_Boost_Dashboard_Accelerator::get_optimization_status()`，需确认该方法返回值是否与数据库设置同步

---

## 三、安全性

### 3.1 权限控制

**当前检查**

- `ajax_clear_cache()` 有 `current_user_can('manage_options')` 检查 ✓
- `add_menu_page` 使用 `manage_options` 能力 ✓

**缺失**

- `admin/parts/tab-header.php` 通过 `require_once` 被所有子页面包含，没有权限检查——理论上任何能访问这些页面的用户都能看到（实际上这些页面本身在 `admin_menu` 之后加载，且主菜单已有 `manage_options` 门槛，风险较低）
- `ttt_wp_boost_get_cache_stats()` 是公开函数，任何人都能调用，不过只返回缓存文件数量和大小，无敏感数据，风险较低

### 3.2 输入验证

- `object_cache_ttl`、`revisions_max` 等数值字段使用了 `absint()`，基本安全 ✓
- `exclude_urls` 使用 `sanitize_textarea_field()` + `array_map('trim')` ✓
- SQL 查询（修订版计数）使用了 `$wpdb->prepare` ✓
- 未发现明显的 XSS、SQL 注入风险

### 3.3 CSRF 保护

- 所有表单使用 `wp_nonce_field()` + `check_admin_referer()` ✓
- AJAX 使用 `wp_create_nonce()` + `check_ajax_referer()` ✓

### 3.4 文件操作

- `file_put_contents` 写入缓存目录，有 `.htaccess` 保护（仅限 Apache）✓
- `wp_mkdir_p` 创建目录 ✓
- `chmod( $cache_file, 0644 )` 设置权限 ✓
- 缓存目录默认在 `WP_CONTENT_DIR . '/cache/ttt-wp-boost/'`，需要确认 Nginx 下是否有对应的目录访问限制

---

## 四、性能优化

### 4.1 缓存统计的写入频率

**当前问题**：`Object Cache` 的每次 `get()` / `set()` 都会调用 `load_stats()` + `save_stats()`，即两次 transient 读写 + 一次 transient 写入。高并发场景下会产生大量数据库写入。

**建议**：

- 将统计聚合改为"定期写入"而非"每次操作写入"
- 或使用内存变量 + `shutdown` 钩子批量持久化
- 或改用 `wp_cache_incr()` / `wp_cache_decr()` 操作 `WP_Object_Cache`

### 4.2 Page Cache 命中路径的性能

当前命中流程：

1. `parse_request`（优先级 -9999）→ `maybe_serve_cache()`
2. `readfile()` → `exit`

这是最优路径，无额外 PHP 开销 ✓。唯一需要确认的是 `record_hit()` 是否引入了额外查询（目前只是 `get_transient` + `set_transient`，可接受）。

### 4.3 `ob_start()` 的管理

**潜在问题**：在 `__construct()` 中无条件调用 `ob_start()`，但只有在 `enable_cache === 'yes'` 时才注册 `wp_footer` 来捕获内容。如果 `ob_start()` 成功但后续条件不满足，输出缓冲会一直悬着。

**检查**：`capture_and_store()` 检查 `is_cacheable()`，不满足时 `return` 不写入文件——但 `ob_get_contents()` 已经消耗了缓冲区内容，即使不写入文件，缓冲区也已清空。**这意味着未缓存请求也会清空缓冲区**，可能影响页面输出。

**结论**：`ob_start()` 应在 `enable_cache === 'yes'` 条件内调用，或者在 `capture_and_store()` 的非缓存路径中调用 `ob_end_clean()` 而非 `return`。

---

## 五、兼容性

### 5.1 WordPress 版本要求

- `ttt-wp-boost.php` 主文件头未声明 `Requires at least:` 和 `Tested up to:` 字段
- 代码中使用了 `DAY_IN_SECONDS`（WordPress 3.5+）、`human_time_diff()`（无版本要求）、`wp_parse_url()`（WordPress 3.6+）
- 建议最低版本：WordPress 5.0（因为使用了 Block Editor 相关检测逻辑）

### 5.2 PHP 版本要求

- 使用了 `??` 空合并运算符（PHP 7.0+）、箭头函数（PHP 7.4+）
- `class_exists()` / `extension_loaded()` 等无版本限制
- 建议最低版本：PHP 7.4

### 5.3 插件兼容性

- **Elementor**：页面构建部分绕过了 Gutenberg，Dashboard Accelerator 的 Gutenberg 优化对其无效；Elementor CSS 缓存方法已定义但未接入
- **WooCommerce**：产品编辑器使用 Gutenberg 变体，Dashboard Accelerator 的设置不影响产品页面 ✓
- **多站点（Network）**：未测试 network 激活场景，`add_menu_page` 在 network 模式下行为不同
- **Nginx**：缓存目录有 `.htaccess` 保护但无 Nginx 配置示例，需补充

### 5.4 对象缓存后端

- Redis/Memcached 连接失败时静默降级到 Transients ✓
- 但没有向用户报告降级原因（`get_server_info()` 在连接失败时返回空数组）

---

## 六、文档与可维护性

### 6.1 代码注释

- 多数方法有 `@param` / `@return` 注释 ✓
- `make_key()`、`get_server_info()` 等方法缺少详细说明
- `shared-config.php` 的注释说明了用途，但功能未被充分利用（见架构部分）

### 6.2 插件元数据

缺失字段：

- `Plugin URI:` ✓ 有
- `Author:` ✓ 有
- `License:` ✓ 有
- `Text Domain:` ✓ 有
- `Domain Path:` ❌ 缺失（多语言翻译必需）
- `Requires at least:` ❌ 缺失
- `Tested up to:` ❌ 缺失
- `Requires PHP:` ❌ 缺失

### 6.3 changelog / readme

- 无 `readme.txt`，用户无法了解更新历史和功能说明
- 无 `CHANGELOG.md`

### 6.4 常量定义

- `TTT_WP_BOOST_VERSION`、`TTT_WP_BOOST_PATH`、`TTT_WP_BOOST_URL` 定义在主文件 ✓
- `TTT_WP_BOOST_CACHE_DIR` 也已定义 ✓
- 但 transient 键名（`'ttt_wb_page_cache_stats'`、`'ttt_wb_object_cache_stats'`）是硬编码字符串，建议统一到常量

---

## 七、用户体验

### 7.1 设置页反馈

- 保存设置后显示 WordPress 默认的 success notice ✓
- 但未区分"部分保存成功"和"全部保存成功"
- 无表单验证错误提示（如 TTL 超出范围）

### 7.2 缓存统计展示

- Page Cache：文件数 + 大小 + Hits + Misses ✓
- Object Cache：Hits + Misses + Hit Rate + Bytes ✓
- 缺少"缓存类型分布"（查询缓存 vs CSS 缓存的占比）

### 7.3 移动端适配

- 当前 UI 使用固定宽度和 px 单位，大屏幕/WP admin 窄屏下可能有布局问题
- 状态卡片使用 `flex-wrap`，移动端基本可用 ✓

---

## 八、总体评估

| 维度 | 评分（1-5） | 说明 |
|------|------------|------|
| **架构设计** | 3 | 共享组件复用良好，但 shared-config 价值未体现；各缓存类职责集中 |
| **功能完整性** | 2 | Object Cache 方法齐全但未接入钩子，Speed Badge 在缓存命中页不显示 |
| **安全性** | 4 | CSRF、权限、输入验证基本到位，主要风险在文件操作边界 |
| **性能优化** | 3 | 命中路径最优，ob_start 管理和统计写入频率需优化 |
| **兼容性** | 3 | 缺版本声明；多站点、Nginx 配置未覆盖 |
| **文档可维护性** | 2 | 缺 readme、changelog、版本声明；方法注释不完整 |
| **用户体验** | 3 | 核心功能有反馈，但缺验证提示和类型分布统计 |

**最优先修复项**：

1. ob_start 位置错误（会导致未缓存页面输出丢失）
2. Speed Badge 在缓存命中页不显示
3. Object Cache 未接入任何 WordPress 钩子（stats 全为 0 的根因）
4. 插件头补充版本声明
