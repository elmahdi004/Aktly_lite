<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminCouponController extends Controller
{
    /**
     * Get all coupons with usage statistics
     */
    public function index(Request $request)
    {
        $query = Coupon::query()->withCount('usages');

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('active', true)
                      ->where(function ($q) {
                          $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                      });
            } elseif ($status === 'inactive') {
                $query->where('active', false);
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        $coupons = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        // Add computed fields
        $coupons->getCollection()->transform(function ($coupon) {
            $coupon->users_remaining = $coupon->usersRemaining();
            $coupon->dossiers_remaining = $coupon->dossiersRemaining();
            $coupon->is_expired = $coupon->isExpired();
            $coupon->total_users = $coupon->usages()->distinct('user_id')->count();
            $coupon->total_dossiers = $coupon->usages()->sum('dossiers_used');
            return $coupon;
        });

        return response()->json($coupons);
    }

    /**
     * Get single coupon with detailed usage
     */
    public function show($id)
    {
        $coupon = Coupon::with(['usages.user:id,name,email'])
                        ->findOrFail($id);

        $coupon->users_remaining = $coupon->usersRemaining();
        $coupon->dossiers_remaining = $coupon->dossiersRemaining();
        $coupon->is_expired = $coupon->isExpired();
        $coupon->total_users = $coupon->usages()->distinct('user_id')->count();
        $coupon->total_dossiers = $coupon->usages()->sum('dossiers_used');

        return response()->json($coupon);
    }

    /**
     * Create new coupon
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code',
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:now',
            'max_users' => 'nullable|integer|min:1',
            'max_dossiers' => 'nullable|integer|min:1',
            'stripe_coupon_id' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon = Coupon::create([
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'expires_at' => $request->expires_at,
            'max_users' => $request->max_users,
            'max_dossiers' => $request->max_dossiers,
            'stripe_coupon_id' => $request->stripe_coupon_id,
            'active' => $request->active ?? true,
        ]);

        return response()->json([
            'message' => 'Coupon created successfully',
            'coupon' => $coupon
        ], 201);
    }

    /**
     * Update existing coupon
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:50|unique:coupons,code,' . $id,
            'description' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
            'max_users' => 'nullable|integer|min:1',
            'max_dossiers' => 'nullable|integer|min:1',
            'stripe_coupon_id' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon->update($request->only([
            'code',
            'description',
            'expires_at',
            'max_users',
            'max_dossiers',
            'stripe_coupon_id',
            'active',
        ]));

        return response()->json([
            'message' => 'Coupon updated successfully',
            'coupon' => $coupon
        ]);
    }

    /**
     * Delete coupon (soft delete by deactivating)
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        // Check if coupon has been used
        if ($coupon->usages()->exists()) {
            // Just deactivate if it has usage history
            $coupon->update(['active' => false]);
            return response()->json([
                'message' => 'Coupon deactivated (has usage history)'
            ]);
        }

        // Actually delete if never used
        $coupon->delete();
        return response()->json([
            'message' => 'Coupon deleted successfully'
        ]);
    }

    /**
     * Toggle coupon active status
     */
    public function toggleStatus($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['active' => !$coupon->active]);

        return response()->json([
            'message' => 'Coupon status updated',
            'coupon' => $coupon
        ]);
    }

    /**
     * Generate random coupon code
     */
    public function generateCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }

    /**
     * Get coupon usage statistics
     */
    public function statistics()
    {
        $stats = [
            'total_coupons' => Coupon::count(),
            'active_coupons' => Coupon::where('active', true)->count(),
            'expired_coupons' => Coupon::where('expires_at', '<=', now())->count(),
            'total_uses' => CouponUsage::count(),
            'total_users' => CouponUsage::distinct('user_id')->count(),
            'total_dossiers_used' => CouponUsage::sum('dossiers_used'),
        ];

        return response()->json($stats);
    }
}
