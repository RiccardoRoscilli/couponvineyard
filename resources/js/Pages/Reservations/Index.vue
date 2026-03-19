<script>
    import AppLayout from '@/Layouts/AppLayout.vue'
    export default {
        props: ['activities', 'locations'],
        components: {
            AppLayout,
        },

        methods: {
            resetFilter() {
                this.locationID = null;
            },
            activitiesFiltered() {
                // Filter activities by location

                // for each location, if checked, add to array
                let locationIDs = this.locations.filter(location => location.checked).map(location => location.id);

                // if no location is checked, return all activities
                if (locationIDs.length === 0) {
                    return this.activities;
                }

                // filter activities by location
                return this.activities.filter(activity => locationIDs.includes(activity.location_id));
            },
            activityIsSelected() {
                // check if any activity is selected
                return this.activities.some(activity => activity.selected);
            },
            startCreateReservation() {

                // get selected activity
                let activity = this.activities.find(activity => activity.selected);

                // redirect to create reservation page
                window.location.href = `/reservations/${activity.id}/create`;

            }

        }
    }
</script>

<template>
    <AppLayout>

        <!-- Select Product -->
        <div class="max-w-10xl mx-auto sm:px-6 py-6 lg:px-8 font-semibold">

            <p class="ml-4 text-lg">Scegli prodotto</p>

            <div :class="{'grid-cols-4': $page.props.auth.user.is_admin, 'grid-cols-2': !$page.props.auth.user.is_admin}" class="grid">

                <!-- Filter -->
                <div  v-if="$page.props.auth.user.is_admin"  class="mt-8 lg:ml-2 sm:ml-1 bg-zinc-200 max-w-xs p-4 border-b border-gray-200 rounded-md min-h-72 col-span-1 h-fit">
                    <div class="w-xl  justify-between grid grid-cols-1 font-semibold text-xl">
                        Filtra Per Location
                        <div v-for="location in locations" class="w-full mt-4 py-3">
                            <input type="checkbox" :id="location.name" v-model="location.checked" class="w-6 h-6 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <label :for="location.name" class="ms-2 text-lg font-medium text-gray-900">{{ location.name }}</label>
                        </div>
                        <button type="button" @click="resetFilter" class="mt-4 float-left w-4 pt-4 hidden">Clear</button>
                    </div>
                </div>

                <!-- Products List -->
              <div class="mx-auto sm:mx-3 max-w-2xl px-8 py-8 sm:px-1 sm:py-8 lg:max-w-7xl lg:px-8 col-span-3 max-h-128 overflow-y-auto">

                    <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
                        <!-- Product -->
                        <div v-for="activity in this.activitiesFiltered()" class="h-full">

                            <input type="radio" :id="activity.id" name="activitySelectedId" :checked="activity.selected" @click="activity.selected = !activity.selected" :value="activity.id" class="hidden peer">

                            <label :for="activity.id" class="h-full block group relative border-solid border-2 peer-checked:border-black rounded-xl hover:text-black-900 cursor-pointer text-gray-400 peer-checked:text-black hover:bg-gray-50" >
                                <div class="text-center	aspect-h-1 font-bold aspect-w-1 w-full overflow-hidden rounded-md lg:aspect-none group-hover:opacity-75 p-6 text-lg">
                                    {{activity.name}}
                                                        <!-- Mostra il prezzo del voucher -->
                                                        <div class="text-center	aspect-h-1 font-bold aspect-w-1 w-full overflow-hidden rounded-md lg:aspect-none group-hover:opacity-75 p-6 text-lg">
                                   Prezzo:  {{activity.product_value.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' }) }}

                                    </div>

                                </div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>


        </div>

        <div class="mt-6">
            <div v-if="this.activityIsSelected()" @click="this.startCreateReservation()" class="cursor-pointer mr-14 float-right rounded-md w-72 bg-green-600 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-400 text-center focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Avanti</div>
        </div>




    </AppLayout>
</template>
