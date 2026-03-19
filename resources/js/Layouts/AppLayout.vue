<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import NavLink from '@/Components/NavLink.vue';

defineProps({
    title: String,
    isAdmin: Boolean,

});

const showingNavigationDropdown = ref(false);


const logout = () => {
    router.post(route('logout'));
};


</script>

<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-white">
            <nav class="bg-zinc-200 ">
                <!-- Primary Navigation Menu -->
                <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 nav-content">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('activities.index')">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div v-if="$page.props.auth.user.is_admin" class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <a class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-md font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out" href="/user">Utenti</a>

                            </div>
                            <div  class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <a class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-md font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out" href="/coupon">Coupon</a>

                            </div>

                            <div v-if="$page.props.auth.user.is_admin" class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <a class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-md font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out" href="/activities">Prodotti</a>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('reservations.index')" :active="route().current('reservations.*')">
                                    Vouchers
                                </NavLink>
                            </div>

                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
