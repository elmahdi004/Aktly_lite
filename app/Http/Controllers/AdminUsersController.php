<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminUsersController extends Controller
{
    private function applyFilters(Request $request)
    {
        $query = User::query();

        $period = $request->get('period', 'all');
        if ($period !== 'all') {
            $cutoff = match ($period) {
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
                default => null,
            };
            if ($cutoff) {
                $query->where('created_at', '>=', $cutoff);
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
                $q->orWhere('email', 'like', "%$search%");
            });
        }

        if ($langueUser = $request->get('langue_user')) {
            if (in_array($langueUser, ['fr', 'nl'], true)) {
                $query->where('langue_user', $langueUser);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }
    /**
     * Return filtered/paginated users for admin marketing use.
     * Query params:
     *  - period: day|week|month|year|all (default all)
     *  - search: string (matches name or email)
     *  - per_page: int (default 50)
     */
    public function index(Request $request)
    {
        $query = $this->applyFilters($request);
        $perPage = (int) $request->get('per_page', 50);
        $users = $query->select(
            'id',
            'name',
            'email',
            'langue_user',
            'subscription_plan',
            'subscription_status',
            'cumulative_dossier_count',
            'created_at'
        )->paginate($perPage);
        return response()->json($users);
    }

    // Return all filtered users (ids + minimal fields) without pagination
    public function all(Request $request)
    {
        $limit = (int) $request->get('limit', 2000); // safety cap
        $query = $this->applyFilters($request);
        $collection = $query->limit($limit)->get([
            'id',
            'name',
            'email',
            'langue_user',
            'subscription_plan',
            'subscription_status',
            'cumulative_dossier_count',
            'created_at'
        ]);
        return response()->json([
            'count' => $collection->count(),
            'ids' => $collection->pluck('id'),
            'users' => $collection,
            'truncated' => $collection->count() === $limit && $query->count() > $limit,
        ]);
    }

}
