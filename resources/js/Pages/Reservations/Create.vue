<script>
import { InformationCircleIcon } from "@heroicons/vue/24/solid";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        AppLayout,
        InformationCircleIcon,
    },
    props: ["activity"],
    data() {
        return {
            isLoading: false,
            step: 1,
            warning: false,
            showPopup: false,
            errors: {},
            invoice: {
            invoice_line: '1', // Imposta il valore predefinito di invoice_line
        },
            client: {
                clientType: "Azienda",
            },
        };
    },

    computed: {
        maxDate() {
            return new Date().toISOString().split("T")[0];
        },
        placeholderType() {
            return this.client.clientType == "Azienda"
                ? "Numero di P.iva"
                : "Codice Fiscale";
        },
    },
    methods: {
        stepIsCompleted() {
            // check if the step is completed
            if (this.step == 1) {
                // just check if the invoice_id, invoice_date and invoice_line are filled

                return (
                    this.invoice.invoice_id &&
                    this.invoice.invoice_date &&
                    this.invoice.invoice_line
                );
            } else if (this.step == 2) {
                // check if vat_number is adequate
                if (this.client.clientType && this.client.vat_number) {
                    // Select the correct regex
                    let fiscalCodeRegex = null;
                    if (this.client.clientType == "Azienda") {
                        fiscalCodeRegex = /^\d{11}$/;
                    } else {
                        /*  fiscalCodeRegex =
                            /^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$/i; */
                        console.log("clientType:", this.client.clientType);
                        console.log("vat_number:", this.client.vat_number);

                        fiscalCodeRegex =
                            /^((?:[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z])|(?:9{11}))$/i;
                    }

                    // Check if the vat_number is correct
                    if (fiscalCodeRegex.test(this.client.vat_number)) {
                        return true;
                    }
                }
            } else if (this.step == 3) {
                // Check if email is correct
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.client.email)) {
                    return false;
                }

                if (this.client.clientType == "Azienda") {
                    return (
                        this.client.companyName &&
                        this.client.vat_number &&
                        this.client.invoicingEndpoint &&
                        this.client.street &&
                        this.client.city &&
                        this.client.province &&
                        this.client.zipCode &&
                        this.client.nation &&
                        this.client.phone_number &&
                        this.client.email
                    );
                } else {
                    return (
                        this.client.name &&
                        this.client.surname &&
                        this.client.vat_number &&
                        this.client.street &&
                        this.client.city &&
                        this.client.province &&
                        this.client.zipCode &&
                        this.client.nation &&
                        this.client.phone_number &&
                        this.client.email
                    );
                }
            } else if (this.step == 4) {
                return (
                    this.activity.product_value &&
                    this.activity.expiration_date &&
                    this.client.emailName &&
                    this.client.emailSurname
                );
            }
            return false;
        },

        async nextStep() {
            this.isLoading = true;

            await new Promise((resolve) => setTimeout(resolve, 1000));

            if (this.step == 1) {
                // Go to the next step
                this.isLoading = false;
                this.step++;
            } else if (this.step == 2) {
                // Check if the vat_number already Exists, go to the next step
                axios
                    .post("/checkBusinessActor", { client: this.client })
                    .then((response) => {
                        this.client = response.data.client;
                        this.step++;
                    })
                    .catch((error) => {
                        alert(
                            "Errore nel recupero del cliente, controllare anagrafica"
                        );
                        console.error("Error checking client", error);
                    })
                    .finally(() => {
                        // Disabilita lo spinner e abilita di nuovo il pulsante
                        this.isLoading = false;
                    });
            } else if (this.step == 3) {
                // Go to the next step
                this.isLoading = false;
                this.step++;
            } else if (this.step == 4) {
                // Create the Reservation and go to the next step
                await axios
                    .post("/reservations/store", {
                        client: this.client,
                        activity: this.activity,
                        invoice: this.invoice,
                    })
                    .then((response) => {
                        if (response.data.error) {
                            window.location.href = "/error-coupon";
                        } else {
                            router.visit("/reservations/showQR", {
                                method: "get",
                                data: {
                                    client: this.client,
                                    activity: this.activity,
                                    coupon_code: response.data.coupon_code,
                                },
                            });
                        }
                    })
                    .catch((error) => {
                        alert(
                            "Errore nella creazione del coupon, controllare numero fattura o voucher su ipratico"
                        );
                        console.error("Error creating reservation", error);
                    })
                    .finally(() => {
                        // Disabilita lo spinner e abilita di nuovo il pulsante
                        this.isLoading = false;
                    });
            }
        },

        previousStep() {
            this.step--;
        },

        togglePopup() {
            this.showPopup = !this.showPopup;
        },
    },
};
</script>

<template>
    <AppLayout>
        <div
            v-if="warning"
            id="warning"
            class="p-3 bg-white rounded-md mt-5 ml-28 w-11/12 border items-center justify-evenly border-l-8 border-l-action md:w-1/2 md:h-fit"
        >
            <span class="text-xs w-4/5" v-html="warning"></span>
        </div>

        <!-- Invoice Information -->
        <div class="py-6" v-if="step == 1">
            <div class="w-full 2xl:w-9/12 mx-auto">
                <div class="bg-white space-y-12 pb-12">
                    <div
                        class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6"
                    >
                        <!-- Invoice ID -->
                        <div class="sm:col-span-6">
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    id="invoice_id"
                                    autocomplete="invoice_id"
                                    maxlength="5"
                                    v-model="invoice.invoice_id"
                                    name="invoice_id"
                                    class="pl-3 block flex-1 border-0 bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="Numero Fattura"
                                />

                                <div v-if="errors.invoice_id">
                                    <p
                                        class="text-sm text-red-600"
                                        v-html="errors.invoice_id"
                                    ></p>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Date -->
                        <div class="sm:col-span-6">
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="date"
                                    id="invoice_date"
                                    :max="maxDate"
                                    autocomplete="invoice_date"
                                    v-model="invoice.invoice_date"
                                    name="invoice_date"
                                    class="pl-3 block flex-1 border-0 bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="Datta Fattura"
                                />
                                <div v-if="errors.invoice_date">
                                    <p
                                        class="text-sm text-red-600"
                                        v-html="errors.invoice_date"
                                    ></p>
                                </div>
                            </div>
                        </div>
                        <!-- Invoice Line -->
                        <div class="sm:col-span-6">
                            Linea Fattura
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    id="invoice_line"
                                    autocomplete="invoice_line"
                                    v-model="invoice.invoice_line"
                                    name="invoice_line"
                                    class="pl-3 block flex-1 border-0 bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                />
                                <div v-if="errors.invoice_line">
                                    <p
                                        class="text-sm text-red-600"
                                        v-html="errors.invoice_line"
                                    ></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fiscal Code Check -->
        <div class="py-6" v-if="step == 2">
            <div class="w-full 2xl:w-9/12 mx-auto">
                <div class="bg-white space-y-12 pb-12">
                    <div
                        class="grid grid-cols-6 gap-x-6 gap-y-6 sm:grid-cols-6"
                    >
                        <!-- Persona/Azienda check -->
                        <div
                            class="col-span-6 sm:col-span-6 inline-flex place-content-center"
                        >
                            <div class="mr-8 mt-16 mb-12">
                                <input
                                    id="azienda"
                                    type="radio"
                                    value="Azienda"
                                    v-model="client.clientType"
                                    name="typeClient"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                />
                                <label
                                    for="azienda"
                                    class="ms-2 font-lg font-medium text-gray-900"
                                    >Azienda</label
                                >
                            </div>
                            <div class="ml-8 mt-16 mb-12">
                                <input
                                    id="persona"
                                    type="radio"
                                    value="Persona Fisica"
                                    v-model="client.clientType"
                                    name="typeClient"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                />
                                <label
                                    for="persona"
                                    class="ms-2 font-lg font-medium text-gray-900"
                                    >Persona Fisica</label
                                >
                            </div>
                        </div>

                        <!-- Client VAT number -->
                        <div class="col-span-6 sm:col-span-6">
                            <label
                                for="vat_number"
                                class="block text-sm font-medium text-gray-700"
                                >PI / Codice Fiscale (inserisci 11 volte 9 per
                                gli stranieri)</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    id="vat_number"
                                    autocomplete="vat_number"
                                    name="vat_number"
                                    v-model="client.vat_number"
                                    class="pl-3 block flex-1 border-0 bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    :placeholder="placeholderType"
                                />
                                <!-- Email Field -->
                                <div
                                    class="col-span-6 sm:col-span-6"
                                    v-if="
                                        client.clientType ===
                                            'Persona Fisica' &&
                                        client.vat_number === '99999999999'
                                    "
                                ></div>

                                <div class="w-1/5 inline-flex">
                                    <svg
                                        @click="togglePopup"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 16 16"
                                        fill="currentColor"
                                        class="w-4 h-4 ml-52 mt-2"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6.75 8a.75.75 0 0 0 0 1.5h.75v1.75a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 8.25 8h-1.5Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>

                                <!-- Grey box information -->
                                <div
                                    v-show="showPopup"
                                    class="w-2/5 absolute bg-gray-100 border p-4 rounded shadow-md inline-flex"
                                >
                                    <p
                                        class="text-sm text-gray-700 w-3/4"
                                        v-if="client.clientType == 'Azienda'"
                                    >
                                        Inserire la partiva iva, campo di 11
                                        caratteri numerici. Nel caso di
                                        anagrafiche straniere immettere la
                                        partita iva (VAT) indicando anche le
                                        eventuali lettere e non considerando il
                                        limite degli 11 caratteri.
                                    </p>
                                    <p
                                        class="text-sm text-gray-700 w-3/4"
                                        v-else
                                    >
                                        Inserire il codice fiscale che per le
                                        persone giuridiche è un campo di 11
                                        caratteri numerici, mentre per le
                                        persone fisiche prevede 16 caratteri
                                        alfanumerici. Nel caso di anagrafiche
                                        straniere (sia persone fisiche che
                                        giuridiche , immettere 11 volte 9).
                                    </p>
                                    <svg
                                        @click="togglePopup"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 16 16"
                                        fill="currentColor"
                                        class="w-4 h-4 ml-28"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm2.78-4.22a.75.75 0 0 1-1.06 0L8 9.06l-1.72 1.72a.75.75 0 1 1-1.06-1.06L6.94 8 5.22 6.28a.75.75 0 0 1 1.06-1.06L8 6.94l1.72-1.72a.75.75 0 1 1 1.06 1.06L9.06 8l1.72 1.72a.75.75 0 0 1 0 1.06Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div
                            class="col-span-6 sm:col-span-6"
                            v-show="client.vat_number === '9'.repeat(11)"
                        >
                            <label
                                for="email"
                                class="block text-sm font-medium text-gray-700"
                                >Email</label
                            >
                            <input
                                id="email"
                                type="email"
                                v-model="client.email"
                                autocomplete="email"
                                class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Data -->
        <div v-if="step == 3">
            <div
                class="w-full 2xl:w-9/12 mx-auto bg-white space-y-6 pb-12 grid grid-cols-8 gap-x-6 sm:grid-cols-8"
            >
                <div class="col-span-6 sm:col-span-6 pt-4">
                    {{
                        client.clientType == "Azienda"
                            ? "Azienda"
                            : "Persona Fisica"
                    }}
                </div>

                <div
                    class="clientTypeSwitcher sm:col-span-8"
                    v-if="client.clientType == 'Azienda'"
                >
                    <!-- Client Company Name -->
                    <div class="col-span-4 sm:col-span-4 mt-2">
                        <label for="companyName">Ragione Sociale</label>
                        <input
                            type="text"
                            id="companyName"
                            autocomplete="companyName"
                            v-model="client.companyName"
                            class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            placeholder="Ragione Sociale*"
                        />
                    </div>
                </div>

                <div
                    class="clientTypeSwitcher sm:col-span-8 grid grid-cols-8 gap-x-6 sm:grid-cols-8"
                    v-else
                >
                    <!-- Client name -->
                    <div class="col-span-4 sm:col-span-4 mt-2">
                        <label for="name">Nome</label>
                        <input
                            type="text"
                            id="name"
                            autocomplete="name"
                            v-model="client.name"
                            name="name"
                            class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            placeholder="Nome*"
                        />
                    </div>
                    <!-- Client surname -->
                    <div class="col-span-4 sm:col-span-4 mt-2">
                        <label for="surname">Cognome</label>
                        <input
                            type="text"
                            id="surname"
                            autocomplete="surname"
                            v-model="client.surname"
                            name="surname"
                            class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            placeholder="Cognome*"
                        />
                    </div>
                </div>

                <!-- Fiscal code / VAT number -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="vatnumber">Codice Fiscale</label>
                    <input
                        type="text"
                        id="vatnumber"
                        readonly="readonly"
                        v-model="client.vat_number"
                        name="vatnumber"
                        class="bg-gray-200 pl-3 block w-full py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Codice Fiscale*"
                    />
                </div>
                <!-- Codice Destinatario -->
                <div
                    v-if="client.clientType == 'Azienda'"
                    class="col-span-4 sm:col-span-4 mt-2 relative"
                >
                    <label for="target_code">Codice Destinatario</label>

                    <input
                        type="text"
                        id="target_code"
                        v-model="client.invoicingEndpoint"
                        name="target_code"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Codice Destinatario"
                    />
                    <div class="absolute right-3 top-7 cursor-pointer">
                        <svg
                            @click="togglePopup"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 16 16"
                            fill="currentColor"
                            class="w-4 h-4 ml-52 mt-2"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6.75 8a.75.75 0 0 0 0 1.5h.75v1.75a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 8.25 8h-1.5Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <!-- Grey box information -->
                    <div
                        v-show="showPopup"
                        class="w-full absolute bg-gray-100 border p-4 rounded shadow-md inline-flex"
                    >
                        <p class="text-sm text-gray-700 w-full">
                            Si tratta del Codice destinatario di - 7 caratteri
                            alfanumerici in caso di soggetto passivo Iva;- 6
                            caratteri alfanumerici in caso di Pubblica
                            Amministrazione. Nel caso di Pubblica
                            Amministrazione, il codice può essere ricercato da
                            qui
                            <a
                                class="text-blue-600"
                                href="https://www.indicepa.gov.it/ipa-portale/consultazione/domicilio-digitale/ricerca-domicili-digitali-ente"
                                target="_blank"
                                >https://www.indicepa.gov.it/ipa-portale/consultazione/domicilio-digitale/ricerca-domicili-digitali-ente</a
                            >
                            - Per le persone fisiche inserire sette zeri
                            0000000, nel caso di soggetti esteri inserire sette
                            ics XXXXXXX
                        </p>
                        <svg
                            @click="togglePopup"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 16 16"
                            fill="currentColor"
                            class="cursor-pointer absolute w-4 right-2"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm2.78-4.22a.75.75 0 0 1-1.06 0L8 9.06l-1.72 1.72a.75.75 0 1 1-1.06-1.06L6.94 8 5.22 6.28a.75.75 0 0 1 1.06-1.06L8 6.94l1.72-1.72a.75.75 0 1 1 1.06 1.06L9.06 8l1.72 1.72a.75.75 0 0 1 0 1.06Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>
                <!-- Client address street -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="address_street">Via</label>
                    <input
                        type="text"
                        id="address_street"
                        v-model="client.street"
                        name="address_street"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Indirizzo*"
                    />
                </div>
                <!-- Client city -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="city">Città</label>
                    <input
                        type="text"
                        id="city"
                        v-model="client.city"
                        name="city"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Città*"
                    />
                </div>
                <!-- Client province -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="province">Provincia</label>
                    <input
                        type="text"
                        id="province"
                        maxlength="2"
                        v-model="client.province"
                        name="province"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Provincia (sigla)*"
                    />
                </div>
                <!-- ZIP code -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="zip_code">CAP</label>
                    <input
                        type="text"
                        id="zip_code"
                        v-model="client.zipCode"
                        name="zip_code"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="ZIP*"
                    />
                </div>
                <!-- Client country -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="country_code">Nazione</label>
                    <input
                        type="text"
                        id="country_code"
                        maxlength="2"
                        v-model="client.nation"
                        name="country_code"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Nazione (sigla)*"
                    />
                </div>
                <!-- Client phone number -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="phone_number">Telefono</label>
                    <input
                        type="text"
                        id="phone_number"
                        v-model="client.phone_number"
                        name="phone_number"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Telefono*"
                    />
                </div>

                <!-- Client email -->
                <div class="col-span-4 sm:col-span-4 mt-2">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        v-model="client.email"
                        name="email"
                        autocomplete="email"
                        class="pl-3 block w-full bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6 rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                        placeholder="Email*"
                    />
                </div>
            </div>
        </div>

        <!-- Value Check -->
        <div class="py-6" v-if="step == 4">
            <div class="w-full 2xl:w-9/12 mx-auto">
                <div class="bg-white space-y-12 pb-12">
                    <div
                        class="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-6"
                    >
                        <!-- Company/Person name -->
                        <div
                            class="sm:col-span-6"
                            v-if="client.clientType == 'Azienda'"
                        >
                            <label for="name" class="ml-1 font-semibold text-sm"
                                >Ragione Sociale</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    readonly="readonly"
                                    id="name"
                                    v-model="client.companyName"
                                    name="name"
                                    class="bg-gray-200 rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                />
                            </div>
                        </div>

                        <div class="sm:col-span-6" v-else>
                            <label for="name" class="ml-1 font-semibold text-sm"
                                >Nome Persona Fisica</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    readonly="readonly"
                                    id="name"
                                    v-model="client.name"
                                    name="name"
                                    class="bg-gray-200 rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                />
                            </div>
                        </div>

                        <div
                            class="sm:col-span-6"
                            v-if="!client.clientType == 'Azienda'"
                        >
                            <label for="name" class="ml-1 font-semibold text-sm"
                                >Cognome Persona Fisica</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    readonly="readonly"
                                    id="name"
                                    v-model="client.surname"
                                    name="name"
                                    class="bg-gray-200 rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                />
                            </div>
                        </div>

                        <!-- iPratico Product -->
                        <div class="sm:col-span-6">
                            <label
                                for="activity_name"
                                class="ml-1 font-semibold text-sm"
                                >Prodotto vendibile</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    readonly="readonly"
                                    id="activity_name"
                                    v-model="activity.name"
                                    name="activity_name"
                                    class="bg-gray-200 rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="Prodotto vendibile"
                                />
                            </div>
                        </div>
                        <!-- Location name -->
                        <div class="sm:col-span-6">
                            <label
                                for="activity_name"
                                class="ml-1 font-semibold text-sm"
                                >Nome Location</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    readonly="readonly"
                                    id="activity_location"
                                    v-model="activity.location.name"
                                    name="activity_location"
                                    class="bg-gray-200 rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="Nome Location"
                                />
                            </div>
                        </div>
                        <!-- Value -->
                        <div class="sm:col-span-6">
                            <label
                                for="activity_name"
                                class="ml-1 font-semibold text-sm"
                                >Valore in €</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="number"
                                    id="activity_value"
                                    v-model="activity.product_value"
                                    name="activity_value"
                                    class="pl-3 block flex-1 border-0 bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="Valore in €"
                                />
                            </div>
                        </div>
                        <!-- Expiration Voucher Date -->
                        <div class="sm:col-span-6">
                            <label
                                for="exoiration_date"
                                class="ml-1 font-semibold text-sm"
                                >Validità entro</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-inset ring-gray-300 focus-within:ring-2 focus-within:ring-inset focus-within:ring-black-200"
                            >
                                <input
                                    type="date"
                                    id="expiration_date"
                                    v-model="activity.expiration_date"
                                    name="expiration_date"
                                    class="pl-3 block flex-1 border-0 bg-transparent py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    placeholder="Validità entro"
                                />
                            </div>
                        </div>
                        <!-- Nome per email -->
                        <div class="sm:col-span-6">
                            <label
                                for="activity_name"
                                class="ml-1 font-semibold text-sm"
                                >Nome da usare nella grafica Voucher</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-gray-300 focus-within:ring-2 focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    id="activity_location"
                                    v-model="client.emailName"
                                    name="activity_location"
                                    class="rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    :placeholder="client.name"
                                />
                            </div>
                        </div>
                        <!-- Location name -->
                        <div class="sm:col-span-6">
                            <label
                                for="activity_name"
                                class="ml-1 font-semibold text-sm"
                                >Cognome da usare nella grafica Voucher</label
                            >
                            <div
                                class="mt-2 flex rounded-md shadow-sm ring-1 ring-gray-300 focus-within:ring-2 focus-within:ring-black-200"
                            >
                                <input
                                    type="text"
                                    id="activity_location"
                                    v-model="client.emailSurname"
                                    name="activity_location"
                                    class="rounded-md pl-3 block flex-1 border-0 py-1.5 text-gray-900 placeholder:text-black-800 focus:ring-0 sm:text-sm sm:leading-6"
                                    :placeholder="client.surname"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 w-full 2xl:w-9/12 mx-auto">
            <div
                v-if="this.step > 1"
                @click="this.previousStep()"
                class="cursor-pointer float-left rounded-md w-72 bg-gray-400 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-400 text-center focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                Indietro
            </div>
            <div>
                <div
                    v-if="stepIsCompleted() && !isLoading"
                    @click="nextStep()"
                    class="cursor-pointer float-right rounded-md w-72 bg-green-600 w-xl px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-400 text-center focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    Avanti
                </div>
                <div v-else class="spinner" v-show="isLoading">
                    <!-- Aggiungi qui il codice per lo spinner -->
                    <div class="spinner-icon"></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
