<?php

namespace App\Http\Controllers;

use App\Models\Weekly_Goals;
use Illuminate\Http\Request;

class WeeklyGoalsController extends Controller
{
    //وضع هدف جديد من عند المدير
    public function add(Request $request)
    {
                // التحقق من صلاحية المستخدم (يجب أن يكون مديرًا)
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => "غير مصرح لك بتنفيذ هذا الإجراء"
            ], 403);
        }
        $request->validate([
           'target_pages' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'title' => 'required|string|max:255',
        ]);

        $weeklyGoal = Weekly_Goals::create([
            'target_pages' => $request->target_pages,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'title' => $request->get('title'),
        ]);

        return response()->json($weeklyGoal, 201);
    }
//---------------------------------------------------------------------------------------------    
//عرض أهداف الأسبوع الحالية
public function cuurentGoals(){
    $currentDate = now();
    $currentGoals = Weekly_Goals::where('start_date', '<=', $currentDate)
        ->where('end_date', '>=', $currentDate)
        ->get();

    return response()->json($currentGoals);
}
//---------------------------------------------------------------------------------------------
}
