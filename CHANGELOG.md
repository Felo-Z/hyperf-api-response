# Changelog

本文件记录 `felo-z/hyperf-api-response` 的版本变更。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

## [Unreleased]

### 变更

- 文档：业务码约定与代码对齐——`0` 成功、`1000–1999` 包内置 `ApiCode`、其余整数为项目自定义（运行时无范围校验）；移除「≥ 10000」表述
- 文档：响应结构示例区分成功/失败，`error` 标注为可选字段；404 等示例与真实输出一致
- 文档：重写 [项目扩展指南](docs/api-response-project-extension.md)，补充 `UserCode` / `OrderCode` 等常量类在业务项目中的创建、目录结构与三种用法
- 文档：`README` 增加 docs 索引；配置示例缩进与完整项修正；benchmark 默认 pipe 数量笔误修正
- 文档：`contract-template` 区分包默认 `error` 格式与团队可选增强格式
- `ApiResponse` 类注释：项目自定义码说明与上述约定一致

## [3.0.0] - 2026-06-26

### 重大变更

- 移除 `ap()` 辅助函数，统一改用 `api_response()`

### 新增

- `api_response()` 辅助函数

## [2.0.0] - 2026-06-25

### 重大变更

- **JSON 契约**：响应体 `code` 改为业务码（成功 `0`，失败 `1000+` / 项目自定义码）；HTTP 状态码独立控制，不再写入 body `code`
- **`status` 字段**：恒由 `code === 0` 推导，调用方不可再手动传入矛盾值
- **`failed()` / `error()`**：签名改为 `failed($message, $code, $httpStatus, $error)`
- **`message()`**：移除第 2 参 `$code`；签名改为 `message($message, $data, $httpStatus)`
- **`json()`**：移除第 1 参 `$status`；签名改为 `json($code, $message, $data, $error, $httpStatus)`
- **204 / 205**：符合 RFC，响应无 body、无 `Content-Type`
- **与 Laravel 版差异**：不再声称与 `felo-z/laravel-api-response` 共享相同 JSON 契约（见 README 对比表）

### 新增

- `ApiCode` 业务码常量（`BIZ_OK`、`BIZ_FAILED`、`BIZ_NOT_FOUND` 等）
- `BusinessThrowable` 契约与 `BusinessExceptionPipe`
- `Macroable::forgetMacro()` / `clearMacros()`
- 配置项 `app_debug`、`fallback_success_status_code`、`fallback_error_status_code`
- `json_encode` 失败时返回 HTTP 500 标准 JSON 兜底
- GitHub Actions CI（PHP 8.2 / 8.3 / 8.4 + Swoole 扩展）

### 变更

- 移除 `StatusCodePipe`，HTTP fallback 合并至 `destination()`
- `ErrorPipe`：生产环境仅隐藏系统诊断类 `error`（堆栈等），保留校验/业务 `error`
- 失败响应 `JSON_PRETTY_PRINT` 仅在 debug 模式启用
- `app_debug()` 优先读 config 并缓存，减少重复 `env()` 读取
- `MessagePipe` 默认文案按 body `code` 而非中间态 `status` 判断

### 测试

- 覆盖 `BusinessExceptionPipe`、`ValidationExceptionPipe`、`AuthenticationExceptionPipe`
- 覆盖 204/205 空 body、`json_encode` 失败、macro 隔离（tearDown 清理）

## [1.0.0] - 初始发布

- 统一 API JSON 响应与 `ApiExceptionHandler`
- 响应辅助函数与 Pipeline 扩展机制

[3.0.0]: https://github.com/Felo-Z/hyperf-api-response/compare/v2.0.0...v3.0.0
[2.0.0]: https://github.com/Felo-Z/hyperf-api-response/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/Felo-Z/hyperf-api-response/releases/tag/v1.0.0
