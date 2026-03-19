<script>
import AppLayout from '@/Layouts/AppLayout.vue'

export default {
    components: {
        AppLayout
    },
    data() {
        return {
            errorMessage: 'Impossibile creare il coupon. I dati inseriti non sono validi.<br> Riprova.'
        }
    }
}

</script>
<template>
    <AppLayout>
        <div class="py-6 text-center max-w-10xl mx-auto sm:px-6 lg:px-8 font-semibold mb-15">
            <p class="ml-4 text-lg center" v-html="this.errorMessage"></p>
        </div>
    </AppLayout>
</template>