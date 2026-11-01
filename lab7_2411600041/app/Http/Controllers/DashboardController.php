<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use App\Support\DashboardStats;

class DashboardController extends Controller
{
    public function index()
    {
        $recentItems = GradeItem::orderBy('updated_at', 'desc')->take(6)->get();

        $hour = now()->format('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

        return view('dashboard', array_merge(
            DashboardStats::summary(),
            compact('greeting', 'recentItems')
        ));
    }
}