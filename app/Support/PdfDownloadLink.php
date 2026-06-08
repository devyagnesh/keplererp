<?php

namespace App\Support;

/**
 * Consistent admin PDF download button markup (SRS §21).
 */
class PdfDownloadLink
{
    public static function button(string $url, string $label = 'PDF'): string
    {
        return '<a href="'.e($url).'" class="btn btn-sm btn-outline-secondary btn-wave" target="_blank" rel="noopener">'
            .e($label).'</a>';
    }
}
