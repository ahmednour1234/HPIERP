<?php

namespace App\Services\Exceptions;

use RuntimeException;

/**
 * قاعدة من قواعد طلب إرجاع البضاعة رفضت الطلب.
 *
 * تحمل حالتها بنفسها: طلب معلّق قائم يردّ 409 لا 422، وهو ما يميّزه
 * التطبيق ليعرض شاشة "بانتظار الموافقة" بدل رسالة خطأ.
 */
class ReturnRequestException extends RuntimeException
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** للمندوب طلب معلّق بالفعل. */
    public static function alreadyPending(): self
    {
        return new self('لديك طلب إرجاع معلّق بالفعل بانتظار موافقة الأدمن.', 409);
    }

    public static function notFound(): self
    {
        return new self('طلب الإرجاع غير موجود.', 404);
    }

    /** الطلب رُوجع بالفعل، فلا يُسحب ولا يُراجع مرة أخرى. */
    public static function notPending(): self
    {
        return new self('تمت مراجعة هذا الطلب بالفعل.', 409);
    }
}
