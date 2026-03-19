<script>
    export default {
        name: 'ErrorAPI',
    }
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
</script>

<template>
    <AppLayout>
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 font-bold">
            The service is unavailable at this moment. Please try again later.
            </div>
        </div>
    </AppLayout>
</template>