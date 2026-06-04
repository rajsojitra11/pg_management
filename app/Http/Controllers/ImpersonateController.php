<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\User\Models\User;

class ImpersonateController extends Controller
{
    public function users(Request $request)
    {
        $query = User::query()
            ->where('id', '!=', auth()->id())
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Super_Admin');
            })
            ->where('status', 'Active')
            ->select('id', 'name', 'email');

        if ($request->filled('q')) {
            $search = mb_strtolower($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(username) like ?', ["%{$search}%"])
                    ->orWhereHas('roles', function ($r) use ($search) {
                        $r->whereRaw('LOWER(name) like ?', ["%{$search}%"]);
                    });
            });
        }

        $limit = $request->filled('q') ? 50 : 5;
        $users = $query->orderBy('name')->limit($limit)->get();

        return response()->json($users);
    }
}
