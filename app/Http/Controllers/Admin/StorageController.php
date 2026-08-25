<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\CPU\Helpers;
use App\Models\Storage;
use Brian2694\Toastr\Facades\Toastr;
use function App\CPU\translate;

class StorageController extends Controller
{
    public function __construct(
        private Storage $storage
    ){}

    public function index(): View|Factory|Application
    {
        $storages = $this->storage->latest()->paginate(Helpers::pagination_limit());
        return view('admin-views.storage.index', compact('storages'));
    }

    public function create(): View|Factory|Application
    {
        return view('admin-views.storage.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
        ], [
            'name.required' => translate('Name is required'),
        ]);

        $storage = new Storage();
        $storage->name = $request->name;
        $storage->save();

        Toastr::success(translate('Storage stored successfully'));
        return back();
    }

    public function edit($id): View|Factory|Application
    {
        $storage = $this->storage->find($id);
        return view('admin-views.storage.edit', compact('storage'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
        ], [
            'name.required' => translate('Name is required'),
        ]);

        $storage = $this->storage->find($id);
        $storage->name = $request->name;
        $storage->save();

        Toastr::success(translate('Storage updated successfully'));
        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $storage = $this->storage->find($request->id);
        $storage->delete();

        Toastr::success(translate('Storage deleted successfully'));
        return back();
    }
}
