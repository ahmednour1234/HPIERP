<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Salary; 
use App\Models\Seller;
use App\Models\AdminSeller;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class SalaryController extends Controller
{
    public function create()
    {
        // Retrieve all salary records
        $salaries = Salary::all();
        $sellers = Seller::all();
        return view('admin-views.salary.index', compact('salaries','sellers'));
    }
     public function createrating()
    {
        // Retrieve all salary records
        $salaries = Salary::all();
$adminId = Auth::guard('admin')->id();
$sellerIds = AdminSeller::where('admin_id', $adminId)->pluck('seller_id');

// Get sellers associated with the admin
$sellers = Seller::whereIn('id', $sellerIds)->get();

        return view('admin-views.salary.rating', compact('salaries','sellers'));
    }
public function index(Request $request)
{
    // Fetch all sellers for the dropdown filter
    $sellers = Seller::all();

    // Get search parameters
    $sellerId = $request->input('seller_id');
    $month = $request->input('month');

    // Query salaries based on search parameters
    $query = Salary::with('seller')->orderby('id','desc'); // eager load seller details

    // Apply filters if they are set
    if ($sellerId) {
        $query->where('seller_id', $sellerId);
    }

    if ($month) {
        $query->where('month', $month); // Use the month directly as "YYYY-MM"
    }

    // Paginate results
    $salaries = $query->paginate(10);

    // Pass data to the view
    return view('admin-views.salary.list', compact('salaries', 'sellers', 'sellerId', 'month'));
}


public function showsalary($id)
{
    // Assuming the Salary model has a seller relationship
    $salary = Seller::where('id', $id)->first();

    if (!$salary) {
        return response()->json(['message' => 'Salary record not found.'], 404);
    }

    return response()->json($salary);
}

    public function show($id)
    {
        // Find the salary record by ID
        $salary = Salary::with('seller')->find($id);

        if (!$salary) {
            Toastr::error('Salary record not found.');
            return redirect()->route('admin.salaries.index');
        }

        // كان المسار admin.salaries.show وهو غير موجود؛ قوالب هذا المشروع
        // تحت admin-views.salary.*، فكانت الصفحة تعطي 500 دائمًا.
        return view('admin-views.salary.show', compact('salary'));
    }

public function store(Request $request)
{
    // Validate incoming request
    $validator = Validator::make($request->all(), [
        'seller_id' => 'required|exists:admins,id',
        'salary' => 'nullable|numeric',
        'commission' => 'nullable|numeric',
        'number_of_visitors' => 'nullable|integer',
        'result_of_visitors' => 'nullable|numeric',
        'salary_of_visitors' => 'nullable|numeric',
         'number_of_days' => 'nullable|numeric',
        'transport_amount' => 'nullable|numeric',
        'collection_incentive' => 'nullable|numeric',
        'note' => 'nullable',
        'notemanager' => 'nullable',
        'discount' => 'nullable|numeric',
        'other' => 'nullable|numeric',
        'score' => 'required|numeric',
        'month' => 'required|date_format:Y-m',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Check for existing record
    $existingSalary = Salary::where('seller_id', $request->seller_id)
        ->where('month', $request->month)
        ->first();

    if ($existingSalary) {
        // Update all fields except `score` and `month`
        $existingSalary->update([
            'salary' => $request->salary,
            'commission' => $request->commission,
            'number_of_visitors' => $request->number_of_visitors,
            'result_of_visitors' => $request->result_of_visitors,
            'salary_of_visitors' => $request->salary_of_visitors,
            'transport_amount' => $request->transport_amount,
            'collection_incentive' => $request->collection_incentive ?? 0,
            'discount' => $request->discount,
            
        ]);

        Toastr::success('Salary record updated successfully.');
        return redirect()->route('admin.salaries.index');
    }

    // If there is no existing record, create a new salary record
    Salary::create($request->all());

    // Update the seller's values after creating the salary record
    DB::table('admins')
        ->where('id', $request->seller_id)
        ->update([
            'result_visitors' => 0,
            'commission' => 0,
            'note' => '',
        'score' => 0,
                'number_of_days' => 0,


        ]);

    Toastr::success('Salary record created successfully.');
    return redirect()->route('admin.salaries.index');
}

public function storerating(Request $request)
{
    // Validate incoming request
    $validator = Validator::make($request->all(), [
        'seller_id' => 'required|exists:admins,id',
        'score' => 'required|numeric',
                'note' => 'required',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Update the score in the sellers table directly
    $seller = Seller::find($request->seller_id);

    if ($seller) {
        // Update the seller's score
        $seller->score = $request->score;
        $seller->note = $request->note;

        // Save the changes to the seller
        $seller->save();

        Toastr::success('Score updated for the seller.');
    } else {
        Toastr::error('Seller not found.');
    }

    return redirect()->route('admin.seller.list');
}

    public function update(Request $request, $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'salary' => 'required|numeric',
            'commission' => 'required|numeric',
            'number_of_visitors' => 'required|integer',
            'result_of_visitors' => 'required|numeric',
            'salary_of_visitors' => 'required|numeric',
            'transport_amount' => 'required|numeric',
            'score' => 'required|numeric',
                        'discount' => 'required|numeric',
            'month' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Find the salary record
        $salary = Salary::with('seller')->find($id);

        if (!$salary) {
            Toastr::error('Salary record not found.');
            return redirect()->route('admin.salaries.index');
        }

        // Update the record
        $salary->update($request->all());

        Toastr::success('Salary record updated successfully.');
        return redirect()->route('admin.salaries.index');
    }
}
