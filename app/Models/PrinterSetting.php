<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'business_id',
    'paper_width_mm',
    'content_padding_mm',
    'font_size_px',
    'show_logo',
])]
class PrinterSetting extends Model
{
    use BelongsToBusiness;

    public const DEFAULT_PAPER_WIDTH_MM = 80;

    public const DEFAULT_CONTENT_PADDING_MM = 4;

    public const DEFAULT_FONT_SIZE_PX = 12;

    /** @return array<int, int> */
    public static function presetWidths(): array
    {
        return [58, 76, 80, 112];
    }

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'paper_width_mm' => self::DEFAULT_PAPER_WIDTH_MM,
            'content_padding_mm' => self::DEFAULT_CONTENT_PADDING_MM,
            'font_size_px' => self::DEFAULT_FONT_SIZE_PX,
            'show_logo' => true,
        ]);
    }

    protected function casts(): array
    {
        return [
            'paper_width_mm' => 'integer',
            'content_padding_mm' => 'integer',
            'font_size_px' => 'integer',
            'show_logo' => 'boolean',
        ];
    }
}
