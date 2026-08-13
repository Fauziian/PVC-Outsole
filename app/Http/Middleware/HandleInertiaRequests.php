<?php

namespace App\Http\Middleware;

use App\Models\NotifikasiStok;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     * Semua data di sini tersedia di setiap komponen React via usePage().props
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            // Data autentikasi user yang sedang login
            'auth' => [
                'user' => $user ? [
                    'id'       => $user->id,
                    'nama'     => $user->nama,
                    'username' => $user->username,
                    'role'     => $user->role,
                    'is_active'=> $user->is_active,
                ] : null,
            ],

            // Jumlah notifikasi stok yang belum dibaca (untuk badge di topbar)
            'notifikasi_stok_count' => fn () => $user
                ? NotifikasiStok::where('is_read', false)->count()
                : 0,
            'unread_notifications_count' => fn () => $user
                ? NotifikasiStok::where('is_read', false)->count()
                : 0,

            // Flash messages untuk toast notification
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'info'    => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
