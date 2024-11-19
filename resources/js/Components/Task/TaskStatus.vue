<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref } from 'vue';
import Axios from 'axios';

const props = defineProps({
    status: {
        type: Number,
        required: true
    },
    id: {
        type: Number,
        required: true
    }
});
// Tells the parent component that the data has been updated
const emit = defineEmits(['updateLoad']);

// Contains the status
const status = ref(props.status ? true : false);

// Handles the update of the status for when the checkbox is clicked
function updateStatus() {
    emit('updateLoad');
    Axios.patch(`/task/${props.id}/update-status`, { status: status.value })
        .catch(err => console.log(err));
}

</script>

<template @click="click">
    <Checkbox class="cursor-pointer" :checked="status" v-model="status" :id="'check-' + props.id" @change="updateStatus" />
    <InputLabel :value="status ? 'Completed' : 'Pending'" :for="'check-' + props.id" class="inline-block ms-2 top-px relative select-none cursor-pointer" />
</template>