<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DocumentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $categories = DocumentCategory::withCount('documents')->orderBy('name')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:document_categories,name'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
        ]);
        $cat = DocumentCategory::create($data);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'category.created', 'entity_type' => DocumentCategory::class, 'entity_id' => $cat->id, 'metadata' => ['name' => $cat->name]]);

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(DocumentCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, DocumentCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('document_categories')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
        ]);
        $category->update($data);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'category.updated', 'entity_type' => DocumentCategory::class, 'entity_id' => $category->id, 'metadata' => ['name' => $category->name]]);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated (rule suggestions use these keywords).');
    }

    public function destroy(DocumentCategory $category): RedirectResponse
    {
        $id = $category->id;
        $category->delete();
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'category.deleted', 'entity_type' => DocumentCategory::class, 'entity_id' => $id, 'metadata' => []]);

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }
}
