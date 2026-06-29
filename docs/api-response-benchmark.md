# API 响应性能压测指南

快速评估 `pipes` 对接口性能的影响。

## 1. 目标

对比以下两种配置下的响应耗时差异：

- **基线组**：关闭 `pipes`（空数组）
- **实验组**：开启默认 `MessagePipe + ErrorPipe`

## 2. 准备压测路由

```php
use Hyperf\HttpServer\Router\Router;

Router::get('/api/__bench__/ok', static fn () => api_response()->ok(['ping' => 'pong']));
```

建议：使用与线上一致的 Swoole Worker 数量。

## 3. 两组配置

基线组：

```php
'pipes' => [],
```

实验组：

```php
'pipes' => [
    MessagePipe::class,
    ErrorPipe::class,
],
```

## 4. 压测命令

```bash
# wrk
wrk -t4 -c100 -d30s --latency "http://127.0.0.1:9501/api/__bench__/ok"

# ab
ab -n 10000 -c 100 "http://127.0.0.1:9501/api/__bench__/ok"
```

## 5. 结果记录

| 组别 | Avg Latency | P95 | P99 | Req/Sec |
|------|-------------|-----|-----|---------|
| 基线组 | | | | |
| 实验组 | | | | |
| 差值 | | | | |

建议跑 3 轮取平均。

## 6. 结果解读

- 差值在单毫秒级且吞吐变化小：pipes 开销可接受
- 差值明显增大时排查：
  1. 自定义 pipe 中是否有 IO 操作
  2. 是否有复杂序列化或大 payload
  3. 逐个 pipe 开关定位开销来源

## 7. 建议

- 保持 pipe 逻辑轻量、无 IO、无阻塞
- 重业务逻辑放在服务层，不放 pipe
- 默认 3 个 pipe（MessagePipe + ErrorPipe + TracePipe）通常不会成为性能瓶颈
- Swoole 常驻内存模式下 pipe 实例化开销极低

## 8. Trace 压测

对比 `trace=true` 与不带 trace 参数时的 HTTP 延迟差异。trace 机制说明见 [api-response-trace.md](api-response-trace.md)。

### wrk 需要 PHP 扩展吗？

**不需要。** `wrk` 是独立的 HTTP 压测 CLI 工具，与 PHP 无关；在压测机上单独安装即可：

```bash
# macOS
brew install wrk

# Debian / Ubuntu
sudo apt install wrk
```

被压测的 Hyperf 服务仍需要 Swoole 运行时；wrk 本身不依赖任何 PHP 扩展。

### 压测路由

```php
use Hyperf\HttpServer\Router\Router;

Router::get('/api/__bench__/ok', static function () {
    api_trace('bench', ['t' => time()]);
    return api_response()->ok(['ping' => 'pong']);
});
```

### 压测命令

```bash
# 基线：无 trace
wrk -t4 -c100 -d30s --latency "http://127.0.0.1:9501/api/__bench__/ok"

# 实验：trace=true
wrk -t4 -c100 -d30s --latency "http://127.0.0.1:9501/api/__bench__/ok?trace=true"
```

### 结果记录

| 组别 | Avg Latency | P95 | P99 | Req/Sec |
|------|-------------|-----|-----|---------|
| 无 trace | | | | |
| trace=true（无 api_trace） | | | | |
| trace=true（含 api_trace） | | | | |

### 结果解读

- 仅 `trace=true`、几乎不写 `api_trace`：延迟差值通常在微秒～零点几毫秒，可忽略
- 大量 `api_trace` 或大 `ctx`：关注 P99 与响应体体积增长
- 差值异常时检查是否在 `ctx` 中塞入了过大的调试数据
