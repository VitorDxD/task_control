<?php

namespace App\Http\Controllers;

use Mail;
use App\Mail\NewTaskMail;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Exports\TasksExport;
use Maatwebsite\Excel\Facades\Excel;

class TaskController extends Controller
{
    public function __construct() 
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $userId = auth()->user()->id;
        $tasks = Task::where('user_id', $userId)
            ->paginate(10)
            ->onEachSide(2);

        return view('task.index', ['tasks' => $tasks]);
    }

    public function create()
    {
        return view('task.create');
    }

    public function store(Request $request)
    {
        $configs = [
            'task' => 'string',
            'limit_date' => 'date'
        ];

        $names = ['limit_date' => 'limit date'];

        $request->validate($configs, [], $names);

        $newTaskData = $request->all();
        $newTaskData['user_id'] = auth()->user()->id;

        $task = Task::create($newTaskData);
        $receiverEmail = auth()->user()->email;
        Mail::to($receiverEmail)->send(new NewTaskMail($task));
        
        return redirect()->route('task.show', ['task' => $task->id]); 
    }

    public function show(Task $task)
    {
        return view('task.show', ['task' => $task]);
    }


    public function edit(Task $task)
    {
        $userId = auth()->user()->id;

        if (!($task->user_id == $userId)) {
            return view('acess-denied');
        }

        return view('task.edit', ['task' => $task]);
    }

    public function update(Request $request, Task $task)
    {
        $configs = [
            'task' => 'string',
            'limit_date' => 'date'
        ];

        $names = ['limit_date' => 'limit date'];

        $request->validate($configs, [], $names);

        $userId = auth()->user()->id;

        if (!($task->user_id == $userId)) {
            return view('acess-denied');
        }

        $task->update($request->all());
        return redirect()->route('task.show', ['task' => $task->id]); 
    }

    public function destroy(Task $task)
    {
        $userId = auth()->user()->id;

        if (!($task->user_id == $userId)) {
            return view('acess-denied');
        }

        $task->delete();
        return redirect()->route('task.index');
    }

    public function export()
    {
        return Excel::download(new TasksExport, 'tasks.xlsx');
    }
}
