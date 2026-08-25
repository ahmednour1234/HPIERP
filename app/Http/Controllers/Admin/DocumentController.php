<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use App\Models\Document;
use App\Models\DocumentAttachment;
use Illuminate\Support\Facades\Storage;
use Brian2694\Toastr\Facades\Toastr;

class DocumentController extends Controller
{
    public function index(): View|Factory|Application
    {
        $documents = Document::with('attachments')->latest()->paginate(20);
        return view('admin-views.documents.index', compact('documents'));
    }

    public function create(): View|Factory|Application
    {
        return view('admin-views.documents.create');
    }

public function store(Request $request): RedirectResponse
{
    $data = $request->validate([
        'name'          => 'required|string|max:255',
        'description'   => 'nullable|string',
        'attachments.*' => 'file|mimes:pdf,jpeg,png,jpg,gif,svg|max:20480',
        'links.*'       => 'nullable|url|max:2048',
    ]);

    // 1) Создаем документ
    $doc = Document::create([
        'name'        => $data['name'],
        'description' => $data['description'] ?? null,
    ]);

    // 2) Сохраняем файлы в storage/app/public/documents/{id}
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            // path = documents/12/filename.png
            $path = $file->store("documents/{$doc->id}", 'public');

            DocumentAttachment::create([
                'document_id' => $doc->id,
                'type'        => $file->extension() === 'pdf' ? 'pdf' : 'image',
                // сохраняем относительный путь без "storage/"
                'url'         => $path,
            ]);
        }
    }

    // 3) Сохраняем ссылки
    if (!empty($data['links'])) {
        foreach ($data['links'] as $link) {
            if ($link) {
                DocumentAttachment::create([
                    'document_id' => $doc->id,
                    'type'        => 'link',
                    'url'         => $link,
                ]);
            }
        }
    }

    Toastr::success('تم إنشاء المستند بنجاح', 'نجاح');
    return redirect()->route('admin.documents.index');
}


    public function show(Document $document): View|Factory|Application
    {
        $document->load('attachments');
        return view('admin-views.documents.show', compact('document'));
    }

    public function edit(Document $document): View|Factory|Application
    {
        $document->load('attachments');
        return view('admin-views.documents.edit', compact('document'));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachments.*' => 'file|mimes:pdf,jpeg,png,jpg,gif,svg|max:20480',
            'links.*'       => 'nullable|url|max:2048',
            'remove_attachments' => 'array',
            'remove_attachments.*' => 'integer|exists:document_attachments,id',
        ]);

        $document->update($data);

        // حذف المرفقات التي اختارها المستخدم
        if ($request->filled('remove_attachments')) {
            foreach ($request->input('remove_attachments') as $attId) {
                $att = $document->attachments()->find($attId);
                if ($att) {
                    if (in_array($att->type, ['pdf','image'])) {
                        $filePath = str_replace('/storage/app/public', '', $att->url);
                        Storage::disk('public')->delete($filePath);
                    }
                    $att->delete();
                }
            }
        }

        // إضافة ملفات جديدة
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("documents/{$document->id}", 'public');
                DocumentAttachment::create([
                    'document_id' => $document->id,
                    'type'        => $file->extension() === 'pdf' ? 'pdf' : 'image',
                    'url'         => Storage::url($path),
                ]);
            }
        }

        // إضافة روابط جديدة
        if ($request->filled('links')) {
            foreach ($request->input('links') as $link) {
                if ($link) {
                    DocumentAttachment::create([
                        'document_id' => $document->id,
                        'type'        => 'link',
                        'url'         => $link,
                    ]);
                }
            }
        }

        Toastr::success('تم تحديث المستند بنجاح', 'نجاح');
        return redirect()->route('admin.documents.show', $document->id);
    }

    public function destroy(Document $document): RedirectResponse
    {
        // حذف كل الملفات المرتبطة
        foreach ($document->attachments as $att) {
            if (in_array($att->type, ['pdf','image'])) {
                $filePath = str_replace('/storage/', '', $att->url);
                Storage::disk('public')->delete($filePath);
            }
            $att->delete();
        }

        $document->delete();
        Toastr::success('تم حذف المستند بنجاح', 'نجاح');
        return redirect()->route('admin.documents.index');
    }
}
