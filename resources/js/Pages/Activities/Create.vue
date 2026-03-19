<script>
    export default {
        name: 'ActivitiesCreate',
    }
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'
import ActivityForm from '@/Components/Activities/Form.vue'

defineProps({
    locations: {
        type: Array,
        required: true
    },
    product_categories: {
        required: false
    }
});

const form = useForm({
    name: '',
    sku: '',  // Aggiunto SKU
    category_ipratico_id: '',
    activity_ipratico_id: '',
    product_value: '',
    description: '',
    details: '',
    prenotare: '',
    location_id: '',
    note: ''
})
</script>

<template>
    <AppLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white">
                    <ActivityForm :form="form" :locations=locations :productCategories=product_categories @submit.prevent="form.post(route('activities.store'))">

                    </ActivityForm>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
