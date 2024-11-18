<script setup>
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import { reactive, ref } from 'vue';
import Axios from 'axios';

const props = defineProps({
    task: {
        type: Object,
        required: true
    }
});
// Used to see if the task was changed after saving
const oldTask = reactive(JSON.parse(JSON.stringify(props.task)));
// Contains task data
const currTask = reactive(props.task);
// Indicator to know when the user clicked the edit icon to edit the task
const editing = ref(false);
// Tells the parent component that the data has been updated
const emit = defineEmits(['updateLoad']);

// Animates the description and edit icon to show/hide
function clickDesc(e) {
    const parent = $(e.target).closest('div.text-parent');
    const description = parent.find('pre.description');
    const edit = parent.find('.edit-button');
    description.toggle();
    edit.toggle(200);
}
// Handles editing the data
function clickEdit(e) {
    editing.value = !editing.value;
    const parent = $(e.target).closest('div.text-parent');
    const form = parent.find('form');
    const text = parent.find('div.text');
    if (editing.value) {
        // Show the input boxes when editing
        edit();
    } else {
        display();
        let equal = true;
        // Check if the data changed
        for (let key of Object.keys(oldTask)) {
            if (oldTask[key] !== currTask[key]) {
                equal = false;
                oldTask[key] = currTask[key];
            }
        }
        // Update the task if the data changed
        if (!equal) {
            emit('updateLoad');
            Axios.patch(`/task/${props.task.id}/update`, currTask)
                .catch(error => console.log(error));
        }
    }
    // Hides the taks display, and shows the input boxes for editing
    function edit() {
        text.hide();
        form.css('display', 'flex');
    }
    // Displays the data, and hides the input boxes
    function display() {
        form.hide();
        text.show();
    }
}
</script>
<template>
    <div class="flex text-parent">
        <div @click="clickDesc" class="grow text">
            {{ currTask.title }}
            <pre :class="['description hidden ps-2', {'text-slate-500': !currTask.description}]">{{ (currTask.description) ? currTask.description : 'No Description' }}</pre>
        </div>
        <form class="grow hidden flex-col ps-1 pe-3 gap-1" @submit.prevent="clickEdit">
            <TextInput v-model="currTask.title" />
            <TextArea v-model="currTask.description" class="resize-y" />
        </form>
        <div class="edit-button hidden me-2">
            <div class="grid content-center h-full">
                <div @click="clickEdit">
                    <svg :class="['size-6 cursor-pointer', {'hidden': editing}]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                    </svg>
                    <svg :class="['size-7 text-green-700 cursor-pointer relative bottom-[0.1rem]', {'hidden': !editing}]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
            </div>
        </div>
    </div>
</template>