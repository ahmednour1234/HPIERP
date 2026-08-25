<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\CPU\Helpers;
use App\Models\StorageSeller;
use App\Models\Storage;
use App\Models\Admin;
use App\Models\AdminSeller;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;
use Illuminate\Support\Facades\Auth;

class StorageSellerController extends Controller
{
    public function __construct(
        private StorageSeller $storageSeller
    ){}

    public function index(): View|Factory|Application
{
    // Get the authenticated admin's ID
    $adminId = Auth::guard('admin')->id();

    // Retrieve seller IDs associated with the authenticated admin from the admin_sellers table
    $sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

    // Retrieve all storages
    $storages = Storage::all();

    // Retrieve sellers who are associated with the admin, using the seller IDs from the admin_sellers table
    $sellers = Admin::whereIn('id', $sellerIds)->get();

    // Retrieve storage seller relationships, along with associated storage and seller, and paginate the results
    $storageSellers = $this->storageSeller->with(['storage', 'seller'])
                                           ->latest()
                                           ->paginate(Helpers::pagination_limit());

    // Return the view with necessary data
    return view('admin-views.storage.list', compact('storageSellers', 'storages', 'sellers'));
}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'storage_id' => 'required',
            'seller_id' => 'required|array',
            'seller_id.*' => 'exists:admins,id',
        ], [
            'storage_id.required' => translate('Storage ID is required'),
            'seller_id.required' => translate('Seller ID is required'),
            'seller_id.*.exists' => translate('Selected seller is invalid'),
        ]);

        foreach ($request->seller_id as $seller_id) {
            if ($this->storageSeller->where('storage_id', $request->storage_id)->where('seller_id', $seller_id)->exists()) {
                Toastr::error(translate('This storage is already assigned to the selected seller'));
                return back();
            }

            $storageSeller = new StorageSeller();
            $storageSeller->storage_id = $request->storage_id;
            $storageSeller->seller_id = $seller_id;
            $storageSeller->save();
        }

        Toastr::success(translate('StorageSeller stored successfully'));
        return back();
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'storage_id' => 'required',
            'seller_id' => 'required|array',
            'seller_id.*' => 'exists:admins,id',
        ], [
            'storage_id.required' => translate('Storage ID is required'),
            'seller_id.required' => translate('Seller ID is required'),
            'seller_id.*.exists' => translate('Selected seller is invalid'),
        ]);

        $storageSeller = $this->storageSeller->find($id);

        foreach ($request->seller_id as $seller_id) {
            if ($this->storageSeller->where('storage_id', $request->storage_id)->where('seller_id', $seller_id)->exists()) {
                Toastr::error(translate('This storage is already assigned to the selected seller'));
                return back();
            }
        }

        $storageSeller->storage_id = $request->storage_id;
        $storageSeller->seller_id = $request->seller_id[0]; // Assuming update only changes one seller
        $storageSeller->save();

        Toastr::success(translate('StorageSeller updated successfully'));
        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $storageSeller = $this->storageSeller->find($request->id);
        $storageSeller->delete();

        Toastr::success(translate('StorageSeller deleted successfully'));
        return back();
    }
}
