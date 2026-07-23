<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminActionCenterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, AdminActionCenterService $actions): View
    {
        return view('admin.notifications', array_merge(
            $actions->summary(user: $request->user()),
            $actions->groups(user: $request->user())
        ));
    }
}
