<?php

if (! function_exists('format_rupiah')) {
    function format_rupiah(int $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }
}
