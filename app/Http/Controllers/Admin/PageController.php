<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SafeHtmlService;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Page::query();
        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('is_active', false);
        }
        if ($request->filled('q')) {
            $keyword = '%'.str_replace(['%', '_'], ['\%', '\_'], trim((string) $request->query('q'))).'%';
            $query->where(fn ($inner) => $inner->where('title', 'like', $keyword)->orWhere('slug', 'like', $keyword));
        }

        return view('admin.pages.index', ['pages' => $query->latest('updated_at')->paginate(24)->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['is_active' => true])]);
    }

    public function store(Request $request, SafeHtmlService $html, SecurityAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request, $html);
        $page = Page::query()->create($data);
        $this->audit($audit, $request, 'admin_page_created', $page);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Đã tạo trang nội dung.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page, SafeHtmlService $html, SecurityAuditService $audit): RedirectResponse
    {
        $page->update($this->validated($request, $html, $page));
        $this->audit($audit, $request, 'admin_page_updated', $page);

        return back()->with('success', 'Đã cập nhật trang nội dung.');
    }

    public function destroy(Request $request, Page $page, SecurityAuditService $audit): RedirectResponse
    {
        $page->update(['is_active' => false]);
        $this->audit($audit, $request, 'admin_page_hidden', $page);

        return back()->with('success', 'Đã ẩn trang khỏi storefront.');
    }

    private function validated(Request $request, SafeHtmlService $html, ?Page $page = null): array
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title'))),
            'is_active' => $request->boolean('is_active'),
        ]);
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['content'] = $html->clean($data['content'] ?? '');

        return $data;
    }

    private function audit(SecurityAuditService $audit, Request $request, string $action, Page $page): void
    {
        $audit->record($action, [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ], ['page_id' => $page->id, 'slug' => $page->slug]);
    }
}
