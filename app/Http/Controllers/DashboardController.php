<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tasks\SummarizeTasks;
use App\Models\Task;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Show an overview of the user's tasks.
     */
    public function __invoke(Request $request, SummarizeTasks $summarize): Response
    {
        $user = $request->user() ?? throw new AuthenticationException;

        return Inertia::render('dashboard', [
            'stats' => $summarize->handle($user),
            'upcomingTasks' => Task::query()->whereBelongsTo($user)->open()->orderByUrgency()->take(5)->get(),
        ]);
    }
}
