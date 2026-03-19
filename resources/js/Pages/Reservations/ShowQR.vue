<script>
    import QrcodeVue from 'qrcode.vue'
    export default {
        name: 'showQR',
        props: ['coupon_code', 'activity', 'client'],
        components: {
            QrcodeVue
        },
        methods: {
            sendEmail() {
                axios.post('/send-email', {
                    coupon_code: this.coupon_code,
                    activity: this.activity,
                    client: this.client
                })
                    .then(response => {
                        window.location.href = '/reservations/successemail';
                    })
                    .catch(error => {
                        console.log(error);
                        alert('Errore nell\'invio dell\'email');
                    });
            }
        }

    }
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
</script>
<template>
    <AppLayout>
        <div class="py-6 text-center max-w-10xl mx-auto sm:px-6 lg:px-8 font-semibold mb-15">
            <p class="ml-4 text-lg center">Voucher generato con successo</p>
        </div>
        <div class="pt-6 text-center max-w-10xl mx-auto sm:px-6 lg:px-8 font-semibold">
            <p class="ml-4 text-lg center">{{ client.emailName }} {{ client.emailSurname }}</p>
        </div>
        <div class="py-3 text-center max-w-10xl mx-auto sm:px-6 lg:px-8 mb-4 font-semibold">
            <p class="ml-4 text-lg center">{{coupon_code}}</p>
        </div>
        <div class="justify-center py-6 flex mt-10 max-w-10xl sm:px-6 lg:px-8 font-semibold">
            <QrcodeVue class="text-center" :render-as="svg" :value="coupon_code" :size="200"/>
        </div>
        <div class="mt-6 py-6" >
            <a :href="route('reservations.index')"><button type="button" class="ml-14 display: inline-block rounded-md w-72 bg-gray-400 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Chiudi</button></a>
            <button type="submit" @click="sendEmail()" class="mr-14 float-right rounded-md w-72 bg-green-600 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Invia Email</button>
        </div>
    </AppLayout>
</template>