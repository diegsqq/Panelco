<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Services\Ai\AiProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin'])->except(['create', 'store']);
        $this->middleware(['auth'])->only(['create', 'store']);
    }

    public function index(): View
    {
        $categories = DocumentCategory::with('children')->withCount('documents')->orderBy('name')->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    private function accessRules(): array
    {
        return [
            'access_level' => ['sometimes', Rule::in(['private', 'link', 'all_employees'])],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    private function sharedUserIds(array $data): array
    {
        return array_values(array_unique(array_map('intval', $data['user_ids'] ?? [])));
    }

    public function create(): View
    {
        $categories = DocumentCategory::orderBy('name')->get();
        $users = User::where('id', '!=', auth()->id())->orderBy('name')->get();

        return view('admin.categories.create', compact('categories', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255', 'unique:document_categories,name'],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:document_categories,id'],
        ], $this->accessRules()));

        $sharedUserIds = $this->sharedUserIds($data);
        $data['created_by'] = auth()->id();
        $data['access_level'] = $data['access_level'] ?? 'all_employees';

        $cat = DocumentCategory::create($data);
        $cat->authorizedUsers()->sync($sharedUserIds);
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'category.created',
            'entity_type' => DocumentCategory::class,
            'entity_id' => $cat->id,
            'metadata' => [
                'name' => $cat->name,
                'access_level' => $cat->access_level,
                'shared_with' => $sharedUserIds,
            ],
        ]);

        if ($request->input('redirect_to') === 'dashboard') {
            return redirect()->route('dashboard')->with('status', 'Folder created.');
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(DocumentCategory $category): View
    {
        $categories = DocumentCategory::where('id', '!=', $category->id)->orderBy('name')->get();
        $users = User::where('id', '!=', auth()->id())->orderBy('name')->get();

        return view('admin.categories.edit', compact('category', 'categories', 'users'));
    }

    public function update(Request $request, DocumentCategory $category): RedirectResponse
    {
        $data = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255', Rule::unique('document_categories')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:document_categories,id'],
        ], $this->accessRules()));

        $sharedUserIds = $this->sharedUserIds($data);
        $data['access_level'] = $data['access_level'] ?? 'all_employees';

        $category->update($data);
        $category->authorizedUsers()->sync($sharedUserIds);
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'category.updated',
            'entity_type' => DocumentCategory::class,
            'entity_id' => $category->id,
            'metadata' => [
                'name' => $category->name,
                'access_level' => $category->access_level,
                'shared_with' => $sharedUserIds,
            ],
        ]);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated (rule suggestions use these keywords).');
    }

    /**
     * Suggest search keywords for a category using the configured AI provider.
     * Used by the "Generate with AI" button on the category form.
     */
    public function aiKeywords(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $ai = app(AiProvider::class);
        if (! $ai->available()) {
            return response()->json(['error' => 'AI is not configured. Add AI credentials in .env first.'], 422);
        }

        $text = "Category name: {$data['name']}\n"
            . 'Description: '.($data['description'] !== '' && $data['description'] !== null ? $data['description'] : 'none')."\n"
            . 'Suggest short search keywords that documents belonging to this category would contain.';

        $result = $ai->analyze($text);
        $keywords = array_values(array_filter($result['keywords'] ?? []));

        if (empty($keywords)) {
            return response()->json(['error' => 'AI returned no keywords. Try again.'], 422);
        }

        return response()->json(['keywords' => implode(', ', $keywords)]);
    }

    public function destroy(DocumentCategory $category): RedirectResponse
    {
        $id = $category->id;
        $category->delete();
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'category.deleted', 'entity_type' => DocumentCategory::class, 'entity_id' => $id, 'metadata' => []]);

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }
}
