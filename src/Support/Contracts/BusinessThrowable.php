<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support\Contracts;

/**
 * 业务异常契约。
 *
 * 业务项目中的业务异常实现此接口后，BusinessExceptionPipe 会自动将其
 * 业务码与消息映射到统一响应结构，避免被兜底逻辑当作系统异常（500）处理。
 */
interface BusinessThrowable
{
    /**
     * 业务错误码（非 HTTP 状态码），如 20001。
     */
    public function getBusinessCode(): int;

    /**
     * 可选的错误详情，附加到响应的 error 字段。
     */
    public function getErrorData(): ?array;
}
