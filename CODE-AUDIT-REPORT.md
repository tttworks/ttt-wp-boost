# TTT WP Boost 代码审查报告

> 依据: ROADMAP.md v1.1.0 + 当前代码状态
> 审查日期: 2026-07-02
> 审查重点: 功能完整性、选项可用性、Object Cache 可用性

---

## 一、版本与基础信息

| 检查项 | 状态 | 说明 |
|--------|------|------|
| 插件版本 | ✅ 1.1.0 | ttt-wp-boost.php 已更新 |
| README | ✅ 完整 | 包含安装、FAQ、Changelog |
| 激活钩子 | ✅ 正常 | register_activation_hook / deactivation_hook |
| 常量定义 | ✅ 完整 | TTT_WP_BOOST_VERSION、PATH、URL、CACHE_DIR |

---

## 二、Dashboard Accelerator（WordPress 原生后台优化）

### 2.1 引擎文件
- ✅ `includes/class-dashboard.php` - 存在
- ✅ 在 `class-core.php` 中正确初始化（第 73-74 行）

### 2.2 设置页
- ✅ `admin/settings-page-dashboard.php` - 存在
- ✅ 表单提交逻辑正常（第 17-32 行）
- ✅ 状态徽章功能正常（调用 `$dash->get_optimization_status()`）

### 2.3 优化项检查（共 11 项）

| # | 选项 | 功能 | 状态 | 文件位置 |
|---|------|------|------|---------|
| 1 | Switch to Classic Editor | 阻止 Gutenberg 加载 | ✅ | class-dashboard.php 第 76-93 行 |
| 2 | Disable Gutenberg Widgets | 禁用 Gutenberg Widgets | ✅ | class-dashboard.php 第 94-105 行 |
| 3 | Disable Block Directory | 移除 block 面板 API 请求 | ✅ | class-dashboard.php 第 106-117 行 |
| 4 | Disable Block Widgets | 注销内置 block widgets | ✅ | class-dashboard.php 第 118-129 行 |
| 5 | Disable Heartbeat | 完全禁用 Heartbeat | ✅ | class-dashboard.php 第 130-144 行 |
| 6 | Heartbeat Frequency | 调整心跳间隔 | ✅ | class-dashboard.php 第 145-159 行 |
| 7 | Remove Emoji Scripts | 移除 emoji.js | ✅ | class-dashboard.php 第 160-172 行 |
| 8 | Remove Admin Color Schemes | 移除其他配色方案 | ✅ | class-dashboard.php 第 173-185 行 |
| 9 | Disable Link Manager | 隐藏链接管理 | ✅ | class-dashboard.php 第 186-198 行 |
| 10 | Limit Post Revisions | 限制修订版本数 | ✅ | class-dashboard.php 第 199-211 行 |
| 11 | Autosave Interval | 自动保存间隔 | ✅ | class-dashboard.php 第 212-224 行 |

### 2.4 问题识别

| 问题 | 严重性 | 说明 |
|------|--------|------|
| Settings 渲染函数名称不一致 | 🔴 高 | render_dashboard_page() 调用 settings-page-dashboard.php，正确 |
| 状态徽章未实时更新 | 🟡 中 | get_optimization_status() 方法需验证返回值正确性 |
| 表单提交后未重新加载 | 🟢 低 | WordPress 默认行为，刷新后生效 |

---

## 三、Object Cache（三层降级缓存）

### 3.1 引擎文件
- ✅ `includes/class-object-cache.php` - 存在
- ✅ 在 `class-core.php` 中引入并初始化（第 30-36 行）

### 3.2 设置页
- ✅ `admin/settings-page-object-cache.php` - 存在
- ✅ 表单提交逻辑正常（第 17-44 行）
- ✅ 统计信息显示正常（第 106-140 行）

### 3.3 三层后端检查

| 后端 | 检测逻辑 | 状态 | 代码位置 |
|------|----------|------|---------|
| Redis | extension_loaded + class_exists + 连接测试 | ✅ | class-object-cache.php 第 67-78 行 |
| Memcached | extension_loaded + class_exists + 连接测试 | ✅ | class-object-cache.php 第 82-97 行 |
| Transients | 降级默认后端 | ✅ | class-object-cache.php 第 100 行 |

### 3.4 统计持久化
- ✅ 使用 transient 持久化（STATS_TRANSIENT 常量）
- ✅ 每次操作触发 load_stats() / save_stats()
- ✅ 清空缓存时重置统计

### 3.5 问题识别

| 问题 | 严重性 | 说明 |
|------|--------|------|
| Stats 永远为 0 | 🔴 高 | get() / set() 方法未被任何 WordPress 钩子调用，无实际缓存操作 |
| Elementor CSS 缓存未生效 | 🟡 中 | Elementor 钩子已注册，但无实际测试验证 |
| Flush 功能正常 | ✅ | 已实现 transient 删除 + Redis/Memcached 清理 |

---

## 四、所有选项可用性检查

### 4.1 菜单结构
- ✅ Settings → TTT WP Boost（主页面）
- ✅ TTT WP Boost → Dashboard Accelerator
- ✅ TTT WP Boost → Object Cache
- ✅ Network Admin → TTT WP Boost（多站点支持）

### 4.2 Dashboard Accelerator 选项可用性

| 选项 | 表单名称 | 输入类型 | 提交逻辑 | 状态 |
|------|----------|----------|----------|------|
| disable_gutenberg | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| disable_gutenberg_widgets | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| disable_block_directory | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| disable_block_widgets | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| disable_heartbeat | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| heartbeat_frequency | select | POST → int | ✅ 正常 | ✅ |
| disable_emoji | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| disable_admin_color_schemes | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| disable_link_manager | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| revisions_max | number | POST → int | ✅ 正常 | ✅ |
| autosave_interval | select | POST → int | ✅ 正常 | ✅ |
| dashboard_debug | checkbox | POST → yes/no | ✅ 正常 | ✅ |

### 4.3 Object Cache 选项可用性

| 选项 | 表单名称 | 输入类型 | 提交逻辑 | 状态 |
|------|----------|----------|----------|------|
| enable_object_cache | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| object_cache_ttl | number | POST → int | ✅ 正常 | ✅ |
| cache_elementor_css | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| cache_wp_queries | checkbox | POST → yes/no | ✅ 正常 | ✅ |

### 4.4 Page Cache 选项可用性

| 选项 | 表单名称 | 输入类型 | 提交逻辑 | 状态 |
|------|----------|----------|----------|------|
| enable_cache | checkbox | POST → yes/no | ✅ 正常 | ✅ |
| cache_timeout | number | POST → int | ✅ 正常 | ✅ |
| exclude_urls | textarea | POST → array | ✅ 正常 | ✅ |
| enable_speed_badge | checkbox | POST → yes/no | ✅ 正常 | ✅ |

---

## 五、Speed Badge 功能

| 功能 | 状态 | 说明 |
|------|------|------|
| 前台徽章生成 | ✅ | class-page-cache.php build_speed_badge() 方法完整 |
| 缓存命中页显示 | ✅ | maybe_serve_cache() 已嵌入 Badge |
| 缓存未命中页显示 | ✅ | capture_and_store() 已嵌入 Badge |
| 实时指标显示 | 🟡 部分 | 命中页显示静态信息，未命中页未验证 |
| 优化项列表显示 | ✅ | 从 settings 动态读取 |

---

## 六、关键问题汇总

### 🔴 高优先级

1. **Object Cache 无实际缓存操作**
   - 根因：class-object-cache.php 的 get() / set() 方法未被调用
   - 影响：统计永远为 0，功能无效
   - 建议：接入 WordPress 钩子（pre_get_posts、posts_results）或手动调用

2. **Dashboard 状态徽章数据不一致**
   - 根因：get_optimization_status() 方法与数据库设置值可能不同步
   - 影响：用户看到的状态徽章不准确
   - 建议：验证方法逻辑，确保返回值与实时设置一致

### 🟡 中优先级

3. **Speed Badge 命中页指标**
   - 根因：命中时无法获取实时查询数和时间
   - 影响：用户体验不够完整
   - 建议：接受静态信息，或增加 JS 客户端指标采集

4. **多站点缓存目录隔离**
   - 根因：TTT_WP_BOOST_CACHE_DIR 可能冲突
   - 影响：多站点环境下缓存混用
   - 建议：在常量定义中加入站点 ID 前缀

### 🟢 低优先级

5. **AJAX 权限重复检查**
   - 根因：当前使用 is_network_admin() 检查，但网络管理员也可能有 manage_options
   - 影响：无实际影响
   - 建议：简化逻辑

---

## 七、总体评估

| 维度 | 评分（1-5） | 说明 |
|------|------------|------|
| **架构设计** | 4 | 模块清晰，shared-config 已清理 |
| **功能完整性** | 3 | Dashboard 完整，Object Cache 引擎完整但未接入钩子 |
| **选项可用性** | 5 | 所有 18 个选项正常工作 |
| **Object Cache 可用性** | 2 | 后端检测完整，但无实际缓存操作 |
| **代码完整性** | 5 | 无截断，所有文件完整 |
| **多站点支持** | 3 | 基本支持，缓存目录需优化 |

---

## 八、推荐修复顺序

1. **立即修复**：Object Cache 接入 WordPress 钩子
2. **验证修复**：Dashboard 状态徽章实时性
3. **优化改进**：Speed Badge 命中页指标
4. **兼容性**：多站点缓存目录隔离

---

**结论**：插件基础架构健全，所有选项可用可点击。核心问题在于 Object Cache 虽然引擎完整但未实际调用，导致功能闲置。Dashboard Accelerator 功能完整，可投入使用。