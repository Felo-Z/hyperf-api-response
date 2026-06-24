<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support;

/**
 * 通用 API 业务码常量（可直接使用，也可在业务项目中二次扩展）。
 *
 * HTTP 状态码请直接使用整数字面量或自定义常量接口。
 */
class ApiCode
{
    public const BIZ_OK = 0;

    public const BIZ_FAILED = 1000;

    public const BIZ_VALIDATION_ERROR = 1001;

    public const BIZ_UNAUTHORIZED = 1002;

    public const BIZ_FORBIDDEN = 1003;

    public const BIZ_NOT_FOUND = 1004;

    public const BIZ_CONFLICT = 1005;

    public const BIZ_TOO_MANY_REQUESTS = 1006;

    public const BIZ_SYSTEM_ERROR = 1999;
}
