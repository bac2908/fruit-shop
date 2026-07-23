<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Category::query()->with(['parent'])->withCount(['products', 'children']);
        if ($request->query('status') === 'trashed') {
            $query->onlyTrashed();
        } elseif ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('is_active', false);
        }

        if ($request->filled('q')) {
            $keyword = '%'.str_replace(['%', '_'], ['\%', '\_'], trim((string) $request->query('q'))).'%';
            $query->where(fn ($inner) => $inner->where('name', 'like', $keyword)->orWhere('slug', 'like', $keyword));
        }

        return view('admin.categories.index', [
            'categories' => $query->orderBy('sort_order')->orderBy('name')->paginate(30)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Category(['is_active' => true, 'sort_order' => 0]));
    }

    public function store(Request $request, SecurityAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $category = Category::query()->create($data);
        $this->audit($audit, $request, 'admin_category_created', ['category_id' => $category->id]);

        return redirect()->route('admin.categories.edit', $category)->with('success', 'Đã tạo danh mục.');
    }

    public function edit(Category $category): View
    {
        return $this->form($category);
    }

    public function update(Request $request, Category $category, SecurityAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request, $category);
        if ((int) ($data['parent_id'] ?? 0) === $category->id) {
            throw ValidationException::withMessages(['parent_id' => 'Danh mục không thể là cha của chính nó.']);
        }
        if (! empty($data['parent_id']) && $category->descendants()->contains('id', (int) $data['parent_id'])) {
            throw ValidationException::withMessages(['parent_id' => 'Không thể chọn danh mục con làm danh mục cha.']);
        }

        $category->update($data);
        $this->audit($audit, $request, 'admin_category_updated', ['category_id' => $category->id]);

        return back()->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Request $request, Category $category, SecurityAuditService $audit): RedirectResponse
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Hãy chuyển sản phẩm và danh mục con trước khi đưa danh mục này vào thùng rác.']);
        }

        $category->delete();
        $this->audit($audit, $request, 'admin_category_archived', ['category_id' => $category->id]);

        return redirect()->route('admin.categories.index')->with('success', 'Đã đưa danh mục vào thùng rác.');
    }

    public function restore(Request $request, int $category, SecurityAuditService $audit): RedirectResponse
    {
        $model = Category::withTrashed()->findOrFail($category);
        $model->restore();
        $model->update(['is_active' => false]);
        $this->audit($audit, $request, 'admin_category_restored', ['category_id' => $model->id]);

        return redirect()->route('admin.categories.edit', $model)->with('success', 'Đã khôi phục danh mục ở trạng thái tạm ẩn.');
    }

    private function form(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::query()
                ->when($category->exists, fn ($query) => $query->where('id', '!=', $category->id))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->input('slug') ?: $request->input('name'))),
            'is_active' => $request->boolean('is_active'),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'icon_url' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function audit(SecurityAuditService $audit, Request $request, string $action, array $metadata): void
    {
        $audit->record($action, [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ], $metadata);
    }
}
