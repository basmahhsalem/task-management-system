<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\RoleService;

class TaskController extends Controller
{
    public function AddTask(Request $request)
    {
        $userId = auth()->id();

        if (RoleService::isManager($userId)) {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'status' => 'required|string|in:pending,completed,cancelled',
                'assigned_to' => 'required|integer',
                'assigned_by' => 'required|integer',
                'due_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            try {
                $sql = "
                DECLARE @TaskID INT;
                EXEC stp_tasks_insert 
                @Title = :title, 
                @Description = :description, 
                @Status = :status,
                @AssignedTo = :assigned_to, 
                @AssignedBy = :assigned_by, 
                @DueDate = :due_date,
                @TaskID = @TaskID OUTPUT;";
                $result = DB::select($sql, [
                    'title' => $request->input('title'),
                    'description' => $request->input('description', null),
                    'status' => $request->input('status', 'pending'),
                    'assigned_to' => $request->input('assigned_to'),
                    'assigned_by' => $request->input('assigned_by'),
                    'due_date' => $request->input('due_date'),

                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Task added successfully',
                    'task_id' => $result,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'error' => 'Server Error: ' . $e->getMessage()
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only managers can add tasks',
            ], 403);
        }
    }
    public function LoadTasksWithFilter(Request $request)
    {
        $userId = auth()->id();
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|string|in:pending,completed,cancelled',
            'due_date_from' => 'sometimes|nullable|date',
            'due_date_to' => 'sometimes|nullable|date',
            'assigned_to' => 'sometimes|nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $status = $request->input('status');
        $dueDateFrom = $request->input('due_date_from');
        $dueDateTo = $request->input('due_date_to');
        $assignedTo = $request->input('assigned_to');
        if (RoleService::isEmployee($userId)) {
            $assignedTo = $userId;
        }
        try {
            $stp = "EXEC stp_tasks_loadAll 
                @Status = :status, 
                @DueDateFrom = :due_date_from, 
                @DueDateTo = :due_date_to, 
                @AssignedTo = :assigned_to";
            $tasks = DB::select($stp, [
                'status' => $status,
                'due_date_from' => $dueDateFrom,
                'due_date_to' => $dueDateTo,
                'assigned_to' => $assignedTo,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Tasks loaded successfully',
                'result' => $tasks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function AddTaskDependency(Request $request)
    {
        $userId = auth()->id();

        if (RoleService::isManager($userId)) {
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer',
                'depend_id' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            try {
                $sql = "
                DECLARE @TaskDepID INT;
                EXEC stp_taskdep_insert
                @TaskID = :task_id,
                @DependID = :depend_id,
                @TaskDepID = @TaskDepID OUTPUT;";
                $result = DB::select($sql, [
                    'task_id' => $request->input('task_id'),
                    'depend_id' => $request->input('depend_id'),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Task dependency added successfully',
                    'task_dep_id' => $result,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Server Error: ' . $e->getMessage()
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only managers can add task dependencies',
            ], 403);
        }
    }
    public function UpdateTask(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:pending,completed,cancelled',
            'assigned_to' => 'sometimes|integer',
            'due_date' => 'sometimes|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $userId = auth()->id();


            // Load task
            $task = DB::select('EXEC stp_tasks_loadById @TaskID = ?', [$id]);
            if (empty($task)) {
                return response()->json(['message' => 'Task not found'], 404);
            }
            $task = $task[0];

            // Get inputs
            $newStatus = $request->input('status');
            $title = $request->input('title');
            $description = $request->input('description');
            $dueDate = $request->input('due_date');
            $assignedTo = $request->input('assigned_to');

            if (!RoleService::isManager($userId)) {
                // Employee can only update status and only for their own tasks
                if ($task->assigned_to != $userId) {
                    return response()->json(['message' => 'Unauthorized: not your task'], 403);
                }

                if (!$newStatus) {
                    return response()->json(['message' => 'Only status update is allowed'], 403);
                }

                // If status is being set to completed, check dependencies
                if ($newStatus === 'completed') {
                    $dependencies = DB::select('EXEC stp_tasks_loadDependencies @TaskID = ?', [$id]);
                    foreach ($dependencies as $dep) {
                        if ($dep->status !== 'completed') {
                            return response()->json(['message' => 'Cannot complete task: dependency task [' . $dep->title . '] is not completed'], 400);
                        }
                    }
                }
                $stp = "EXEC stp_tasks_update 
                @TaskID = ?, 
                @Title = NULL, 
                @Description = NULL, 
                @Status = ?, 
                @DueDate = NULL, 
                @AssignedTo = NULL";
                // Update only status
                DB::statement($stp, [
                    $id,
                    $newStatus
                ]);

                return response()->json([ 
                    'success' => true,
                    'message' => 'Task status updated'
                ]);
            } else {
                // If manager updates status to completed, validate dependencies
                if ($newStatus === 'completed') {
                    $dependencies = DB::select('EXEC stp_tasks_loadDependencies @TaskID = ?', [$id]);
                    foreach ($dependencies as $dep) {
                        if ($dep->status !== 'completed') {
                            return response()->json(['message' => 'Cannot complete task: dependency task [' . $dep->title . '] is not completed'], 400);
                        }
                    }
                }
                $stp = "EXEC stp_tasks_update 
                                @TaskID = ?, 
                                @Title = ?, 
                                @Description = ?, 
                                @Status = ?, 
                                @DueDate = ?, 
                                @AssignedTo = ?'";
                // Update task with manager fields
                DB::statement($stp, [
                    $id,
                    $title,
                    $description,
                    $newStatus,
                    $dueDate,
                    $assignedTo
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Task updated successfully'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function LoadTaskDependencies(Request $request)
    {
        $taskId = $request->input('task_id');
        $stp = "EXEC stp_tasks_loadDependencies @TaskID = :task_id";
        $dependencies = DB::select($stp, [
            'task_id' => $taskId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Task dependencies loaded successfully',
            'result' => $dependencies,
        ]);
    }

    public function loadTaskWithDependencies($id)
    {
        // Load the main task by ID
        $task = DB::select('EXEC stp_tasks_loadById @TaskID = ?', [$id]);

        if (empty($task)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found',
            ], 404);
        }

        // Load dependencies of the task
        $dependencies = DB::select('EXEC stp_tasks_loadDependencies @TaskID = ?', [$id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Task and dependencies loaded successfully',
            'task' => $task[0],
            'dependencies' => $dependencies,
        ]);
    }
}
