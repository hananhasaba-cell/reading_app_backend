<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserProgress;
use App\Models\Weekly_Goals;
class ProgressController extends Controller
{
public function update(Request $request)
{
    $goal = Weekly_Goals::findOrFail($request->weekly_goal_id);

    $progress = UserProgress::updateOrCreate(
        [
            'user_id' => auth()->id(),
            'weekly_goal_id' => $request->weekly_goal_id,
            'book_id' => $request->book_id,
        ],
        [
            'pages_read' => $request->pages_read,
        ]
    );

    // حساب النقاط
    $points = $goal->target_pages ? ($progress->pages_read / $goal->target_pages) * 100 : 0;

    $progress->update([
        'points_earned' => round($points)
    ]);

    return response()->json([
        'progress' => $progress->pages_read,
        'points' => $progress->points_earned
    ]);
}
}