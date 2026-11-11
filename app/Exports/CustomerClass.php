<?php

namespace App\Exports;

/**
 * تصنيف العميل A–D، مخزَّن رقمًا في customers.type (1=A … 4=D).
 *
 * مشترك بين ملف التحديث وقراءته حتى يرجع الحرف المصدَّر بنفس الرقم.
 */
class CustomerClass
{
    private const MAP = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'];

    public static function label($type): string
    {
        return self::MAP[(int) $type] ?? '';
    }

    /** الحرف (أو الرقم 1–4) إلى قيمة type، أو null إن لم يكن صالحًا. */
    public static function value($label): ?int
    {
        $label = strtoupper(trim((string) $label));

        if (in_array($label, ['1', '2', '3', '4'], true)) {
            return (int) $label;
        }

        $value = array_search($label, self::MAP, true);

        return $value === false ? null : $value;
    }
}
