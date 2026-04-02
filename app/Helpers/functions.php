<?php

namespace App\Helpers;

use Illuminate\Database\QueryException;

function isDbConnectionError(QueryException $e): bool
{
    $msg = strtolower($e->getMessage());
    $patterns = [
        'timeout expired',
        'connection refused',
        'no connection',
        'could not connect',
        'server closed the connection',
        'connection reset',
        'broken pipe',
        '08006',
        '08001',
        '08003',
        'hy000',
    ];
    foreach ($patterns as $p) {
        if (str_contains($msg, $p)) {
            return true;
        }
    }
    return false;
}
