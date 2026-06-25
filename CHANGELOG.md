# Changelog

本文件记录 `felo-z/hyperf-api-response` 的版本变更。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

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
- `ap()` 辅助函数与 Pipeline 扩展机制

[2.0.0]: https://github.com/Felo-Z/hyperf-api-response/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/Felo-Z/hyperf-api-response/releases/tag/v1.0.0
