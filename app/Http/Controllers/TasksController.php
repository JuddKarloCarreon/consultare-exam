<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Task;
use App\Events\updateTasks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Broadcast;

class TasksController extends Controller
{
    public function index()
    {
        return Inertia::render('Tasks/Index');
    }
    // Handles creation of tasks
    public function create(Request $request)
    {
        // Validate data
        $this->validate($request);

        $tasks = ['title' => $request->title, 'description'=> $request->description];

        // Create new task
        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        // Update all clients
        $this->updateClients();
    }
    // Handles obtaining all tasks from the database
    public function read($status = 'All')
    {
        $tasks = DB::table('tasks')
            ->select('id', 'title', 'description', 'status')
            ->orderBy('id');

        if ($status === 'Completed') {
            $tasks->where('status', true);
        } else if ($status === 'Pending') {
            $tasks->where('status', false);
        } else if ($status !== 'All') {
            return response()->json([
                'message' => 'Invalid URL'
            ], 422);
        }
        
        return $tasks->get();
    }
    // Handles the update of the status
    public function updateStatus(Request $request, $id)
    {
        // Validate data
        $request->validate([
            'status' => 'required|boolean',
        ]);
        
        DB::table('tasks')
            ->where('id', $id)
            ->update(['status' => $request->status]);
        
        $this->updateClients();
    }
    // Handles the update of the task aside from the status
    public function update(Request $request, $id)
    {
        // Validate data
        $this->validate($request);

        DB::table('tasks')
        ->where('id', $id)
        ->update([
            'title' => $request->title,
            'description' => $request->description
        ]);
    
        $this->updateClients();
    }
    // Handles the deletion of task
    public function delete(Request $request, $id)
    {
        $delId = DB::table('tasks')
        ->where('id', $id)
        ->delete();
        
        $this->updateClients();
    }

    // Handles updating the clients via websockets for live data
    private function updateClients()
    {
        $tasks = $this->read();

        // Use this if prefer to use Events
        broadcast(new updateTasks($tasks));
        
        // Use this if you don't like using events
        // Broadcast::on('update-tasks')
        //     ->as('update')
        //     ->with($tasks)
        //     ->sendNow();
    }
    // Handles data validation
    private function validate(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
        ]);
    }
}
