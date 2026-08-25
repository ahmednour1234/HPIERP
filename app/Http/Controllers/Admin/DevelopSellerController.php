<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DevelopSeller;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Toastr;
use App\CPU\translate; // Replace this with the correct namespace

class DevelopSellerController extends Controller
{
    // Display a listing of DevelopSeller records
   public function index(Request $request,$type)
{
    $adminId = Auth::guard('admin')->id(); // Get the authenticated admin's ID
    $query = DevelopSeller::with(['admins', 'sellers'])->where('type',$type)
                ->where('admin_id', $adminId);
    // Initialize search term
    $searchTerm = $request->input('search', '');

    // Apply search filters if search term is provided
    if (!empty($searchTerm)) {
        $query->whereHas('sellers', function ($q) use ($searchTerm) {
            $q->where('f_name', 'like', '%' . $searchTerm . '%')
              ->orWhere('l_name', 'like', '%' . $searchTerm . '%')
              ->orWhere('email', 'like', '%' . $searchTerm . '%');
        });
    }

    // Execute the query with pagination
    $developSellers = $query->paginate(10)->appends($request->all());

    return view('admin-views.developsellers.index', compact('developSellers', 'searchTerm','type'));
}



    // Show the form for creating a new DevelopSeller record
    public function create($type)
    {
        $adminId = Auth::guard('admin')->id(); // Get the authenticated admin's ID

        // Fetch sellers linked to the authenticated admin through the admin_seller table
        $sellers = Seller::join('admin_sellers', 'admins.id', '=', 'admin_sellers.seller_id')
                    ->where('admin_sellers.admin_id', $adminId)
                    ->select('admins.*')
                    ->get();

        return view('admin-views.developsellers.create', compact('sellers','type'));
    }

    // Store a new DevelopSeller record
    public function store(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|exists:admins,id',
            'note' => 'nullable',
        ]);

        $developSeller = new DevelopSeller();
        $developSeller->admin_id = Auth::guard('admin')->id();
        $developSeller->seller_id = $request->seller_id;
        $developSeller->note = $request->note;
        $developSeller->type = $request->type;
        $developSeller->date = $request->date;
        $developSeller->save();

        Toastr::success('DevelopSeller record created successfully.');
        return redirect()->back();
    }

    // Show the form for editing the specified DevelopSeller record
    public function edit($id)
    {
        $developSeller = DevelopSeller::findOrFail($id);
        $sellers = Seller::all(); // Fetch all sellers for the dropdown selection
        return view('admin-views.developsellers.edit', compact('developSeller', 'sellers'));
    }

    // Update the specified DevelopSeller record
    public function update(Request $request, $id)
    {
        $request->validate([
            'seller_id' => 'required|exists:admins,id',
            'note' => 'nullable',
        ]);

        $developSeller = DevelopSeller::findOrFail($id);
        $developSeller->seller_id = $request->seller_id;
        $developSeller->note = $request->note;
        $developSeller->save();

        Toastr::success('DevelopSeller record updated successfully.');
        return redirect()->back();
    }

    // Delete the specified DevelopSeller record
    public function destroy($id)
    {
        $developSeller = DevelopSeller::findOrFail($id);
        $developSeller->delete();

        Toastr::success('DevelopSeller record deleted successfully.');
        return redirect()->route('admin.developsellers.index');
    }
public function status(Request $request,$id)
{
    // Find the DevelopSeller record or fail
    $developSeller = DevelopSeller::findOrFail($id);
    // dd($developSeller);
    // Toggle the active status
    $developSeller->active = 1;

    $developSeller->save();

    // Update the seller's holidays if the status is being approved (active = true)

        $seller = $developSeller->sellers; // Assuming there's a relationship
        if ($seller) {
            $seller->holidays--;
            $seller->save();
        }
    

    Toastr::success('تمت الموافقة علي الاجازة');

    return redirect()->back();
}


}
