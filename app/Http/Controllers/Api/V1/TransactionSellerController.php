<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransactionSeller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionSellerController extends Controller
{
    // List all transactions
  // List all transactions for the authenticated seller
public function index()
{
    // Get the authenticated user's ID as the seller_id
    $sellerId = auth()->user()->id;

    // Fetch transactions only for this seller
    $transactions = TransactionSeller::where('seller_id', $sellerId)->get();

    return response()->json($transactions, 200);
}

    // Create a new transaction
// Create a new transaction
public function store(Request $request)
{
    // Get the authenticated user's ID as the seller_id
    $sellerId = auth()->user()->id;

    // Validate the request data
    $validated = $request->validate([
        'amount' => 'required|numeric',
        'note' => 'nullable|string',
        'img' => 'nullable',
        'account_id'=>'required'
    ]);

    // Include the authenticated user's ID in the validated data
    $validated['seller_id'] = $sellerId;

    // Handle image upload if provided
    if ($request->hasFile('img')) {
        $validated['img'] = $request->file('img')->store('transaction_images', 'public');
    }

    // Create the transaction
    $transaction = TransactionSeller::create($validated);

    return response()->json($transaction, 200);
}


}
