<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetCard;
use App\Models\AssetCardTemplate;

class GenerateAssetCardCode
{
    /**
     * Words ignored when building the initials.
     *
     * @var array<int, string>
     */
    private const array STOP_WORDS = [
        'a',
        'al',
        'con',
        'de',
        'del',
        'e',
        'el',
        'en',
        'la',
        'las',
        'los',
        'o',
        'para',
        'u',
        'y',
    ];

    private const int MAX_INITIALS = 3;

    /**
     * Builds the next asset code for the given company and template, using the
     * format {COMPANY}-{TEMPLATE}-{NNN} where NNN is auto-incremental.
     */
    public function execute(string $companyName, AssetCardTemplate $template): string
    {
        $prefix = $this->initials($companyName).'-'.$this->initials($template->name).'-';

        $number = $this->nextNumber($prefix);

        do {
            $code = $prefix.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $number += 1;
        } while ($this->codeExists($code));

        return $code;
    }

    private function nextNumber(string $prefix): int
    {
        $max = 0;

        foreach (
            AssetCard::query()->where('code', 'like', $prefix.'%')->pluck('code') as $code
        ) {
            if (! is_string($code)) {
                continue;
            }

            $suffix = substr($code, strlen($prefix));

            if (is_numeric($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max + 1;
    }

    private function codeExists(string $code): bool
    {
        return AssetCard::query()->where('code', $code)->exists();
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach ($words as $word) {
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $word) ?? '';

            if (
                $clean === '' ||
                in_array(mb_strtolower($clean), self::STOP_WORDS, true)
            ) {
                continue;
            }

            $initials .= mb_strtoupper(mb_substr($clean, 0, 1));
        }

        if ($initials === '') {
            return 'X';
        }

        return mb_substr($initials, 0, self::MAX_INITIALS);
    }
}
