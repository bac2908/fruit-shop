<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Banner::query();
        if ($request->filled('placement')) {
            $query->where('placement', $request->query('placement'));
        }
        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('is_active', false);
        }

        return view('admin.banners.index', [
            'banners' => $query->orderBy('placement')->orderBy('sort_order')->paginate(24)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.form', ['banner' => new Banner(['placement' => 'hero', 'is_active' => true, 'sort_order' => 0])]);
    }

    public function store(Request $request, SecurityAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image_url'] = $this->imagePath($request);
        $banner = Banner::query()->create($data);
        $this->audit($audit, $request, 'admin_banner_created', $banner);

        return redirect()->route('admin.banners.edit', $banner)->with('success', 'Đã tạo banner.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner, SecurityAuditService $audit): RedirectResponse
    {
        $data = $this->validated($request, true);
        if ($request->hasFile('image')) {
            $this->deleteLocalImage($banner->image_url);
            $data['image_url'] = $this->imagePath($request);
        } elseif ($request->filled('image_url')) {
            $data['image_url'] = trim((string) $request->input('image_url'));
        } else {
            $data['image_url'] = $banner->image_url;
        }

        $banner->update($data);
        $this->audit($audit, $request, 'admin_banner_updated', $banner);

        return back()->with('success', 'Đã cập nhật banner.');
    }

    public function destroy(Request $request, Banner $banner, SecurityAuditService $audit): RedirectResponse
    {
        $this->deleteLocalImage($banner->image_url);
        $bannerId = $banner->id;
        $placement = $banner->placement;
        $banner->delete();
        $audit->record('admin_banner_deleted', [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ], ['banner_id' => $bannerId, 'placement' => $placement]);

        return redirect()->route('admin.banners.index')->with('success', 'Đã xóa banner.');
    }

    private function validated(Request $request, bool $updating = false): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'placement' => ['required', 'in:hero,promo'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'image' => [$updating ? 'nullable' : 'required_without:image_url', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:1500'],
            'link_url' => ['nullable', 'string', 'max:1500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);
    }

    private function imagePath(Request $request): string
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->storeAs('banners', Str::uuid().'.'.$file->getClientOriginalExtension(), 'public');

            return 'storage/'.$path;
        }

        return trim((string) $request->input('image_url'));
    }

    private function deleteLocalImage(?string $path): void
    {
        if (str_starts_with((string) $path, 'storage/')) {
            Storage::disk('public')->delete(Str::after((string) $path, 'storage/'));
        }
    }

    private function audit(SecurityAuditService $audit, Request $request, string $action, Banner $banner): void
    {
        $audit->record($action, [
            'user_id' => $request->user()->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ], ['banner_id' => $banner->id, 'placement' => $banner->placement]);
    }
}
