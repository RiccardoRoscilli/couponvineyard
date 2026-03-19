<script>
    export default {
        name: 'ActivitiesUpdate'
    }
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import ActivityForm from '@/Components/Activities/Form.vue'

const props = defineProps({
    activity: {
        type: Object,
        required:true
    },
    locations: {
        type: Array,
        required: true
    },
    product_categories: {
        type: Array
    },
})

const form = useForm({
    name: props.activity.name,
    sku: props.activity.sku,
    category_ipratico_id: props.activity.ipratico_category_id,
    activity_ipratico_id: props.activity.ipratico_id,
    product_value: props.activity.product_value,
    type: props.activity.type,
    description: props.activity.description,
    details: props.activity.details,
    prenotare: props.activity.prenotare,
    location_id: props.activity.location_id,
    note: props.activity.note
})
</script>

<template>
    <AppLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white">
                    <ActivityForm :form="form" :updating="true" :locations=locations :productCategories=product_categories @submit.prevent="form.put(route('activities.update', activity.id))">

                    </ActivityForm>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
