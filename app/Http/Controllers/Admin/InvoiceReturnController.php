<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Account, Customer, Order, OrderDetail, Product, Transection};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceReturnController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $accounts = Account::orderBy('id')->get();
        $customerId = $request->integer('customer_id');
        $invoices = Order::where('user_id', $customerId)->whereIn('type', [4, 12, 24])->latest('id')->get();
        $invoice = $invoices->firstWhere('id', $request->integer('invoice_id'));
        if ($invoice) {
            $invoice->load('details');
        }
        return view('admin-views.pos.refund.create', compact('customers', 'accounts', 'customerId', 'invoices', 'invoice'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'invoice_id' => 'required|integer',
            'method' => 'required|in:cash,credit',
            'account_id' => 'required_if:method,cash|nullable|integer|exists:accounts,id',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'nullable|integer|min:0',
        ]);
        $return = DB::transaction(function () use ($data) {
            $original = Order::where('id', $data['invoice_id'])->where('user_id', $data['customer_id'])
                ->whereIn('type', [4, 12, 24])->lockForUpdate()->firstOrFail();
            $details = OrderDetail::where('order_id', $original->id)->lockForUpdate()->get();
            $selected = array_filter($data['quantities'], fn ($qty) => (int) $qty > 0);
            if (!$selected || array_diff(array_keys($selected), $details->pluck('id')->all())) {
                throw ValidationException::withMessages(['quantities' => 'اختر أصنافًا من الفاتورة وحدد كمية المرتجع.']);
            }
            $base = $details->sum(fn ($line) => ((float) $line->price - (float) $line->discount_on_product + (float) $line->tax_amount) * $line->quantity);
            $ratio = $base > 0 ? min(1, max(0, (float) $original->order_amount / $base)) : 0;
            $total = $tax = 0;
            $lines = [];
            foreach ($details as $line) {
                $qty = (int) ($selected[$line->id] ?? 0);
                if (!$qty) continue;
                if ($qty > (float) $line->quantity - (float) $line->quantity_returned) {
                    throw ValidationException::withMessages(['quantities' => 'كمية المرتجع أكبر من الكمية المتاحة في الفاتورة.']);
                }
                $product = Product::whereKey($line->product_id)->lockForUpdate()->firstOrFail();
                $product->increment('quantity', $qty);
                $line->quantity_returned = (float) $line->quantity_returned + $qty;
                $line->save();
                $unitTax = (float) $line->tax_amount * $ratio;
                $unitNet = ((float) $line->price - (float) $line->discount_on_product) * $ratio;
                $total += ($unitNet + $unitTax) * $qty;
                $tax += $unitTax * $qty;
                $lines[] = [
                    'product_id' => $line->product_id, 'product_details' => $line->product_details,
                    'quantity' => $qty, 'price' => $line->price,
                    'discount_on_product' => (float) $line->price - $unitNet,
                    'tax_amount' => $unitTax, 'discount_type' => $line->discount_type,
                    'update_flag' => 0, 'created_at' => now(), 'updated_at' => now(),
                ];
            }
            $total = round($total, 2);
            $cash = $data['method'] === 'cash';
            $account = $cash ? Account::whereKey($data['account_id'])->lockForUpdate()->firstOrFail() : null;
            $customer = Customer::whereKey($data['customer_id'])->lockForUpdate()->firstOrFail();
            $return = Order::create([
                'parent_id' => $original->id, 'user_id' => $customer->id,
                'owner_id' => auth('admin')->id(), 'type' => 7, 'cash' => $cash ? 1 : 2,
                'payment_id' => $account ? $account->id : $original->payment_id,
                'total_tax' => round($tax, 2), 'order_amount' => $total,
                'collected_cash' => $cash ? $total : 0, 'update_flag' => 0,
            ]);
            foreach ($lines as &$line) $line['order_id'] = $return->id;
            unset($line);
            OrderDetail::insert($lines);
            if ($account) {
                $account->balance -= $total;
                $account->total_out += $total;
                $account->save();
            } else {
                $customer->balance = (float) $customer->balance + $total;
                $customer->save();
            }
            Transection::create([
                'tran_type' => 7, 'seller_id' => auth('admin')->id(),
                'account_id' => $return->payment_id, 'customer_id' => $customer->id,
                'order_id' => $return->id, 'amount' => $total, 'cash' => $return->cash,
                'description' => $cash ? 'مرتجع نقدي للفاتورة '.$original->id : 'مرتجع لرصيد العميل للفاتورة '.$original->id,
                'debit' => 0, 'credit' => 1, 'balance' => $account ? $account->balance : $customer->balance,
                'date' => now()->toDateString(),
            ]);
            return $return;
        });
        return redirect()->route('admin.pos.index', ['type' => 7, 'customer_id' => $data['customer_id'], 'invoice_id' => $data['invoice_id']])
            ->with('success', 'تم تنفيذ المرتجع رقم '.$return->id.' بنجاح.');
    }
}
