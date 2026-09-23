<?php

namespace App\View\Composers;

use App\Models\PermissionCategory;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PermissionsComposer
{
    public function compose(View $view): void
    {
        $view->with('roles', Role::whereNull('agency_id')->orderBy('name')->get());
        $view->with('permissionCategories', PermissionCategory::orderBy('sort_order')->get());
    }
}
