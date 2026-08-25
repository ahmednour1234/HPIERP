<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DevelopSeller;
use App\Models\CourseSeller;

use App\Models\Seller;
use App\Models\Salary;
use App\Models\AdminSeller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Toastr;
use App\CPU\translate; // Remove this if not needed
use Illuminate\Http\JsonResponse;

class DeveloperSellerController extends Controller
{
    // Display a listing of DevelopSeller records
    public function index(Request $request, $type)
    {
        // Get the authenticated seller's ID (not admin)
        $sellerId = Auth::user()->id;

        // Query to fetch DevelopSeller records based on seller ID and type
        $query = DevelopSeller::where('seller_id',$sellerId)->where('type', $type)->get();

        // Initialize search term

        // Apply search filters if search term is provided
      
        // Execute the query with pagination

        // Return data as JSON response
        return response()->json([
            'data' => $query,
            'type' => $type
        ]);
    }
public function indexcourses(Request $request)
{
    $sellerId = Auth::user()->id;

    // Initialize the query for CourseSeller model
    $query = CourseSeller::where('seller_id', $sellerId);

    // Get the course sellers without pagination
    $courseSellers = $query->get();

    // Return the results as a JSON response
    return response()->json([
        'data' => $courseSellers,
    ]);
}
public function storeImages(Request $request)
{
    // Validate the incoming request
    $request->validate([
        'course_id' => 'required|exists:course_sellers,id', // Ensure the course exists in the table
        'images' => 'required|array', // Ensure images is an array
        'images.*' => 'nullable', // Ensure each item in the array is a valid image
    ]);

    // Find the CourseSeller row by course_id
    $courseSeller = CourseSeller::find($request->course_id);

    // Initialize an array to store the file paths of the uploaded images
    $uploadedImages = [];

    // Loop through each image in the 'images' array
    foreach ($request->file('images') as $image) {
        // Store the image in the 'public/images' directory
        $imagePath = $image->store('images', 'public');

        // Add the path to the uploaded images array
        $uploadedImages[] = $imagePath;
    }

    // Update the images field in the database (assuming 'images' column stores an array or JSON)
    $courseSeller->img = $uploadedImages;

    // Save the updated course seller row
    $courseSeller->save();

    // Return a JSON response indicating success
    return response()->json([
        'message' => 'Images uploaded and updated successfully.',
        'data' => $courseSeller,
    ]);
}


public function indexsalary(Request $request)
{
    // Fetch the authenticated user's ID
    $sellerId = Auth::user()->id;

    // Get search parameters (month), default to previous month if not provided
    $month = $request->input('month', now()->subMonth()->format('Y-m')); // Default to previous month (YYYY-MM)

    // Query salaries based on search parameters
    $query = Salary::with('seller'); // eager load seller details

    // Apply filters if they are set
    if ($sellerId) {
        $query->where('seller_id', $sellerId);
    }

    if ($month) {
        $query->where('month', $month); // Use the month directly as "YYYY-MM"
    }

    // Get the salaries (can paginate if needed, but here we're using `get()` for simplicity)
    $salaries = $query->get();

    // Return the data as a JSON response
    return response()->json([
        'salaries' => $salaries,
        'month' => $month
    ]);
}


    // Store a new DevelopSeller record
public function store(Request $request)
{
    $request->validate([
        'note' => 'nullable',
        'type' => 'required|integer',
        'date' => 'nullable|date',
    ]);

    // Get the authenticated seller's ID
    $sellerId = Auth::user()->id; // Ensure the correct guard is used

    // Retrieve the admin_id from the admin_seller table where seller_id matches
    $adminSeller = AdminSeller::where('seller_id', $sellerId)->first();

    if (!$adminSeller) {
        return response()->json([
            'message' => 'Admin not associated with the seller.',
        ], 404);
    }

    // Create a new DevelopSeller record
    $developSeller = new DevelopSeller();
    $developSeller->seller_id = $sellerId; // Use the authenticated seller's ID
    $developSeller->admin_id = $adminSeller->admin_id; // Use the retrieved admin_id
    $developSeller->note = $request->note;
    $developSeller->type = $request->type;
    $developSeller->date = $request->date;
    $developSeller->save();

    // Return success message in JSON format
    return response()->json([
        'message' => 'DevelopSeller record created successfully.',
        'data' => $developSeller,
    ], 200);
}
public function storedevelop(Request $request): JsonResponse
{
    $request->validate([
        'seller_id' => 'required|exists:admins,id',
        'note' => 'nullable',
    ]);

    $developSeller = new DevelopSeller();
    $developSeller->admin_id =Auth::user()->id;
    $developSeller->seller_id = $request->seller_id;
    $developSeller->note = $request->note;
    $developSeller->type = 0;
    $developSeller->save();

    return response()->json([
        'success' => true,
        'message' => 'DevelopSeller record created successfully.',
        'data' => $developSeller,
    ]);
}


    // Delete the specified DevelopSeller record
    public function destroy($id)
    {
        $developSeller = DevelopSeller::findOrFail($id);
        $developSeller->delete();

        // Return success message in JSON format
        return response()->json([
            'message' => 'DevelopSeller record deleted successfully.'
        ]);
    }
}
