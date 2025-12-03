# PHPWind 9.x 项目结构与运行机制深度解析

本文档旨在为开发者提供 PHPWind 9.x (PW9) 项目的全面架构指南。该项目基于 **Wind Framework**（阿里云/PHPWind 自研框架）开发，采用经典的 MVC 模式，但有其独特的目录结构和加载机制。

---

## 1. 项目目录结构概览

```text
root/
├── admin.php                # 后台入口文件
├── index.php                # 前台核心入口文件
├── windid.php               # WindID (用户中心) 入口文件
├── install.php              # 安装程序入口
├── conf/                    # 全局配置文件
│   ├── application/         # 应用级配置 (如 phpwind.php, pwadmin.php)
│   ├── database.php         # 数据库连接配置
│   └── site.php             # 站点全局配置
├── src/                     # 【核心代码区】所有的业务逻辑都在这里
│   ├── applications/        # [Controller层] 各个业务模块的控制器
│   │   ├── bbs/             # 论坛模块
│   │   ├── u/               # 用户模块
│   │   ├── design/          # 门户/设计模块
│   │   └── ...
│   ├── service/             # [Model/Service层] 业务服务与数据模型
│   │   ├── forum/           # 论坛服务 (Dao, Dm, Srv)
│   │   ├── user/            # 用户服务
│   │   └── ...
│   ├── library/             # 通用类库/工具类
│   ├── extensions/          # 插件扩展目录
│   ├── hooks/               # 钩子定义目录
│   ├── bootstrap/           # 启动引导脚本 (phpwindBoot.php 等)
│   └── wekit.php            # 全局容器/核心加载器 (Wekit)
├── template/                # [View层] 核心模板文件 (HTML)
│   ├── bbs/                 # 论坛相关模板
│   ├── common/              # 公共模板 (头部、底部)
│   └── ...
├── themes/                  # 主题/风格目录 (覆盖 template 的显示)
│   ├── site/                # 整站风格
│   └── extres/              # 扩展资源
├── wind/                    # 底层 Wind Framework 框架代码
├── res/                     # 静态资源 (CSS, JS, Images)
└── data/                    # 数据目录 (需写权限)
    ├── cache/               # 文件缓存
    ├── compile/             # 模板编译缓存
    └── log/                 # 系统日志
```

---

## 2. 运行路线：从用户访问到页面渲染

当用户访问 `http://localhost/index.php?m=bbs&c=read&a=run&tid=100` 时，系统经历了以下步骤：

### 第一阶段：初始化 (Bootstrap)
1.  **入口 (index.php):** 加载 `src/wekit.php`。
2.  **Wekit加载:** `Wekit::run('phpwind')` 启动应用。
3.  **引导 (Bootstrap):** 调用 `src/bootstrap/phpwindBoot.php`。
    *   加载全局配置 (`conf/`)。
    *   初始化数据库连接。
    *   初始化用户 Session (`src/service/user/bo/PwUserBo.php`)。
    *   计算全局 URL 路径 (CSS/JS 路径)。

### 第二阶段：路由 (Routing)
4.  **路由分发:** 底层框架 `WindRouter` 解析 URL 参数。
    *   `m=bbs`: **Module** (模块)，对应 `src/applications/bbs`。
    *   `c=read`: **Controller** (控制器)，对应 `src/applications/bbs/controller/ReadController.php`。
    *   `a=run`: **Action** (动作)，对应控制器中的 `run()` 方法。
5.  **过滤器 (Filters):** 执行配置在 `conf/application/phpwind.php` 中的过滤器（如全局权限检查、CSRF 防御）。

### 第三阶段：控制器执行 (Controller)
6.  **执行 Action:** 系统实例化 `ReadController` 并调用 `run()` 方法。
    *   **获取输入:** 使用 `$this->getInput('tid')` 获取参数。
    *   **调用服务:** 使用 `Wekit::load('SRV:forum.srv.PwThreadService')` 获取帖子数据。
    *   **逻辑处理:** 处理权限、阅读数增加等。
    *   **设置输出:** 使用 `$this->setOutput($data, 'variableName')` 将数据传递给前端。

### 第四阶段：视图渲染 (View)
7.  **模板定位:** 框架根据约定寻找模板。`m=bbs, c=read` 默认对应 `template/bbs/read_run.htm`。
8.  **模板编译:**
    *   Wind 模板引擎将 `.htm` 文件编译成 PHP 文件 (存放在 `data/compile/`)。
    *   解析标签：如 `<!--# foreach... #-->` 被转换为 PHP `foreach`，`{@url:...}` 被转换为实际 URL。
9.  **输出响应:** 编译后的 PHP 文件被执行，生成 HTML，发送给浏览器。

---

## 3. 前后端工作模式

PHPWind 9 采用的是 **服务器端渲染 (SSR)** 模式，而非现代的前后端分离 (SPA)。

*   **后端 (PHP):** 负责处理业务逻辑、查询数据库，并将处理好的**数据对象**注入到视图层。
*   **前端 (HTM/JS/CSS):**
    *   **HTML结构:** 定义在 `template/` 目录下的 `.htm` 文件中。
    *   **数据展示:** 使用模板标签（如 `{$variable}`）直接输出后端传递的数据。
    *   **交互:** 依赖 `res/js/dev/` 下的 jQuery 和 Wind.js。页面加载后，JS 负责 DOM 操作和部分 AJAX 请求（如点赞、弹窗）。

### 后端 API 接口写在哪里？
虽然主要是 SSR，但也有 API 供 AJAX 或移动端调用：
1.  **内部控制器:** 任何 Controller 的 Action 都可以作为 API。如果在请求中带上 `_json=1`，框架通常会尝试以 JSON 格式返回（取决于具体的 `showMessage` 处理）。
2.  **移动端 API:** 位于 `src/applications/native/controller/`，例如 `NativeBaseController`。
3.  **WindID API:** 位于 `src/windid/api/`。

---

## 4. 模块之间的引用与命名空间 (Wekit 核心)

PW9 使用 `Wekit` 作为服务容器，通过特定的**别名 (Alias)** 来加载类。

### 常用别名：
*   **APPS:** 指向 `src/applications/`。用于加载控制器。
*   **SRV:** 指向 `src/service/`。用于加载通用业务服务。
*   **LIB:** 指向 `src/library/`。用于加载工具类。
*   **WIND:** 指向 `wind/`。加载底层框架类。
*   **EXT:** 指向 `src/extensions/`。加载插件。

### 如何调用代码：
*   **加载 Service (单例):** `Wekit::load('SRV:user.srv.PwUserService')`
    *   这会加载 `src/service/user/srv/PwUserService.php`。
*   **加载 DAO (数据访问对象):** `Wekit::loadDao('SRV:user.dao.PwUserDao')`
    *   这会加载 `src/service/user/dao/PwUserDao.php`。
*   **获取配置:** `Wekit::C('site', 'info.name')`
    *   获取站点配置。

---

## 5. 插件和主题是如何工作的

### 插件 (Extensions)
*   **位置:** `src/extensions/`。
*   **原理:** 基于 **Hook (钩子)** 机制。
    *   系统核心代码中埋有埋点，例如 `PwSimpleHook::getInstance('hook_name')->runDo($args)`。
    *   插件通过 XML 配置文件 (`Manifest.xml`) 声明自己要监听哪些钩子。
    *   当系统运行到钩子处时，会拦截并执行插件目录下的 `Injector` 或 `Do` 类。

### 主题 (Themes)
*   **位置:** `themes/`。
*   **原理:** 模板覆盖。
    *   如果在 `themes/site/default/template/bbs/read_run.htm` 存在文件，它会**优先**于 `template/bbs/read_run.htm` 被加载。
    *   这允许在不修改核心文件的情况下改变外观。

---

## 6. 命名规范 (Conventions)

为了利用框架的自动加载，必须遵守以下规范：

1.  **文件名与类名一致:** `PwUserService.php` 其中的类名必须是 `PwUserService`。
2.  **Controller:**
    *   位置: `src/applications/{module}/controller/`
    *   命名: `{Name}Controller.php` (如 `IndexController.php`)
    *   Action: `public function {name}Action()` (如 `runAction`, `loginAction`)
3.  **Service (Srv):**
    *   位置: `src/service/{module}/srv/`
    *   命名: `Pw{Module}{Function}` (如 `PwUserLogin`)
4.  **Dao (Database Access Object):**
    *   位置: `src/service/{module}/dao/`
    *   命名: `Pw{Module}Dao` (如 `PwUserDao`)
    *   继承: 必须继承 `PwBaseDao`。
5.  **Data Model (Dm):**
    *   位置: `src/service/{module}/dm/`
    *   用途: 用于数据的校验、组装，在传给 Dao 之前处理数据。

---

## 7. 常用开发调试技巧

*   **调试模式:** 修改 `conf/baseconfig.php` 或后台设置开启 Debug 模式，这会禁用模板缓存并显示更多错误。
*   **日志:** 检查 `data/log/` 目录。
    *   `log.txt`: 运行错误日志。
    *   `sql.log`: 慢 SQL 或 SQL 错误。
*   **模板修改:** 修改了 `.htm` 文件后，必须删除 `data/compile/` 下对应的缓存文件，否则修改不生效（除非开启了 Debug 模式）。

---

**总结:** 
PHPWind 9 是一个重业务逻辑、轻前端框架的系统。
*   **想改页面:** 去 `template/` 或 `themes/`。
*   **想改逻辑:** 去 `src/applications/` (Controller) 或 `src/service/` (Service)。
*   **想改数据库:** 去 `src/service/.../dao/`。
*   **想加功能:** 优先考虑编写插件 (`src/extensions/`)。
