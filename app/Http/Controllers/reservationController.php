<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Models\Reservation;
use App\Http\Controllers\clientController;
use App\Mail\VoucherEmail;
use App\Models\Location;
use App\Services\IpraticoAPIService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\couponController;

class reservationController extends Controller
{

    // load the reservation view
    public function index()
    {


        //Call different view depending on data
        $user = auth()->user();
        $props['activities'] = Activity::with('location')->get();
        $props['locations'] = Location::all();

        if ($user->is_admin == 1) {
            $props['activities'] = Activity::with('location')->get();
            $props['locations'] = Location::all();
        } else {
            $props['activities'] = Activity::where('location_id', $user->location_id)->get();
            $props['locations'] = Location::all();
        }

        //  $props['locations'] = Location::findOrFail(2);
        //Test iPratico API is up
        $testiPratico = IpraticoAPIService::api('GET', 'business-actors');
        if ($testiPratico == 'error') {
            return inertia('Activities/ErrorAPI');
        }

        //Go to create voucher
        return inertia('Reservations/Index', $props);
    }

    public function create($id)
    {

        // get the activity data
        $props['activity'] = Activity::where('id', $id)->with('location')->first();
        $props['activity']['expiration_date'] = Carbon::now()->addMonths(6)->format('Y-m-d');

        return inertia('Reservations/Create', $props);
    }

    // create a new reservation
    public function store(Request $request)
    {

        // Get the client data
        $data = $request->all();
        $client = $request->client;
        $invoice = $request->invoice;
        $activity = $request->activity;


        $invoiceYear = substr($invoice['invoice_date'], 0, 4); // Prende i primi quattro caratteri, che rappresentano l'anno
        $lastThreeDigits = substr($invoiceYear, -3); // Prende le ultime tre cifre dell'anno

        // get or update the client from iPratico
        $clientController = new clientController;
        $client = $clientController->create($client);


        // create coupon code -> Location suffix + 5 digit invoice number + 3 digit invoice year
        // Location suffix
        $suffix = $activity['location']['suffix'];
        // 5 digit invoice number
        $invoiceNumber = str_pad($invoice['invoice_id'], 5, '0', STR_PAD_LEFT);

        // Controlla se esiste già una prenotazione con lo stesso numero di fattura
        $existingReservation = Reservation::where('n_fattura', $invoiceNumber)->first();
        if ($existingReservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation with this invoice number already exists',
            ], 400);
        }
        // 3 digit invoice year
        // $currentYear = substr(date('Y'), -3);
        $currentDate = Carbon::now()->toDateTimeString();

        $coupon_code = $suffix . $invoiceNumber . $lastThreeDigits;

        if ($client['clientType'] == 'Persona Fisica') {
            $client['companyName'] = '';
        } else {
            $client['name'] = '';
            $client['surname'] = '';
        }
        /*      if ($activity['location_id'] == 3)
            $dataScadenza = Carbon::now()->addMonths(12);
        else
        */
        //  $dataScadenza = Carbon::now()->addMonths(6);
        // Recupera le chiusure della location
        $location = Location::findOrFail($activity['location_id']);
        $closures = $location->closures;

        $startDate = Carbon::today();

        // Converti le chiusure in oggetti Carbon
        $closurePeriods = $closures->map(function ($closure) {
            return [
                'start_date' => Carbon::parse($closure->start_date),
                'end_date' => Carbon::parse($closure->end_date)
            ];
        });

        // Calcola la data di fine aggiungendo 6 mesi alla data di partenza
        $endDate = $startDate->copy()->addMonths(6);

        // Aggiungi i giorni di chiusura alla data finale
        foreach ($closurePeriods as $period) {
            // Se il periodo di chiusura è compreso tra la data di partenza e la data di fine
            if ($period['start_date']->between($startDate, $endDate) || $period['end_date']->between($startDate, $endDate)) {
                $closureDays = $period['end_date']->diffInDays($period['start_date']) + 1;
                $endDate->addDays($closureDays);
            }
        }


        $dataScadenza = $endDate;

        // create coupon on iPratico solo se api è = ipratico altrimenti slope
        if ($activity['api'] == 'slope') {
            $coupon = true;
        } else {
            $coupon = $this->createCoupon($client, $invoice, $activity, $coupon_code, $activity['ipratico_id']);
        }

        if (isset($coupon->error)) {
            // redirect to error page
            return $coupon;
        } else {
            // scriviamo i dati sul db
            $reservation = new Reservation();
            $reservation->location_id = $activity['location_id'];
            $reservation->client_id = 1;
            $reservation->ipratico_client_id = isset($client['id']) ? $client['id'] : '';
            $reservation->users_amount = 1;
            $reservation->nome_activity = $activity['name'];
            $reservation->description_activity = $activity['description'];
            $reservation->experience_label = $activity['description'];
            $reservation->details_activity = $activity['details'];
            $reservation->note_activity = $activity['note'];
            $reservation->prenotare_activity = $activity['prenotare'];
            $reservation->nome_activity = $activity['name'];
            $reservation->amount = $activity['product_value'];
            $reservation->status = 'In Attesa';
            $reservation->n_fattura = $invoiceNumber;
            $reservation->data_fattura = $currentDate;
            $reservation->invoice_date = $currentDate;
            $reservation->company_cliente = $client['companyName'];
            $reservation->nome_cliente = $client['name'];
            $reservation->cognome_cliente = $client['surname'];
            $reservation->email_cliente = $client['email'];
            $reservation->nome_beneficiario = $client['emailName'];
            $reservation->cognome_beneficiario = $client['emailSurname'];
            $reservation->coupon_code = $coupon_code;
            $reservation->data_scadenza = $dataScadenza;
            $reservation->ipratico_id = isset($coupon->id) ? $coupon->id : '';
            // Salva la nuova prenotazione nel database
            $reservation->save();

            $return['success'] = true;
            $return['coupon_code'] = $coupon_code;
            return $return;
        }
    }
    /*
{
    "client": {
        "fiscalCode": "Partita iva o codice fiscale",
        "sdi": "N92GLON",
        "surnameOrCompanyName": "Doe or Company",
        "name": "John",
        "email": "john.doe@example.com",
        "phone_number": "3282914852",
        "address": "via test 1",
        "city": "Milano",
        "province": "MI",
        "nation": "IT",
        "zipCode": "20100"
    },
    "beneficiary":{
        "name": "darling",
        "surname": "clementine"
    },
    "invoice": {
        "invoice_number": 12345,
        "invoice_date": "2024-07-08"
    },
   "location": {
       "location_id": 1
        },
    "activity": {
        "activity_id": "1",
        "activity_language": "IT"
    }
}
*/

    public function apiStore(Request $request)
    {
        try {
            Log::info('API ERP ricevuta - inizio elaborazione');

            Log::info('Richiesta API ricevuta da ERP:', ['request' => $request->all()]);
            $requestData = $request->all();
            if (empty($requestData))
                return response()->json([
                    'success' => false,
                    'message' => 'error in json body',
                ]);
            // Stampa i dati per il debug
            // Get the client data
            $data = $request->input('request');
            $requestData = $request->input('request');

            if (!is_array($requestData) || !isset($requestData['client'])) {
                Log::warning("Formato JSON non valido o campo 'client' mancante", ['request' => $requestData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON structure. Expected { "request": { "client": {...} } }',
                ], 400);
            }

            $data = $requestData;
            $client = $data['client'] ?? null;


            $client['vat_number'] = $client['fiscalCode'];
            $client['street'] = $client['address'];

            if (empty($client['email']) || !filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email cliente mancante o non valida.',
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            if (isset($client['nation']) && strtoupper($client['nation']) == 'IT') {
                if (preg_match('/^[A-Z0-9]{16}$/', $client['fiscalCode'])) {
                    // Codice Fiscale
                    $client['clientType'] = 'Persona Fisica';
                    $client['surname'] = $client['surnameOrCompanyName'];
                } elseif (preg_match('/^[0-9]{11}$/', $client['fiscalCode'])) {
                    // Partita IVA
                    $client['clientType'] = 'Azienda';
                    $client['companyName'] = $client['surnameOrCompanyName'];
                } else {
                    // ❌ Codice non valido → blocca richiesta API
                    Log::warning('Codice Fiscale o Partita IVA non valido: ' . $client['fiscalCode']);

                    return response()->json([
                        'success' => false,
                        'message' => 'Codice Fiscale o Partita IVA non valido: ' . $client['fiscalCode'] . '. Se il cliente è straniero inserire 99999999999 come codice fiscale',
                    ], 422, [], JSON_UNESCAPED_UNICODE);
                }
            } else {
                // Cliente estero → tipo azienda, nessun controllo
                $client['clientType'] = 'Azienda';
                $client['companyName'] = $client['surnameOrCompanyName'];
            }


            $places = $request->client;
            $beneficiary = $data['beneficiary'] ?? null;
            $invoice = $data['invoice'] ?? null;
            $invoice['invoice_id'] = $invoice['invoice_increment'];
            $invoice['invoice_line'] = 1; //default 1 se serve si inserisce nel json

            $activity_data = $data['activity'] ?? null;

            // Trova l'attività usando lo SKU
            $sku = $activity_data['activity_sku'];
            $activity = Activity::where('sku', $sku)->first();
            if (!$activity) {
                Log::warning("SKU non trovato: " . $sku);
                return response()->json([
                    'success' => false,
                    'message' => 'activity SKU not found',
                ]);
            }

            // Se la lingua richiesta è inglese, sovrascrivi i campi dell'attività
            if (($activity_data['activity_language'] ?? 'IT') === 'EN') {
                $activity->name = $activity->name_en ?? $activity->name;
                $activity->description = $activity->description_en ?? $activity->description;
                $activity->details = $activity->details_en ?? $activity->details;
                $activity->note = $activity->note_en ?? $activity->note;
                $activity->prenotare = $activity->prenotare_en ?? $activity->prenotare;
            } elseif (($activity_data['activity_language'] ?? 'IT') === 'FR') {
                $activity->name = $activity->name_fr ?? $activity->name;
                $activity->description = $activity->description_fr ?? $activity->description;
                $activity->details = $activity->details_fr ?? $activity->details;
                $activity->note = $activity->note_fr ?? $activity->note;
                $activity->prenotare = $activity->prenotare_fr ?? $activity->prenotare;
            }


            $invoiceYear = substr($invoice['invoice_date'], 0, 4);
            // $lastThreeDigits = substr($invoiceYear, -3);

            // get or update the client from iPratico
            $clientController = new ClientController;
            $client = $clientController->create($client);

            // create coupon code -> Location suffix + 5 digit invoice number + 3 digit invoice year
            $suffix = $activity['location']['suffix'];

            //   $invoiceNumber = str_pad($invoice['invoice_id'], 5, '0', STR_PAD_LEFT);
            // $invoiceNumber = str_replace('/', '', (string)($invoice['invoice_id'] ?? ''));
            $invoiceNumber = $invoice['invoice_id'];

            // Controlla se esiste già una prenotazione con lo stesso numero di fattura
            if (!app()->environment('debug')) {
                $existingReservation = Reservation::where('n_fattura', $invoiceNumber)->first();

                if ($existingReservation) {
                    Log::info("Fattura già presente: " . $invoiceNumber);
                    return response()->json([
                        'success' => false,
                        'message' => 'Reservation with this invoice number already exists',
                    ], 400);
                }
            }


            $coupon_code = $suffix . $invoice['invoice_number'];

            if ($client['clientType'] == 'Persona Fisica') {
                $client['companyName'] = '';
            } else {
                $client['companyName'] = $client['surnameOrCompanyName'];
                $client['name'] = '';
                $client['surname'] = '';
            }

            // $dataScadenza = Carbon::now()->addMonths(6);
            // Recupera le chiusure della location
            $location = Location::findOrFail($activity['location_id']);
            $closures = $location->closures;

            $currentDate = Carbon::parse($invoice['invoice_date']);
            $startDate = $currentDate;

            // Converti le chiusure in oggetti Carbon
            $closurePeriods = $closures->map(function ($closure) {
                return [
                    'start_date' => Carbon::parse($closure->start_date),
                    'end_date' => Carbon::parse($closure->end_date)
                ];
            });

            // Calcola la data di fine base aggiungendo 6 mesi alla data di partenza
            $baseEndDate = $startDate->copy()->addMonths(6);

            // Calcola i giorni di chiusura che cadono DENTRO il periodo di validità del coupon
            $totalClosureDays = 0;
            
            foreach ($closurePeriods as $period) {
                $closureStart = $period['start_date'];
                $closureEnd = $period['end_date'];
                
                // Se la chiusura termina prima della data fattura o inizia dopo la scadenza base, skip
                if ($closureEnd->lt($startDate) || $closureStart->gt($baseEndDate)) {
                    continue;
                }
                
                // Calcola l'inizio effettivo dell'intersezione (il più tardi tra data_fattura e closure_start)
                $intersectionStart = $closureStart->gte($startDate) ? $closureStart : $startDate;
                
                // Calcola la fine effettiva dell'intersezione (il più presto tra base_expiration e closure_end)
                $intersectionEnd = $closureEnd->lte($baseEndDate) ? $closureEnd : $baseEndDate;
                
                // Calcola i giorni di intersezione
                $closureDays = $intersectionStart->diffInDays($intersectionEnd);
                $totalClosureDays += $closureDays;
            }

            // Calcola la data di fine finale aggiungendo i giorni di chiusura rilevanti
            $endDate = $startDate->copy()->addMonths(6)->addDays($totalClosureDays);




            $formattedEndDate = $endDate->format('Y-m-d\TH:i:sP'); // Output: 2023-03-31T00:00:00+02:00
            $data_scadenza = $endDate->format('Y-m-d');

            // create coupon on iPratico or Slope
            if ($activity['api'] == 'slope') {
                $coupon = true;
            } else {
                if (app()->environment('debug')) {
                    Log::info('🛑 SALTO chiamata a iPratico in locale per coupon:', [
                        'client' => $client,
                        'invoice' => $invoice,
                        'activity' => $activity,
                        'coupon_code' => $coupon_code,
                        'data_scadenza' => $data_scadenza,
                    ]);
                    $coupon = (object) ['id' => null]; // oppure simula un oggetto id
                } else {
                    $coupon = $this->createCoupon(
                        $client,
                        $invoice,
                        $activity,
                        $coupon_code,
                        $activity['ipratico_id'],
                        $formattedEndDate
                    );
                }
                //     $coupon = $this->createCoupon($client, $invoice, $activity, $coupon_code, $activity['ipratico_id']);
            }

            if (isset($coupon->error)) {
                Log::error("Errore nella creazione del coupon: ", (array) $coupon->error);
                return response()->json([
                    'success' => false,
                    'message' => $coupon->error->message ?? 'Errore nella creazione del coupon',
                    'code' => $coupon->error->code ?? null,
                    'details' => $coupon->error ?? (array) $coupon, // tutto l'errore in JSON
                ], 400);
            } else {
                $reservation = new Reservation();
                $reservation->location_id = $activity['location_id'];
                $reservation->client_id = 1;
                $reservation->ipratico_client_id = isset($client['id']) ? $client['id'] : '';
                $reservation->users_amount = 1;
                $reservation->nome_activity = $activity['name'];
                $reservation->description_activity = $activity['description'];
                $reservation->experience_label = $activity['description'];
                $reservation->details_activity = $activity['details'];
                $reservation->note_activity = $activity['note'];
                $reservation->prenotare_activity = $activity['prenotare'];
                $reservation->nome_activity = $activity['name'];
                $reservation->amount = $activity['product_value'];
                $reservation->status = 'In Attesa';
                $reservation->n_fattura = $invoice['invoice_number'];
                $reservation->data_fattura = $currentDate;
                $reservation->invoice_increment = $invoiceNumber;
                $reservation->invoice_date = $currentDate;
                $reservation->company_cliente = $client['companyName'];
                $reservation->nome_cliente = $client['name'];
                $reservation->cognome_cliente = $client['surname'];
                $reservation->email_cliente = $client['email'];
                $reservation->nome_beneficiario = $beneficiary['name'];
                $reservation->cognome_beneficiario = $beneficiary['surname'];
                $reservation->note_beneficiario = $beneficiary['note'];
                $reservation->coupon_code = $coupon_code;
                $reservation->data_scadenza = $data_scadenza;
                $reservation->ipratico_id = isset($coupon->id) ? $coupon->id : '';

                // Salva la nuova prenotazione nel database
                try {
                    $reservation->save();
                    Log::info("Reservation salvata con successo: ID " . $reservation->id);
                } catch (\Exception $e) {
                    Log::error("Errore salvataggio Reservation: " . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'Errore salvataggio Reservation',
                        'error' => $e->getMessage()
                    ], 500);
                }

                //  crea pdf ed invia la mail

                // Crea un'istanza del controller Coupon per il pdf
                $couponController = new couponController();
                //  $request->reservation_id = $reservation->id;
                $requestData['reservation_id'] = $reservation->id;
                // Richiama il metodo regenerateVoucher e passa la request


                $requestData['client']['surname'] = $requestData['client']['surnameOrCompanyName'];
                $requestData['client']['emailName'] = $beneficiary['name'];
                $requestData['client']['emailSurname'] = $beneficiary['surname'];

                $requestData['activity']['id'] = $activity->id;
                $requestData['activity']['name'] = $activity->name;
                $requestData['activity']['expiration_date'] = $data_scadenza;
                $requestData['activity']['details'] = $activity->details;
                $requestData['activity']['note'] = $activity->note;
                $requestData['activity']['description'] = $activity->description;
                $requestData['activity']['prenotare'] = $activity->prenotare;
                //$requestData['activity'] = $activity;
                $requestData['coupon_code'] = $coupon_code;

                $modifiedRequest = new Request($requestData);

                //  $couponController->regenerateVoucher($modifiedRequest);


                // invia la mail all'acquirente
                $this->sendEmail($modifiedRequest);



                return response()->json([
                    'success' => true,
                    'coupon_code' => $coupon_code
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('❌ Errore grave nella API ERP', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Errore interno server'], 500);
        }

    }
    // Show QR
    public function showQR(Request $request)
    {

        $props['coupon_code'] = $request->coupon_code;
        $props['activity'] = $request->activity;
        $props['client'] = $request->client;

        return inertia('Reservations/ShowQR', $props);
    }

    public function sendEmail(Request $request)
    {

        $coupon_code = $request->coupon_code;


        $activity = $request->activity;

        $activityModel = Activity::find($activity['id']);
        $client = $request->client;
        $location = $activityModel->location;


        if (!isset($client['companyName']) || $client['companyName'] == '') {
            $dataEmail['name'] = $client['name'];
            $dataEmail['surname'] = $client['surname'];
        } else {
            $dataEmail['name'] = $client['companyName'];
            $dataEmail['surname'] = '';
        }
        $dataEmail['beneficiarioName'] = $client['emailName'];
        $dataEmail['beneficiarioSurname'] = $client['emailSurname'];
        $dataEmail['activity_name'] = $activity['name'];
        // blocco traduzioni 
        $sanitizeText = fn($text) => str_replace(["\\r\\n", "\\n", "\\r", "\r\n", "\r", "\n"], '<br>', substr(json_encode($text, JSON_UNESCAPED_UNICODE), 1, -1));

        $dataEmail['details'] = $activity['details'] ? $sanitizeText($activity['details']) : null;
        $dataEmail['note'] = $activity['note'] ? $sanitizeText($activity['note']) : null;
        $dataEmail['description'] = $activity['description'] ? $sanitizeText($activity['description']) : null;
        $dataEmail['prenotare'] = $activity['prenotare'] ? $sanitizeText($activity['prenotare']) : null;
        Log::info('Prenotare cleaned:', ['prenotare' => $dataEmail['prenotare']]);
        // 
        $dataEmail['coupon_code'] = $coupon_code;
        $dataEmail['expiration_date'] = $activity['expiration_date'];
        $dataEmail['title'] = "Il suo buono regalo per " . $location->name;
        $dataEmail['logo'] = $location->logo;
        $dataEmail['telefono'] = $location->telefono;
        $dataEmail['location_name'] = $location->name;
        $dataEmail['ccn'] = 'shop@antoninocannavacciuolo.it';
        //generate pdf
        $dataEmail['fileName'] = 'Voucher ' . $dataEmail['name'] . ' ' . $dataEmail['surname'] . ' - ' . $coupon_code . '.pdf';

        $language = strtoupper($activity['activity_language'] ?? 'IT'); // fallback a 'IT' se non c'è

        if ($language === 'EN') {
            $templateView = 'emails.pdf_en';
            $mailTemplate = 'emails.mail_en';
        } elseif ($language === 'FR') {
            $templateView = 'emails.pdf_fr';
            $mailTemplate = 'emails.mail_fr';
        } else { // Italiano di default
            $templateView = 'emails.pdf';
            $mailTemplate = 'emails.mail';
        }

        PDF::loadView($templateView, $dataEmail)
            ->setPaper('a4', 'portrait')
            ->save('vouchers/' . $dataEmail['fileName']);


        //  cambio le credenziali al volo

        config([
            'mail.mailers.smtp.username' => $location->utente_mail,
            'mail.mailers.smtp.password' => $location->password_mail,
            'mail.from.address' => $location->utente_mail,
            'mail.from.name' => $location->name,
            // altre configurazioni se necessario
        ]);


        //send email
        //   $mailTemplate = $request->input('activity.language') === 'EN' ? 'emails.mail_en' : 'emails.mail';
        Mail::to($client['email'])->send(new VoucherEmail($dataEmail, $mailTemplate));

        //   Mail::to($client['email'])->send(new VoucherEmail($dataEmail));
    }

    //load success view
    public function successEmail()
    {
        return inertia('Reservations/Success');
    }

    // Error page
    public function errorCoupon()
    {
        return inertia('Reservations/ErrorCoupon');
    }

    // create coupon on iPratico
    function createCoupon($client, $invoice, $activity, $coupon_code, $activity_sku = null, $endDate)
    {

        $ipratico_key = $activity['location']['ipratico_key'];
        // create voucher on ipratico
        $promo_body = array(
            "shared" => false,
            "isReusable" => 1,
            "isActive" => true,
            "isCumulable" => true,
            "constraints" => array(
                "applyOnFinalPrice" => true,
                "allowedBusinessActorIds" => array(
                    $client['id'] // business actor id
                ),
            ),
            "finalUnitaryPriceVariation" => array(
                "isPercentage" => "false",
                "variation" => $activity['product_value']
            ),
            "validity" => array(
                "endDate" => $endDate
            ),
            "code" => $coupon_code,
            "name" => $coupon_code,
            "note" => "creato da ERP",
            "preselectedBusinessActorId" => $client['id'], // id del business actor che su ipad si deve associare direttamente alla vendita
            "invoiceDetails" => array(
                "number" => $invoice['invoice_id'],
                "date" => $invoice['invoice_date'],
                "line" => $invoice['invoice_line']
            )
        );

        // if activity_sku is set, add it to the constraints
        if ($activity_sku) {
            $promo_body['constraints']['productsAppliedDiscount'] = array($activity_sku);
        }

        // Log the request
        Log::info('Request to create coupon on iPratico', [
            'endpoint' => 'promo-codes',
            'body' => $promo_body,
            'ipratico_key' => $ipratico_key,
        ]);


        $email = IpraticoAPIService::api('POST', 'promo-codes', $promo_body, $ipratico_key);

        return $email;
    }
}
