<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class Dashboard extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'keyword' => 'max:255',
        ]);

        $keyword = $request->input('keyword');
        $tasks = Task::select(
            'tasks.*',
            'tasks.id as key,'
        )
            ->with('task_kind')
            ->with('task_status')
            ->with('assigner')
            ->with('user')
            ->with('project')
            //@TODOタスク優先度テーブルが作成されたらコメントアウト解除
            //->with('task_priority')
            ->join('projects', 'tasks.project_id', 'projects.id') 
            ->where('assigner_id', '=', $request->user()->id);
        if ($request->has('keyword') && $keyword != '') {
            $tasks
                ->join('users as search_users', 'tasks.created_user_id', 'search_users.id')
                ->join('task_kinds as search_task_kinds', 'tasks.task_kind_id', 'search_task_kinds.id');
            $tasks
                ->where(function ($tasks) use ($keyword) {
                    $tasks
                        ->where('search_task_kinds.name', 'like', '%'.$keyword.'%')
                        ->orWhere('projects.key', 'like', '%'.$keyword.'%')
                        ->orWhere('tasks.name', 'like', '%'.$keyword.'%')
                        ->orWhere('search_users.name', 'like', '%'.$keyword.'%');
                });
        }
        $tasks = $tasks
            ->sortable('name')
            ->paginate(20)
            ->appends(['keyword' => $keyword]);

        return view('dashboard', compact('tasks'), [
            'keyword' => $keyword,
        ]);
    }
}
