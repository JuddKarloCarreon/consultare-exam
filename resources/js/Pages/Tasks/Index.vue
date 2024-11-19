<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, onUnmounted, nextTick } from 'vue';
import TaskStatus from '@/Components/Task/TaskStatus.vue';
import TaskForm from '@/Components/Task/TaskForm.vue';
import Axios from 'axios';

// turn add task to modal

// Container the values to add tasks
const taskForm = ref();
// Indicator for when the a task currently being edited has been deleted by another user
const deleted = ref(false);
// Contains the filter value for the status
const filter = ref('All');
// Container for all the tasks
const tasks = ref([]);
// Used to change the key of the task to force refresh the data
const keyBase = ref(0);
// Handles display of refresh message in case of long loading times
let displayRefresh;

// Websocket for all users to have live data for whenever it's changed by any user.
Echo.channel('update-tasks')
    .listen('.update', async (data) => {
        // Get new tasks
        let newTasks = (typeof data === 'object') ? data.tasks : data;
        // Filter tasks
        if (filter.value === 'All') {
            tasks.value = newTasks;
        } else {
            let filterVal = (filter.value === 'Completed') ? true : false;
            let filteredTasks = [];
            newTasks.forEach((task) => {
                if (task.status == filterVal) {
                    filteredTasks.push(task);
                }
            });
            tasks.value = filteredTasks;
        }
        updateTasks();
        display();
    });

// Cycle through filter values
function cycleFilter() {
    filter.value = (filter.value === 'All') ? 'Pending' : (filter.value === 'Pending') ? 'Completed' : 'All';
    loading();
    getTasks();
}
// Handles deletion of data
function clickDelete(e, task) {
    let deleteTask = confirm(`Are you sure you want to delete:\n\nTitle: ${task.title}\n\nDescription: ${task.description}\n\n?`);
    if (deleteTask) {
        loading();
        Axios.delete(`/task/${task.id}`)
            .catch(error => console.log(error));
    }
}
// Obtain the tasks from the server
function getTasks() {
    Axios.get(`/task/read/${filter.value}`)
        .then(res => {
            tasks.value = res.data;
            display();
        })
        .catch(error => console.log(error));
}
// Animates the description and edit icon to show/hide
function clickDesc(e) {
    const parent = $(e.target).closest('div.text-parent');
    const description = parent.find('pre.description');
    const edit = parent.find('.edit-button');
    description.toggle();
    edit.toggle(200);
}
// Shows the loading screen while fetching the task data
function loading() {
    $('#loading').html('Loading...');
    $('#loading').show();
    $('#tasks-data').hide();
    // Display new message for long loading times after 5s
    displayRefresh = setTimeout(() => {
        $('#loading').html('Websocket broadcast not received. Please refresh page to reset connection.');
    }, 5000);
}
// Hides the loading screen, and displays the tasks
function display() {
    clearTimeout(displayRefresh);
    $('#loading').hide();
    $('#tasks-data').show();
}
async function updateTasks() {
    // This rerenders the tasks
    keyBase.value++;
    await nextTick();
    // Check if a deleted task is being edited
    if (taskForm.value && taskForm.value.id) {
        if ($(`#task-${taskForm.value.id}`).length < 1) {
            deleted.value = true;
        }
    }
}
function cancelForm() {
    updateTasks();
    taskForm.value = null;
    if (deleted.value) deleted.value = false;
}

// Get the tasks when the page first loads
onMounted(getTasks);
// Leave the websocket channel when leaving the page
onUnmounted(() => {
    Echo.leave('update-tasks');
});
</script>

<template>
    <Head title="Task Manager" />
    <main class="flex h-screen">
        <div class="m-auto min-w-[35rem] w-4/5">
            <p>Notes: Click on a task to show description. Click on status to filter, plus to add task, pen to edit, and trash to delete</p>
            <div class="flex flex-col border-solid border-2 border-slate-500 rounded-lg min-h-[30rem] max-h-80 p-1 overflow-y-auto overflow-x-hidden">
                <div id="loading">
                    Loading...
                </div>
                <table id="tasks-data" class="table-auto w-full hidden">
                    <thead>
                        <tr class="border-b border-slate-400">
                            <th class="w-0 cursor-pointer px-1 border-r border-slate-400">
                                <svg class="size-6 text-cyan-700" @click="() => taskForm = { title: '', description: '' }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </th>
                            <th>Task</th>
                            <th class="w-0 cursor-pointer whitespace-nowrap px-3" @click="cycleFilter">Status: {{ filter }}</th>
                            <th class="w-0">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="task in tasks" class="rounded-lg hover:bg-slate-300 px-2 border-b border-slate-400" :id="'task-' + task.id" :key="keyBase + '-' + task.id">
                            <td class="border-r border-slate-400">
                                <svg class="size-5 cursor-pointer mx-auto" @click="() => taskForm = task" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                            </td>
                            <td class="ps-1">
                                <div class="flex text-parent">
                                    <div @click="clickDesc" class="grow text">
                                        {{ task.title }}
                                        <pre :class="['description hidden ps-2', {'text-slate-500': !task.description}]">{{ (task.description) ? task.description : 'No Description' }}</pre>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap align-middle px-2 text-center">
                                <TaskStatus :status="task.status" :id="task.id" @update-load="loading" />
                            </td>
                            <td>
                                <svg class="size-6 text-red-700 cursor-pointer mx-auto" @click="() => clickDelete($event, task)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
                                </svg>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <TaskForm v-if="taskForm" :task="taskForm" :deleted="deleted" @refresh="cancelForm" />
    </main>
</template>
