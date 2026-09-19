<?php

namespace App\Support;

/**
 * سجل الصلاحيات: المصدر الوحيد لما يمكن منحه.
 *
 * الصلاحيات كانت أعمدة منطقية على جدول admins، فإضافة واحدة تتطلب هجرة،
 * ولا سبيل لمعرفة ما هو موجود إلا بقراءة المخطط. صارت هنا في قائمة واحدة
 * تُزرع في جدول permissions، ويُبنى منها شاشة الأدوار.
 *
 * الاسم على شكل <المجموعة>.<الفعل>، و<المجموعة>.view هي رؤية القسم في
 * القائمة الجانبية.
 */
class Permissions
{
    /** الصلاحية التي تفتح كل شيء؛ يملكها دور المدير العام وحده. */
    public const SUPER = 'system.super';

    /**
     * العمود القديم على admins مقابل مجموعة الصلاحيات الجديدة، لترحيل
     * الصلاحيات القائمة دون أن يفقد أحد وصوله.
     */
    public const LEGACY_COLUMN_MAP = [
        'dashboard'     => 'dashboard',
        'pos'           => 'invoices',
        'stock'         => 'stock',
        'store'         => 'stores',
        'cat'           => 'categories',
        'unit'          => 'units',
        'product'       => 'products',
        'stock_limit'   => 'stock_limit',
        'coupons'       => 'coupons',
        'customer'      => 'customers',
        'seller'        => 'sellers',
        'admin'         => 'admins',
        'supplier'      => 'suppliers',
        'setting'       => 'settings',
        'requests'      => 'requests',
        'storage'       => 'storages',
        'notification'  => 'notifications',
        'tracking'      => 'tracking',
        'vehicle_stock' => 'vehicle_stock',
        'reports'       => 'reports',
        'regions'       => 'regions',
        'sales'         => 'sales',
        'accounts'      => 'accounts',
        'rating'        => 'ratings',
        'visit'         => 'visits',
        'sectionsalary' => 'salaries',
        'production'    => 'production',
        'hr'            => 'hr',
        'attendance'    => 'attendance',
        'install'       => 'installments',
        'documents'     => 'documents',
    ];

    /**
     * كل مجموعة واسمها العربي والأفعال المتاحة فيها.
     *
     * view دائمًا موجودة: هي ما يقرر ظهور القسم في القائمة. وبقية الأفعال
     * تُخفي الأزرار داخل الشاشة.
     *
     * @return array<string, array{label: string, actions: array<string, string>}>
     */
    public static function groups(): array
    {
        $crud = [
            'view'   => 'عرض',
            'create' => 'إضافة',
            'update' => 'تعديل',
            'delete' => 'حذف',
        ];

        $viewOnly = ['view' => 'عرض'];

        $viewExport = ['view' => 'عرض', 'export' => 'تصدير'];

        return [
            'dashboard'     => ['label' => 'لوحة التحكم',        'actions' => $viewOnly],
            'invoices'      => ['label' => 'الفواتير',           'actions' => $crud + ['export' => 'تصدير']],
            'sales'         => ['label' => 'المبيعات',           'actions' => $crud],
            'accounts'      => ['label' => 'الحسابات',           'actions' => $crud + ['export' => 'تصدير']],
            'installments'  => ['label' => 'التحصيلات',          'actions' => $crud + ['export' => 'تصدير']],
            'stock'         => ['label' => 'المخزون',            'actions' => $crud],
            'stock_limit'   => ['label' => 'حدود المخزون',       'actions' => $viewOnly],
            'vehicle_stock' => ['label' => 'مخزون العربيات',     'actions' => $crud + ['approve' => 'اعتماد']],
            'stores'        => ['label' => 'المخازن',            'actions' => $crud],
            'storages'      => ['label' => 'المستودعات',         'actions' => $crud],
            'products'      => ['label' => 'المنتجات',           'actions' => $crud + ['export' => 'تصدير']],
            'categories'    => ['label' => 'الأقسام',            'actions' => $crud],
            'units'         => ['label' => 'الوحدات',            'actions' => $crud],
            'coupons'       => ['label' => 'الكوبونات',          'actions' => $crud],
            'customers'     => ['label' => 'العملاء',            'actions' => $crud + ['export' => 'تصدير']],
            'suppliers'     => ['label' => 'الموردين',           'actions' => $crud],
            'sellers'       => ['label' => 'المناديب',           'actions' => $crud],
            'regions'       => ['label' => 'المناطق',            'actions' => $crud],
            'visits'        => ['label' => 'الزيارات',           'actions' => $crud + ['export' => 'تصدير']],
            'tracking'      => ['label' => 'تتبّع المناديب',     'actions' => $viewOnly],
            'ratings'       => ['label' => 'التقييمات',          'actions' => $crud],
            'requests'      => ['label' => 'الطلبات',            'actions' => $crud + ['approve' => 'اعتماد']],
            'reports'       => ['label' => 'التقارير',           'actions' => $viewExport],
            'documents'     => ['label' => 'الوثائق',            'actions' => $crud],
            'hr'            => ['label' => 'الموارد البشرية',    'actions' => $crud],
            'attendance'    => ['label' => 'الحضور والانصراف',   'actions' => $viewExport],
            'salaries'      => ['label' => 'المرتبات',           'actions' => $crud],
            'production'    => ['label' => 'الإنتاج والتصنيع',   'actions' => $crud],
            'notifications' => ['label' => 'الإشعارات',          'actions' => $viewOnly],
            'admins'        => ['label' => 'المستخدمين',         'actions' => $crud],
            'roles'         => ['label' => 'الأدوار والصلاحيات', 'actions' => $crud],
            'settings'      => ['label' => 'الإعدادات',          'actions' => $viewOnly + ['update' => 'تعديل']],

            // أقسام لم تكن لها أعمدة، فكانت مفتوحة لكل من يدخل اللوحة.
            'brands'        => ['label' => 'الماركات',           'actions' => $crud],
            'taxes'         => ['label' => 'الضرائب',            'actions' => $crud],
            'shifts'        => ['label' => 'الورديات',           'actions' => $crud],
            'factories'     => ['label' => 'المصانع',            'actions' => $crud],
            'materials'     => ['label' => 'المواد الخام',       'actions' => $crud],
            'purchases'     => ['label' => 'المشتريات',          'actions' => $crud],
            'supply_orders' => ['label' => 'أوامر التوريد',      'actions' => $crud + ['approve' => 'اعتماد']],
            'deposits'      => ['label' => 'تحويلات المناديب',   'actions' => $crud + ['approve' => 'اعتماد']],
            'stock_returns' => ['label' => 'طلبات إرجاع البضاعة', 'actions' => $viewOnly + ['approve' => 'اعتماد']],
        ];
    }

    /**
     * كل الصلاحيات مسطّحة: الاسم => التسمية.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $out = [self::SUPER => 'صلاحية كاملة على النظام'];

        foreach (self::groups() as $group => $meta) {
            foreach ($meta['actions'] as $action => $actionLabel) {
                $out[$group . '.' . $action] = $meta['label'] . ' — ' . $actionLabel;
            }
        }

        return $out;
    }
}
