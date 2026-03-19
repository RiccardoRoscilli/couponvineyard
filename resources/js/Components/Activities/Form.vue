<script>
import axios from 'axios';

    export default {
        name: 'ActivitiesForm',
        computed: {
            uniqueLocations() {
                return this.locations.filter((location, index, self) =>
                    index === self.findIndex((l) => l.id === location.id)
                );
            },
        },
        data () {
            return {
                activitiesiPratico: '',
            }
        },
        props: {
            form: {
                type: Object,
                required: true
            },
            updating: {
                type: Boolean,
                required: false,
                default: false
            },
            typeButton: {
                type: String,
                default: 'submit',
            },
            message: String,
            locations: {
                type: Array,
                required: true
            },
            productCategories: {
                required: false
            },
        },
        methods: {
            productsInCategory()
            {
                if (this.form.category_ipratico_id) {
                    axios.get('/get-products-ipratico', { category_id: this.form.category_ipratico_id })
                    .then(response => {
                        this.activitiesiPratico = response.data;
                    })
                    .catch(error => {
                        console.error('Error fetching products', error);
                    });
                }
            },
        },
        emits: ['submitted'],
        created() {
            this.productsInCategory();
        }
    }
</script>
<template>
    <form @submit.prevent="$emit('submitted')">
        <div class="space-y-12">
            <div class="pb-12">
                <div class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6">
                    <!--Name Field-->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200" :class="$page.props.errors.name ? 'ring-red-300' : 'ring-gray-300'">
                            <input type="text" v-model="form.name" id="name" name ="name" autocomplete="name" class="pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="Nome Prodotto">
                        </div>
                        <div>
                            <p class="text-sm text-red-600">
                                {{ $page.props.errors.name }}
                            </p>
                        </div>
                    </div>
                   <!-- SKU Field -->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200">
                            <input type="text" v-model="form.sku" id="sku" name="sku" autocomplete="sku" class="pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="SKU">
                            <div>
                                <p class="text-sm text-red-600">
                                    {{ $page.props.errors.sku }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- iPratico Category and Product-->
                    <div class="sm:col-span-6">
                        <select id="category_ipratico_id" name="category_ipratico_id" @change="productsInCategory()" v-model=form.category_ipratico_id class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value='' disabled>Categorie iPratico</option>
                            <option v-for="category in productCategories" :value=category.id>{{ category.value.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-6">
                        <select id="activity_ipratico_id" name="activity_ipratico_id" v-model=form.activity_ipratico_id class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value='' disabled>Associazione prodotto iPratico</option>
                            <option v-for="product in activitiesiPratico" :value=product.id>{{ product.value.name }}</option>
                        </select>
                    </div>
                    <!--Location Field-->
                    <div class="sm:col-span-6">
                        <div class="mt-2 grid grid-cols-4 flex p-3 text-black-800 rounded-md h-36 shadow-sm ring-1 ring-insetfocus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200" :class="$page.props.errors.location_id ? 'ring-red-300' : 'ring-gray-300'">
                            <p class="col-span-1 text-gray-500 col-span-4 mb-2">Location</p>
                            <div class="col-span-1" v-for="location in uniqueLocations">
                                <div class="me-4 min-h-14 py-2">
                                    <input id="location_id" autocomplete="location_id" v-model="form.location_id" name="location_id" type="radio" :value="location.id" class="w-6 h-6 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                    <label for="location_id" class="ms-2 text-md font-medium text-gray-900">{{ location.name }}</label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-red-600">
                                {{ $page.props.errors.location_id }}
                            </p>
                        </div>
                    </div>
                    <!-- Value Field -->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200">
                            <input type="number" v-model="form.product_value" id="product_value" name="product_value" autocomplete="product_value" class="pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="Valore in €">
                            <div>
                                <p class="text-sm text-red-600">
                                    {{ $page.props.errors.product_value }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Description Field -->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200">
                            <textarea rows="2" v-model="form.description" id="description" name="description" autocomplete="description" class="h-24 pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="Descrizione"></textarea>
                            <div>
                                <p class="text-sm text-red-600">
                                    {{ $page.props.errors.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Details Field -->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200">
                            <textarea rows="2" v-model="form.details" id="details" name="details" autocomplete="details" class="h-24 pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="Dettagli"></textarea>
                            <div>
                                <p class="text-sm text-red-600">
                                    {{ $page.props.errors.details }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Note Field -->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200">
                            <textarea rows="2" v-model="form.note" id="note" name="note" autocomplete="note" class="h-24 pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="Note"></textarea>
                            <div>
                                <p class="text-sm text-red-600">
                                    {{ $page.props.errors.note }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Come Prenotare Field -->
                    <div class="sm:col-span-6">
                        <div class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200">
                            <textarea rows="2" v-model="form.prenotare" id="prenotare" name="prenotare" autocomplete="prenotare" class="h-24 pl-3 block flex-1 border-0 bg-transparent py-1.5 pl-1 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6" placeholder="Come prenotare"></textarea>
                            <div>
                                <p class="text-sm text-red-600">
                                    {{ $page.props.errors.prenotare }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Control Buttons -->
            <div class="">
                <a :href="route('activities.index')"><button type="button" class="display: inline-block rounded-md w-72 bg-gray-400 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Annulla</button></a>
                <button :type="typeButton" class="float-right bg-green-600 rounded-md w-72 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Salva</button>
            </div>
        </div>
    </form>
</template>
