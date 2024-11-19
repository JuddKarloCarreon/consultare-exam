<script setup>
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { reactive, ref, defineEmits, watch } from 'vue';
import Axios from 'axios';

const props = defineProps({
    task: {
        type: Object,
        required: true
    },
    deleted: {
        type: Boolean,
        required: true
    }
});
// Used to see if the task was changed after saving (only for edit)
const oldTask = reactive(JSON.parse(JSON.stringify(props.task)));
// Contains task data
const currTask = reactive(JSON.parse(JSON.stringify(props.task)));
// Container for error messages due to form submission
const formErrors = reactive({
    title: null,
    description: null
});
// Contains a general error message
const genError = ref();
const submitText = (props.task.hasOwnProperty('id')) ? 'Update' : 'Create';
// Indicator for when form has been submitted
const submitting = ref(false);
// Used to forcefully rerender tasks in case changes were made
const emit = defineEmits(['refresh']);

watch(() => props.deleted, (newVal) => {
    if (newVal) {
        genError.value = 'This task has been deleted by another user. It can no longer be edited.';
    }
});

// Handles form submission
function submitForm() {
    let equal = true;
    // Check if the data changed
    for (let key of Object.keys(oldTask)) {
        if (oldTask[key] !== currTask[key]) {
            equal = false;
            break;
        }
    }
    // Create or update the task if the data changed, and task hasn't been deleted by another user
    if (!equal && !props.deleted.value) {
        submitting.value = true;
        let submitMethod, submitUrl;

        if (props.task.hasOwnProperty('id')) {
            submitMethod = 'patch';
            submitUrl = `/task/${props.task.id}/update`;
        } else {
            submitMethod = 'post';
            submitUrl = '/task/create';
        }
        Axios({
            method: submitMethod,
            url: submitUrl,
            data: currTask
        }).then(() => {
            if (props.task.hasOwnProperty('id')) {
                // change old task values to current task values since current task is validated at this point
                for (let key of Object.keys(oldTask)) {
                    oldTask[key] = currTask[key];
                }
            }
            // Remove Errors
            Object.keys(formErrors).forEach((key) => {
                formErrors[key] = null;
            });
            emit('refresh');
        }).catch(error => {
            if (error.response) {                
                // Displays form Errors, and revert error values to old values
                Object.entries(error.response.data.errors).forEach(([key, value]) => {
                    formErrors[key] = value;
                    currTask[key] = oldTask[key];
                });
            } else {
                // Something happened in setting up the request that triggered an Error
                console.log('Error', error.message);
            }
        }).finally(() => {
            submitting.value = false;
        });
    } else {
        genError.value = 'No change detected.'
    }
}
</script>

<template>
    <div class="modal absolute top-0 left-0 m-auto h-screen w-screen flex justify-items-center" id="form-modal">
        <div class="absolute h-screen w-screen opacity-25 bg-slate-700 z-0"></div>
        <div class="z-10 min-w-[35rem] w-4/5 m-auto">
            <form class="relative grow flex flex-col gap-3 bg-white rounded-lg p-3" @submit.prevent="submitForm">
                <InputError :message="genError" />
                <div class="flex flex-col">
                    <InputLabel for="form-title" value="Title:" />
                    <TextInput v-model="currTask.title" id="form-title" autofocus />
                    <InputError v-for="error in formErrors.title" :message="error" />
                </div>
                <div class="flex flex-col">
                    <InputLabel for="form-description" value="Description:" />
                    <TextArea v-model="currTask.description" id="form-description" class="resize-y" />
                    <InputError v-for="error in formErrors.description" :message="error" />
                </div>
                <PrimaryButton type="submit" class="justify-center" :disabled="submitting" v-if="!props.deleted">{{ (submitting) ? "Submitting..." : submitText }}</PrimaryButton>
                <SecondaryButton class="justify-center" @click="$emit('refresh');">Cancel</SecondaryButton>
            </form>
        </div>
    </div>
</template>