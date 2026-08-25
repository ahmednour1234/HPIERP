<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseSeller;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Toastr;

class CourseSellerController extends Controller
{
    // Display a listing of CourseSeller records
   public function index(Request $request)
    {
        $adminId = Auth::guard('admin')->id(); // Get the authenticated admin's ID
    $query = CourseSeller::with(['admins', 'sellers'])
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
        $courseSellers = $query->paginate(10)->appends($request->all());

        return view('admin-views.coursesellers.index', compact('courseSellers'));
    }

    // Show the form for creating a new CourseSeller record
    public function create()
    {
 $adminId = Auth::guard('admin')->id(); // Get the authenticated admin's ID

        // Fetch sellers linked to the authenticated admin through the admin_seller table
        $sellers = Seller::join('admin_sellers', 'admins.id', '=', 'admin_sellers.seller_id')
                    ->where('admin_sellers.admin_id', $adminId)
                    ->select('admins.*')
                    ->get();
                    return view('admin-views.coursesellers.create', compact('sellers'));
    }

    // Store a new CourseSeller record
    public function store(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|exists:admins,id',
            'link' => 'nullable|url|max:255', // Validate 'link' as a URL
           'name' => 'required|max:500', // Validate 'link' as a URL
        ]);

        $courseSeller = new CourseSeller();
        $courseSeller->admin_id = Auth::guard('admin')->id(); // Get admin ID from authenticated user
        $courseSeller->seller_id = $request->seller_id;
        $courseSeller->link = $request->link;
                $courseSeller->name = $request->name;
        $courseSeller->save();

        Toastr::success('CourseSeller record created successfully.');
        return redirect()->route('admin.coursesellers.index');
    }

    // Show the form for editing the specified CourseSeller record
    public function edit($id)
    {
        $courseSeller = CourseSeller::findOrFail($id);
        $sellers = Seller::all(); // Retrieve sellers for the dropdown selection
        return view('admin.coursesellers.edit', compact('courseSeller', 'sellers'));
    }

    // Update the specified CourseSeller record
    public function update(Request $request, $id)
    {
        $request->validate([
            'seller_id' => 'required|exists:admins,id',
            'link' => 'nullable|url|max:255',
        ]);

        $courseSeller = CourseSeller::findOrFail($id);
        $courseSeller->seller_id = $request->seller_id;
        $courseSeller->link = $request->link;
        $courseSeller->save();

        Toastr::success('CourseSeller record updated successfully.');
        return redirect()->route('admin.coursesellers.index');
    }

    // Delete the specified CourseSeller record
    public function destroy($id)
    {
        $courseSeller = CourseSeller::findOrFail($id);
        $courseSeller->delete();

        Toastr::success('CourseSeller record deleted successfully.');
        return redirect()->route('admin.coursesellers.index');
    }
}
