<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, onUnmounted, watch, computed, nextTick } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import TaskStatus from '@/Components/Task/TaskStatus.vue';
import TaskText from '@/Components/Task/TaskText.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Axios from 'axios';

// refresh and errors console log $errors filter tasks based on status

// Container the values to add tasks
const addForm = reactive({
    title: '',
    description: ''
});
// Container for error messages due to form submission
const formErrors = reactive({
    title: null,
    description: null
});
// Contains the filter value for the status
const filter = ref('All');
// Container for all the tasks
const tasks = ref([]);
// Handles display of refresh message in case of long loading times
let displayRefresh;

// Websocket for all users to have live data for whenever it's changed by any user.
Echo.channel('update-tasks')
    .listen('.update', async (data) => {
        // Clear old data set, let DOM rerender, then add new data set and display
        tasks.value = [];
        await nextTick();
        tasks.value = (typeof data === 'object') ? data.tasks : data;
        display();
    });

// Cycle through filter values
function cycleFilter() {
    filter.value = (filter.value === 'All') ? 'Pending' : (filter.value === 'Pending') ? 'Completed' : 'All';
    loading();
    getTasks();
}
// Add a new task to the database
function store() {
    loading();
    Axios.post('/task/create', addForm)
        .catch(error => {
            if (error.response) {                
                // Displays form Errors
                Object.entries(error.response.data.errors).forEach(([key, value]) => {
                    formErrors[key] = value;
                });
            } else {
                // Something happened in setting up the request that triggered an Error
                console.log('Error', error.message);
            }
            display();
        });
    addForm.title = '';
    addForm.description = '';
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
// Shows the loading screen while fetching the task data
function loading() {
    $('#loading').html('Loading...');
    $('#loading').show();
    $('#tasks-data').hide();
    // Clear errors
    Object.keys(formErrors).forEach((key) => {
        formErrors[key] = null;
    });
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
            <p>Notes: Click on a task to show description or edit. Click on status to filter</p>
            <div class="flex flex-col border-solid border-2 border-slate-500 rounded-lg min-h-[30rem] max-h-80 p-1 overflow-y-auto overflow-x-hidden">
                <form class="flex" @submit.prevent="store">
                    <InputLabel for="title" value="Add:" class="me-3 ms-1 mt-3 text-lg" />
                    <div class="flex flex-col gap-y-2 mb-2 flex-grow">
                        <TextInput  v-model="addForm.title" placeholder="Title" id="title" required />
                        <InputError v-for="error in formErrors.title" :message="error" />
                        <div v-if="addForm.title !== '' || formErrors.description != null" class="flex flex-col gap-y-2">
                            <TextArea v-model="addForm.description" placeholder="Description" />
                            <InputError v-for="error in formErrors.description" :message="error" />
                            <PrimaryButton type="submit" :disabled="addForm.processing">Submit</PrimaryButton>
                        </div>
                    </div>
                </form>
                <div id="loading">
                    Loading...
                </div>
                <table id="tasks-data" class="table-auto w-full hidden">
                    <thead>
                        <tr class="border-b border-slate-400">
                            <th>Task</th>
                            <th class="w-0 cursor-pointer whitespace-nowrap px-3" @click="cycleFilter">Status: {{ filter }}</th>
                            <th class="w-0">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="task in tasks" class="rounded-lg hover:bg-slate-300 px-2 border-b border-slate-400" :id="'task-' + task.id" :key="task.id">
                            <td>
                                <TaskText :task="task" @update-load="loading" />
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
    </main>
</template>
