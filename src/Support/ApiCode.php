<?php

declare(strict_types=1);

namespace FeloZ\HyperfApiResponse\Support;

/**
 * 通用 API code 常量（可直接使用，也可在业务项目中二次扩展）。
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

    public const HTTP_OK = 200;

    public const HTTP_CREATED = 201;

    public const HTTP_ACCEPTED = 202;

    public const HTTP_NO_CONTENT = 204;

    public const HTTP_BAD_REQUEST = 400;

    public const HTTP_UNAUTHORIZED = 401;

    public const HTTP_FORBIDDEN = 403;

    public const HTTP_NOT_FOUND = 404;

    public const HTTP_CONFLICT = 409;

    public const HTTP_UNPROCESSABLE_ENTITY = 422;

    public const HTTP_TOO_MANY_REQUESTS = 429;

    public const HTTP_INTERNAL_SERVER_ERROR = 500;
}
