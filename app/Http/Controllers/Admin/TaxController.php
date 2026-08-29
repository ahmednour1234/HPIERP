<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\CPU\Helpers;
use App\Models\Taxe;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;

class TaxController extends Controller
{
    public function __construct(
        private Taxe $taxes
    ){}

    public function index(): View|Factory|Application
    {
        $taxes = $this->taxes->latest()->paginate(Helpers::pagination_limit());
        return view('admin-views.tax.index', compact('taxes'));
    }

    public function create(): View|Factory|Application
    {
        // This renders the list view, which iterates $taxes - without it the
        // page died with "Undefined variable $taxes".
        $taxes = $this->taxes->latest()->paginate(Helpers::pagination_limit());

        return view('admin-views.tax.index', compact('taxes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'amount'=>'required',
            'active'=>'nullable'
        ]);

        $tax = new Taxe();
        $tax->name = $request->name;
        $tax->amount = $request->amount;
        $tax->active = $request->active ?? 0;

        $tax->save();

        Toastr::success(translate('الضريبة خزنت بنجاح'));
        return back();
    }

    public function edit($id): View|Factory|Application
    {
        $taxe = $this->taxes->findOrFail($id);

        // The view refers to $taxe; passing it as $tax left it undefined and
        // the page answered 500.
        return view('admin-views.tax.edit', compact('taxe'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'amount'=>'required',
            'active'=>'nullable'
        ]);

        $tax = $this->taxes->find($id);
         $tax->name = $request->name;
        $tax->amount = $request->amount;
        $tax->active = $request->active;
        $tax->save();

        Toastr::success(translate('الضريبة عدلت بنجاح '));
        return back();
    }
public function status(Request $request): RedirectResponse
{
    $tax = $this->taxes->find($request->id);
    
    // Toggle the active status (if 0, set 1; if 1, set 0)
    $tax->active = $tax->active ? 0 : 1;
    
    $tax->save();

    Toastr::success(translate('تم تغيير حالة الضريبة'));

    return back();
}

    public function delete(Request $request): RedirectResponse
    {
        $tax = $this->taxes->find($request->id);
        $tax->delete();

        Toastr::success(translate('الضربية حذفت بنجاح'));
        return back();
    }
}
