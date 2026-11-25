<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select('sessions.*', 'users.nama as user_nama', 'users.nik as user_nik', 'users.role as user_role')
            ->orderByDesc('last_activity');

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('users.nama', 'like', "%{$cari}%")
                  ->orWhere('users.nik', 'like', "%{$cari}%")
                  ->orWhere('sessions.ip_address', 'like', "%{$cari}%");
            });
        }

        $sessions = $query->paginate(20)->withQueryString();

        $sessions->getCollection()->transform(function ($s) {
            $s->last_activity_formatted = Carbon::createFromTimestamp($s->last_activity)->format('d/m/Y H:i');
            $s->browser = $this->parseBrowser($s->user_agent);
            $s->device  = $this->parseDevice($s->user_agent);
            return $s;
        });

        return view('session.index', compact('sessions'));
    }

    private function parseBrowser($ua)
    {
        if (blank($ua)) return 'Unknown';
        if (str_contains($ua, 'Edg'))     return 'Edge';
        if (str_contains($ua, 'Chrome'))  return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari'))  return 'Safari';
        return 'Unknown';
    }

    private function parseDevice($ua)
    {
        if (blank($ua)) return 'Unknown';
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac'))     return 'macOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone'))  return 'iPhone';
        return 'Unknown';
    }
}