<script>
export default {
    name: "CategoriesIndex",
    data() {
        return {
            locationID: null,
            locationFilterSelected: null,
        };
    },
    computed: {
        uniqueLocations() {
            return this.locations.filter(
                (location, index, self) =>
                    index === self.findIndex((l) => l.id === location.id)
            );
        },
        activitiesFiltered() {
            if (this.locationID) {
                return this.activities.filter(
                    (activity) => activity.location_id === this.locationID
                );
            }

            return this.activities;
        },
    },
    methods: {
        resetFilter() {
            this.locationID = null;
        },
        checkFilterDeselect(filterLocationId) {
            this.locationFilterSelected =
                this.locationFilterSelected === filterLocationId
                    ? null
                    : filterLocationId;
            this.resetFilter();
        },
    },
};
</script>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    activities: {
        type: Array,
        required: true,
    },
    locations: {
        type: Array,
        required: true,
    },
    isAdmin: Boolean,
});
</script>
<!-- TODO: Responsive design -->
<template>
    <AppLayout>
        <div class="py-6">
            <div
                class="h-14 fixed w-72 lg:ml-10 ml-1 mt-14 bg-zinc-200 max-w-xs p-4 bg-white border-b border-gray-200 rounded-md col-span-1">
                <div class="w-xl flex justify-between">
                    <Link :href="route('activities.create')" class="w-full">
                    <p class="inline-block font-semibold text-center text-xl">
                        Aggiungi prodotto
                    </p>
                    <svg class="hidden lg:block float-right h-5 text-gray-800" aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 12h14m-7 7V5" />
                    </svg>
                    </Link>
                </div>
            </div>
            <div class="h-14 lg:ml-10 ml-1 mt-14 max-w-xs p-4rounded-md col-span-1"></div>
            <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-4">
                    <!-- Filter -->
                    <div
                        class="mt-8 lg:ml-2 sm:ml-1 max-w-xs p-4 bg-white border-gray-200 rounded-md min-h-72 col-span-1 h-fit">
                    </div>
                    <div
                        class="mt-8 fixed lg:ml-2 sm:ml-1 bg-zinc-200 max-w-xs p-4 bg-white border-b border-gray-200 rounded-md min-h-72 col-span-1 h-fit">
                        <div class="w-xl flex justify-between grid grid-cols-1 font-semibold text-xl">
                            Filtra Per Location
                            <div v-for="location in uniqueLocations" class="w-full mt-4 py-3">
                                <input type="radio" id="locationID" name="locationID" :checked="locationFilterSelected === location.id
                                    " @click="checkFilterDeselect(location.id)" v-model="locationID"
                                    :value="location.id" @change="activitiesFiltered()"
                                    class="w-6 h-6 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" />
                                <label for="locationID" class="ms-2 text-lg font-medium text-gray-900">{{ location.name
                                    }}</label>
                            </div>
                            <button type="button" @click="resetFilter" class="mt-4 float-left w-4 pt-4 hidden">
                                Clear
                            </button>
                        </div>
                    </div>
                    <!-- Products List -->
                    <div class="lg:mx-0 sm:mx-3 mt-0 md:-mt-28 px-8 py-8 sm:px-1 sm:py-8 lg:px-8 col-span-3">
                        <div class="mt-6 grid xl:grid-cols-4 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8">
                            <!-- Product -->
                            <div class="group relative border-solid border-2 border-black rounded-xl"
                                v-for="activity in activitiesFiltered">
                                <div
                                    class="text-center aspect-h-1 font-bold aspect-w-1 w-auto m-auto overflow-hidden rounded-md lg:aspect-none group-hover:opacity-75 p-8 text-xl">
                                    {{ activity.name }}
                                </div>
                                <!-- SKU -->
                                <div class="text-center p-4">
                                    <p class="text-lg text-gray-900 font-bold">
                                        SKU: {{ activity.sku }}
                                    </p>
                                </div>

                                <!-- Price -->
                                <div class="text-center p-4">
                                    <p class="text-lg text-gray-900 font-bold">
                                        Prezzo: €{{ activity.product_value }}
                                    </p>
                                </div>
                                <div class="flex justify-between p-6">
                                    <div>
                                        <h3 class="text-sm text-gray-700">
                                            <Link :href="route(
                                                'activities.edit',
                                                activity.id
                                            )
                                                ">
                                            <svg width="24" height="24" viewBox="0 0 24 24"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                            </svg>
                                            </Link>
                                        </h3>
                                    </div>
                                    <div>
                                        <h3 class="text-sm text-gray-700">
                                            <Link :href="route(
                                                'activities.duplicate',
                                                activity.id
                                            )
                                                ">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            </Link>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
