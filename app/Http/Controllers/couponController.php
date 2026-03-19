<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Location;
use App\Models\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\IpraticoAPIService;
use LDAP\Result;
use DataTables;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Mail;
use App\Mail\VoucherEmail;
use Illuminate\Support\Facades\Log;

class CouponController extends Controller
{

    /**
     * Display a listing of the reservations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // $locations =  Location::whereNotNull('ipratico_key')->get();
        $locations = Location::all();
        $reservations = Reservation::all();
        return view('reservations.index', compact('reservations', 'locations'));
    }

    public function arriving()
    {
        $locations = Location::all();

        $reservations = Reservation::all();
        return view('reservations.arriving', compact('reservations', 'locations'));
    }
    public function used()
    {
        $locations = Location::all();

        $reservations = Reservation::all();
        return view('reservations.used', compact('reservations', 'locations'));
    }

    public function deleted()
    {

        $user = auth()->user();
        if ($user && $user->is_admin !== 1) {
            abort(403, 'Unauthorized action.');
        }




        $locations = Location::whereNotNull('ipratico_key')->get();

        $reservations = Reservation::all();
        return view('reservations.deleted', compact('reservations', 'locations'));
    }

    public function getReservationsWaiting(Request $request)
    {
        $status = 'In Attesa';
        // elenco delle colonne usate nella table
        $columns = array(
            0 => 'coupon_code',
            1 => 'nome_activity',
            2 => 'acquirente',
            3 => 'nome_cliente',
            4 => 'cognome_cliente',
            5 => 'beneficiario',
            6 => 'nome_beneficiario',
            7 => 'cognome_beneficiario',
            8 => 'data_fattura',
            9 => 'amount',
            10 => 'n_fattura',
            11 => 'data_scadenza',
            12 => 'location_id',
            13 => 'n_tavolo',
            14 => 'n_camera',
            15 => 'voucher',
        );
        echo $this->getReservationCommon($request, $status, $columns);
    }

    public function getReservationsArriving(Request $request)
    {
        $status = 'In Arrivo';
        $columns = array(
            0 => 'coupon_code',
            1 => 'nome_activity',
            2 => 'acquirente',
            3 => 'nome_cliente',
            4 => 'cognome_cliente',
            5 => 'beneficiario',
            6 => 'nome_beneficiario',
            7 => 'cognome_beneficiario',
            8 => 'databooking',
            9 => 'orabooking',
            10 => 'n_tavolo',
            11 => 'n_camera',
            12 => 'data_scadenza',
            13 => 'amount',
            14 => 'voucher',
            //   14 => 'n_camera',
        );
        echo $this->getReservationCommon($request, $status, $columns);
    }
    public function getReservationsUsed(Request $request)
    {
        $status = 'Usufruito';
        $columns = array(
            0 => 'coupon_code',
            1 => 'nome_activity',
            2 => 'acquirente',
            3 => 'nome_cliente',
            4 => 'cognome_cliente',
            5 => 'beneficiario',
            6 => 'nome_beneficiario',
            7 => 'cognome_beneficiario',
            8 => 'databooking',
            9 => 'orabooking',
            10 => 'data_scadenza',
            11 => 'amount',
            12 => 'voucher',
            //   14 => 'n_camera',
        );
        echo $this->getReservationCommon($request, $status, $columns);
    }

    public function getReservationsDeleted(Request $request)
    {
        $status = 'Cancellato';
        $columns = array(
            0 => 'coupon_code',
            1 => 'nome_activity',
            2 => 'acquirente',
            3 => 'nome_cliente',
            4 => 'cognome_cliente',
            5 => 'beneficiario',
            6 => 'nome_beneficiario',
            7 => 'cognome_beneficiario',
            8 => 'data_fattura',
            9 => 'amount',
            10 => 'n_fattura',
            11 => 'data_scadenza',
            12 => 'voucher',
            //   14 => 'n_camera',
        );
        echo $this->getReservationCommon($request, $status, $columns);
    }

    private function getReservationCommon($request, $status, $columns)
    {


        // processa tutte le variabili inviate get in ajax
        $limit = $request->input('length');
        $start = $request->input('start');

        $order = $columns[$request->input('order.0.column')];
        // intercetta gli ordinamenti per le colonne ricavate
        $dir = $request->input('order.0.dir');
        if ($order == 'beneficiario') {
            $order = 'cognome_beneficiario';
        }

        if ($order == 'acquirente') {
            $order = 'cognome_cliente';
        }

        $location_id = $request->input('location_id');
        $user = auth()->user(); // Ottieni l'utente autenticato

        if ($user->is_admin == 0) { // vede solo la sua location
            $location_id = $user->location_id;
        }

        if ($location_id == '') {
            $location_id = Location::pluck('id')->implode(',');
        }

        $totalData = $totalFiltered = Reservation::whereIn('location_id', explode(',', $location_id))
            ->Where('status', 'LIKE', "%{$status}%")
            ->count();

        $totalFiltered = $totalData;




        // controlla se ci sono  dei parametri di ricerca
        if (empty($request->input('search.value'))) {

            $tipo = $request->input('service_type');
            $query = Reservation::whereIn('location_id', explode(',', $location_id))
                ->Where('status', 'LIKE', "%{$status}%")
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);

            $reservations = $query->get();
            $totalFiltered = Reservation::whereIn('location_id', explode(',', $location_id))
                ->Where('status', 'LIKE', "%{$status}%")
                ->count();
            //  dd($sql);
        } else { // bisogna mettere i criteri per ogni campo su cui cercare
            $search = $request->input('search.value');

            $query = Reservation::whereIn('location_id', explode(',', $location_id))
                ->Where('status', 'LIKE', "%{$status}%")
                ->where(function ($query) use ($search) {
                    $query->where('coupon_code', 'LIKE', "%{$search}%")
                        ->orWhere('cognome_cliente', 'LIKE', "%{$search}%")
                        ->orWhere('cognome_beneficiario', 'LIKE', "%{$search}%")
                        ->orWhere('company_cliente', 'LIKE', "%{$search}%")
                        ->orWhere('cognome_beneficiario', 'LIKE', "%{$search}%")
                        ->orWhere('n_fattura', 'LIKE', "%{$search}%");
                    // Aggiungi altre colonne dove cercare, se necessario
                })
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);

            $sql = $query->toSql();
            // dd($sql);
            $bindings = $query->getBindings();
            $fullSql = Str::replaceArray('?', $bindings, $sql);
            // dd($fullSql);

            $reservations = $query->get();

            $totalFiltered = Reservation::where('location_id', $location_id)
                ->where('status', $status)
                ->where(function ($query) use ($search) {
                    $query->where('coupon_code', 'LIKE', "%{$search}%")
                        ->orWhere('cognome_cliente', 'LIKE', "%{$search}%")
                        ->orWhere('cognome_beneficiario', 'LIKE', "%{$search}%")
                        ->orWhere('company_cliente', 'LIKE', "%{$search}%")
                        ->orWhere('n_fattura', 'LIKE', "%{$search}%");
                    // Aggiungi altre colonne dove cercare, se necessario
                })
                ->count();
        }

        $data = array();
        if (!empty($reservations)) {
            foreach ($reservations as $reservation) {
                // $show =  route('admin.services.show', $post->id);
                //       $edit =  route('admin.services.edit', $reservation->id);
                //       $delete =  route('admin.services.delete', $reservation->id);

                $nestedData['id'] = $reservation->id;
                $nestedData['coupon_code'] = $reservation->coupon_code;
                $nestedData['cognome_cliente'] = $reservation->cognome_cliente;
                $nestedData['nome_cliente'] = $reservation->nome_cliente;
                $nestedData['nome_activity'] = $reservation->nome_activity;
                if ($reservation->company_cliente == '')
                    $acquirente = $reservation->nome_cliente . ' ' . $reservation->cognome_cliente;
                else
                    $acquirente = $reservation->company_cliente;
                // gestisce la modal con i dati da ipratico
                $acquirente = '<button type="button" class="btn btn-primary" data-action="getclient" >' . $acquirente . '</button>';

                //  $acquirente = '<button class="open-modal" data-id="' . $reservation->id .  '">' . $acquirente . '</button>';
                $nestedData['acquirente'] = $acquirente;

                $nestedData['beneficiario'] = $reservation->nome_beneficiario . ' ' . $reservation->cognome_beneficiario;
                $nestedData['cognome_beneficiario'] = $reservation->cognome_beneficiario;
                $nestedData['nome_beneficiario'] = $reservation->nome_beneficiario;
                //   $nestedData['user_email'] = $reservation->user->email;

                $nestedData['data_fattura'] = Carbon::parse($reservation->data_fattura)->format('d/m/Y');
                $nestedData['amount'] = $reservation->amount;
                $nestedData['n_fattura'] = $reservation->n_fattura;
                $nestedData['data_scadenza'] = Carbon::parse($reservation->data_scadenza)->format('d/m/Y');
                $nestedData['n_tavolo'] = $reservation->n_tavolo;
                $nestedData['n_camera'] = $reservation->n_camera;
                $nestedData['databooking'] = Carbon::parse($reservation->databooking)->format('d/m/Y');
                $nestedData['orabooking'] = Carbon::parse($reservation->orabooking)->format('H:i');
                /*  $nestedData['action'] = "<a href='{$edit}' class='edit btn btn-primary btn-sm'>Modifica</a></a>
                                             <a href='{$delete}' class='delete btn btn-danger btn-sm'  >Cancella</a>";
                    */
                $nestedData['ipratico_client_id'] = $reservation->ipratico_client_id;
                $nestedData['customer_email'] = $reservation->email_cliente;
                if (!isset($reservation->company_cliente) || $reservation->company_cliente == '') {
                    $dataEmail['name'] = $reservation->nome_cliente;
                    $dataEmail['surname'] = $reservation->cognome_cliente;
                } else {
                    $dataEmail['name'] = $reservation->company_cliente;
                    $dataEmail['surname'] = '';
                }

                $nestedData['filename'] = 'Voucher ' . $dataEmail['name'] . ' ' . $dataEmail['surname'] . ' - ' . $reservation->coupon_code . '.pdf';
                $voucherDir = public_path('vouchers/');

                // Nome del file del voucher
                $voucherFile = $voucherDir . $nestedData['filename'];
                if (file_exists($voucherFile)) {
                    $nestedData['voucher'] = '<a href="/vouchers/' . $nestedData['filename'] . '" target="_blank"><i class="fas fa-file-pdf  text-center">Voucher</i></a>';
                } else {
                    $nestedData['voucher'] = '<button type="button" class="btn btn-primary" data-action="regenerate" data-id="' . $reservation->id . '">Rigenera Coupon</button>
                    <div class="spinner-border text-primary" role="status" style="display: none;"><span class="sr-only">Loading...</span></div>';
                }
                $data[] = $nestedData;
            }
        }
        //  $data = Reservation::where('status', 'In Attesa')->latest()->get(); // Filtra le locations per l'utente autenticato

        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );

        return json_encode($json_data);
    }


    /**
     * Show the form for creating a new reservation.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('reservations.create');
    }

    /**
     * Store a newly created reservation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'field1' => 'required',
            'field2' => 'required',
            // Add more validation rules as needed
        ]);

        Reservation::create($request->all());

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation created successfully.');
    }

    /**
     * Display the specified reservation.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function show(Reservation $reservation)
    {
        return view('reservations.show', compact('reservation'));
    }

    /**
     * Show the form for editing the specified reservation.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function edit(Reservation $reservation)
    {

        return view('reservations.edit', compact('reservation'));
    }


    /**
     * Update the specified reservation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $reservation = Reservation::find($id);
        // Salva il valore originale del campo data_scadenza
        $originalDataScadenza = $reservation->data_scadenza;

        /** definizione della route di ritorno a seconda dello status */
        switch ($reservation->status) {
            case 'In Arrivo':
                $route = 'couponArriving';
                break;
            case 'In Attesa':
                $route = 'couponWaiting';
                break;
            case 'Usufruito':
                $route = 'couponUsed';
                break;
            case 'Cancellato':
                $route = 'couponDeleted';
                break;
        }


        switch ($request->input('action')) {
            case 'segna_usufruito':
                $reservation->update([
                    'status' => 'Usufruito',
                ]);
                break;
            case 'rimetti_attesa':
                $reservation->update([
                    'databooking' => null,
                    'orabooking' => null,
                    'status' => 'In Attesa',
                ]);
                break;
            case 'rimetti_arrivo':
                $reservation->update([
                    'status' => 'In Arrivo',
                ]);
                break;
            case 'salva':
                $messages = [
                    // 'email_beneficiario' => 'Il campo email deve essere una mail valida.',
                    // Altri messaggi personalizzati per altri campi del form
                ];
                $request->validate([
                    // 'email_beneficiario' => 'email',
                    // Add more validation rules as needed
                ], $messages);
                // Aggiorna i dati della configurazione

                $reservation->update([
                    'databooking' => $request->databooking,
                    'orabooking' => $request->orabooking,
                    'email_beneficiario' => $request->email_beneficiario,
                    'telefono_beneficiario' => $request->telefono_beneficiario,
                    'note_beneficiario' => $request->note_beneficiario,
                    'data_scadenza' => $request->data_scadenza,
                    'status' => 'In Arrivo',
                    'n_camera' => $request->n_camera,
                    'n_tavolo' => $request->n_tavolo,
                ]);
                break;
        }

        // Verifica se il campo data_scadenza è stato modificato
        //  if ($reservation->data_scadenza != $originalDataScadenza) {
        // modificato in false il 18 luglio 2025 perchè dicono che l'aggiornamento del coupon su ipratico 
        // cancella la fattura di riferimento
        if (true) { // aggiorno sempre la data di scadenza quando modificano il coupon
            // verifica se il coupon è di ipratico
            if ($reservation->ipratico_id !== '' && $reservation->ipratico_id !== null) {
                // Il campo ipratico_id non è nullo aggiorna la scadenza su ipratico
                // ottieni la chiave dalla location e aggiorna
                $ipratico_key = $reservation->location->ipratico_key;
                $endDate = Carbon::parse($reservation->data_scadenza);
                $formattedEndDate = $endDate->format('Y-m-d\TH:i:sP');

                $this->updateIpraticoCoupon($id, $reservation->ipratico_id, $reservation->coupon_code, $formattedEndDate, $ipratico_key);
            }
        }



        // Redirect alla pagina di visualizzazione della configurazione aggiornata
        /*   return redirect()->route('coupon.edit', $reservation->id)
            ->with('success', 'Coupon aggiornato con successo.');*/
        return redirect()->route($route)->with('success', 'Coupon aggiornato con successo.');
    }

    private function updateIpraticoCoupon($id, $ipratico_id, $coupon_code, $end_date, $ipratico_key)
    {
        $reservation = Reservation::find($id);

        $client = $reservation->ipratico_client_id;
        $importo = intval($reservation->amount);
        $data_fattura_formattata = Carbon::parse($reservation->data_fattura)->format('Y-m-d');
        // Recupera i dati della fattura dalla reservation
        $invoiceDetails = [
            "number" => $reservation->invoice_increment,
            "date" => $data_fattura_formattata,
            "line" => 1,
        ];

        // Corpo della richiesta per aggiornare il promo-code
        $promo_body = [
            "name" => $coupon_code,
            "code" => $coupon_code,
            "isReusable" => 1,
            "finalUnitaryPriceVariation" => [
                "variation" => $importo,
                "isPercentage" => "false",
                "roundValue" => 0
            ],
            "isActive" => true,
            "validity" => [
                "endDate" => $end_date
            ],
            "constraints" => [
                "allowedBusinessActorIds" => [
                    $client
                ]
            ],
            "preselectedBusinessActorId" => $reservation->ipratico_client_id, // id del business actor che su ipad si deve associare direttamente alla vendita
            "invoiceDetails" => $invoiceDetails, // <-- aggiunto qui
        ];
       
        // Chiamata PUT all'API iPratico
        $response = IpraticoAPIService::api('PUT', 'promo-codes/' . $ipratico_id, $promo_body, $ipratico_key);

        return $response;
    }


    /**
     * Remove the specified reservation from storage.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $reservation = Reservation::find($id);
        switch ($reservation->status) {
            case 'In Arrivo':
                $route = 'couponArriving';
                break;
            case 'In Attesa':
                $route = 'couponWaiting';
                break;
            case 'Usufruito':
                $route = 'couponUsed';
                break;
            case 'Cancellato':
                $route = 'couponDeleted';
                break;
        }
        // soft delete, status cancellato
        $reservation->update([
            'status' => 'Cancellato',
        ]);

        return redirect()->route($route)->with('success', 'Coupon cancellato.');
    }

    /** invia mail dal dettaglio della prenotazione */
    public function sendmail(Request $request)
    {
        try {
            $reservation = Reservation::find($request->reservation_id);

            $coupon_code = $reservation->coupon_code;
            //  $activity =  $request->activity;
            //  dd( $reservation->location->name);
            // $activityModel = Activity::find($activity['id']);
            //  $client = $request->client;
            $location = $reservation->location;



            if (!isset($client['companyName']) || $client['companyName'] == '') {
                $dataEmail['name'] = $reservation->nome_cliente;
                $dataEmail['surname'] = $reservation->cognome_cliente;
            } else {
                $dataEmail['name'] = $reservation->company_cliente;
                $dataEmail['surname'] = '';
            }

            //    $dataEmail['name'] = $reservation->nome_cliente;
            //    $dataEmail['surname'] = $reservation->cognome_cliente;
            $dataEmail['beneficiarioName'] = $reservation->nome_beneficiario;
            $dataEmail['beneficiarioSurname'] = $reservation->cognome_beneficiario;
            $dataEmail['activity_name'] = $reservation->nome_activity;
            $dataEmail['details'] = json_encode($reservation->details_activity, JSON_UNESCAPED_UNICODE);
            $dataEmail['note'] = json_encode($reservation->note_activity, JSON_UNESCAPED_UNICODE);
            $dataEmail['description'] = json_encode($reservation->description_activity, JSON_UNESCAPED_UNICODE);
            $dataEmail['prenotare'] = json_encode($reservation->prenotare_activity, JSON_UNESCAPED_UNICODE);
            $dataEmail['coupon_code'] = $coupon_code;
            $dataEmail['expiration_date'] = $reservation->data_scadenza;
            $dataEmail['title'] = "Il suo buono regalo per " . $reservation->location->name;
            $dataEmail['logo'] = $reservation->location->logo;
            $dataEmail['telefono'] = $reservation->location->telefono;
            $dataEmail['location_name'] = $reservation->location->name;
            // $dataEmail['ccn'] = 'shop@antoninocannavacciuolo.it';
            //generate pdf
            $dataEmail['fileName'] = 'Voucher ' . $dataEmail['name'] . ' ' . $dataEmail['surname'] . ' - ' . $coupon_code . '.pdf';
            PDF::loadView('emails.pdf', $dataEmail)->setPaper('a4', 'portrait')->save('vouchers/' . $dataEmail['fileName']);

            //  cambio le credenziali al volo

            config([
                'mail.mailers.smtp.username' => $reservation->location->utente_mail,
                'mail.mailers.smtp.password' => $reservation->location->password_mail,
                'mail.from.address' => $reservation->location->utente_mail,
                'mail.from.name' => $reservation->location->name,
                // altre configurazioni se necessario
            ]);


            //send email
            Mail::to($reservation->email_cliente)->send(new VoucherEmail($dataEmail));
            // Invio della risposta di successo
            return response()->json(['message' => 'Email inviata con successo'], 200);
        } catch (\Exception $e) {
            // Gestione degli errori
            Log::error('Errore rilevato in invio Email', [
                'exception' => $e
            ]);
            return response()->json(['error' => 'Si è verificato un errore durante l\'invio dell\'email'], 500);
        }
    }
    public function getAllCouponsIpratico()
    {
        $count = 0;
        $locations = Location::whereNotNull('ipratico_key')->get();
        foreach ($locations as $location) {
            $promo_body = '';
            $location_promo_codes_ipratico = IpraticoAPIService::api('GET', 'promo-codes', $promo_body, $location->ipratico_key);
            foreach ($location_promo_codes_ipratico as $promo_code) {
                // print_r($promo_code->value->code);
                $couponValue = $promo_code->value->code;

                $reservation = Reservation::where('coupon_code', $couponValue)->first();

                if ($reservation) {
                    // La prenotazione è stata trovata, puoi accedere ai suoi attributi
                    echo "La prenotazione è stata trovata: ";
                    echo "ID: " . $reservation->id . ", ";
                    echo "Nome: " . $reservation->data_scadenza . ", ";
                    echo "Coupon code: " . $reservation->coupon_code . ", ";
                    echo "aggiorno ipratico_id " . $promo_code->id;
                    echo "<br>";
                    $reservation->ipratico_id = $promo_code->id;
                    $reservation->save();
                    $count++;
                } else {
                    // Nessuna prenotazione trovata con il coupon code corrispondente
                    echo "Nessuna prenotazione trovata con il coupon code: $couponValue";
                    echo "<br>";
                }
            }
            print "Totale coupon aggiornati " . $count;
            return;
        }
    }
    public function searchCouponIpratico($coupon_code)
    {
        $count = 0;
        $coupon = Reservation::where('coupon_code', $coupon_code)->first();
        // controllo se il prodotto è di ipratico

        $locations = Location::whereNotNull('ipratico_key')->get();
        foreach ($locations as $location) {
            $promo_body = '';
            $location_promo_codes_ipratico = IpraticoAPIService::api('GET', 'promo-codes', $promo_body, $location->ipratico_key);
            foreach ($location_promo_codes_ipratico as $promo_code) {
                // print_r($promo_code->value->code);
                $couponValue = $promo_code->value->code;

                $reservation = Reservation::where('coupon_code', $couponValue)->first();

                if ($reservation) {
                    // La prenotazione è stata trovata, puoi accedere ai suoi attributi
                    echo "La prenotazione è stata trovata: ";
                    echo "ID: " . $reservation->id . ", ";
                    echo "Nome: " . $reservation->data_scadenza . ", ";
                    echo "Coupon code: " . $reservation->coupon_code . ", ";
                    echo "aggiorno ipratico_id " . $promo_code->id;
                    echo "<br>";
                    $reservation->ipratico_id = $promo_code->id;
                    $reservation->save();
                    $count++;
                } else {
                    // Nessuna prenotazione trovata con il coupon code corrispondente
                    echo "Nessuna prenotazione trovata con il coupon code: $couponValue";
                    echo "<br>";
                }
            }
            print "Totale coupon aggiornati " . $count;
            return;
        }
    }
    public function getIpraticoClientData(Request $request, $reservation_id)
    {
        $reservation = Reservation::find($reservation_id);
        $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors/' . $reservation->ipratico_client_id);

        //   dd($reservation->ipratico_client_id);

        //   $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors',  array('email' => $customer_email));
        if ($iPraticoClient) {
            //  $iPraticoClient = $iPraticoClient[0];
            // if exists, return the client data

            $email = null;
            if (isset($iPraticoClient->value->emails[0])) {
                $email = $iPraticoClient->value->emails[0];
            }

            $places = null;
            if (isset($iPraticoClient->value->places[0])) {
                $places = $iPraticoClient->value->places[0];
            }

            $phones = null;
            if (isset($iPraticoClient->value->phones[0])) {
                $phones = $iPraticoClient->value->phones[0];
            }

             if (!isset($iPraticoClient->value->fiscalCode)) {
                \Log::error('fiscalCode mancante nella risposta Ipratico', ['client' => $iPraticoClient]);

                return response()->json([
                    'message' => 'Errore: fiscalCode non trovato nei dati restituiti da Ipratico',
                    'success' => false
                ], 422); // oppure 400 o 500 in base alla gravità
            }
                

            if (ctype_digit($iPraticoClient->value->fiscalCode))
                $clientType = 'Azienda';
            else
                $clientType = 'privato';

            $client = array(
                'CF/PI' => $iPraticoClient->value->fiscalCode,
                'email' => $email,
                'Indirizzo' => $places->address,
                'Citta' => $places->city,
                'Provincia' => $places->province,
                'Nazione' => $places->nation,
                'CAP' => $places->zipCode,
                'Telefono' => $phones,
                'clientType' => $clientType,
            );

            if (isset($iPraticoClient->value->invoiceData->invoicingEndpoint)) {
                $client['invoicingEndpoint'] = $iPraticoClient->value->invoiceData->invoicingEndpoint;
            }

            // if the clientType is a company, return the company name
            if ($client['clientType'] == 'Azienda') {
                $client['companyName'] = $iPraticoClient->value->surnameOrCompanyName;
            } else {

                $client['Nome'] = $iPraticoClient->value->personal->foreName ?? null;

                $client['Cognome/Societa'] = $iPraticoClient->value->surnameOrCompanyName;
            }

            return response()->json([
                'message' => 'Client already exists',
                'client' => $client
            ], 200);
        } else {

            // if the client doesn't exist, return empty data
            return response()->json([
                'message' => 'Client does not exist',
                'client' => $client
            ], 200);
        }
    }
    public function getIpraticoClientId(Request $request, $ipraticoClientId)
    {

        $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors/' . $ipraticoClientId);
        if ($iPraticoClient) {

            // if exists, return the client data

            $email = null;
            if (isset($iPraticoClient->value->emails[0])) {
                $email = $iPraticoClient->value->emails[0];
            }

            $places = null;
            if (isset($iPraticoClient->value->places[0])) {
                $places = $iPraticoClient->value->places[0];
            }

            $phones = null;
            if (isset($iPraticoClient->value->phones[0])) {
                $phones = $iPraticoClient->value->phones[0];
            }
            if (ctype_digit($iPraticoClient->value->fiscalCode))
                $clientType = 'Azienda';
            else
                $clientType = 'privato';

            $client = array(
                'CF/PI' => $iPraticoClient->value->fiscalCode,
                'email' => $email,
                'Indirizzo' => $places->address,
                'Citta' => $places->city,
                'Provincia' => $places->province,
                'Nazione' => $places->nation,
                'CAP' => $places->zipCode,
                'Telefono' => $phones,
                'clientType' => $clientType,
            );

            if (isset($iPraticoClient->value->invoiceData->invoicingEndpoint)) {
                $client['invoicingEndpoint'] = $iPraticoClient->value->invoiceData->invoicingEndpoint;
            }

            // if the clientType is a company, return the company name
            if ($client['clientType'] == 'Azienda') {
                $client['companyName'] = $iPraticoClient->value->surnameOrCompanyName;
            } else {
                // dd($iPraticoClient[0]);
                $client['Nome'] = $iPraticoClient->value->personal->foreName;
                $client['Cognome/Societa'] = $iPraticoClient->value->surnameOrCompanyName;
            }

            return response()->json([
                'message' => 'Client already exists',
                'client' => $client
            ], 200);
        } else {

            // if the client doesn't exist, return empty data
            return response()->json([
                'message' => 'Client does not exist',
                'client' => $client
            ], 200);
        }
    }
    public function regenerateVoucher(Request $request)
    {

        $reservationId = $request->reservation_id;

        // Recupera i dati della prenotazione dalla tabella reservations
        $reservation = Reservation::find($reservationId);

        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }

        $activityModel = Activity::find($reservation->activity_id);

        $location = $reservation->location;
        $client = [
            'name' => $reservation->nome_cliente,
            'surname' => $reservation->cognome_cliente,
            'companyName' => $reservation->company_cliente,
            'emailName' => $reservation->nome_beneficiario,
            'emailSurname' => $reservation->cognome_beneficiario,
        ];

        $dataEmail = [
            'name' => $reservation->company_cliente ? $reservation->company_cliente : $reservation->nome_cliente,
            'surname' => $reservation->company_cliente ? '' : $reservation->cognome_cliente,
            'beneficiarioName' => $reservation->nome_beneficiario,
            'beneficiarioSurname' => $reservation->cognome_beneficiario,
            'activity_name' => $reservation->nome_activity,
            'details' => $reservation->details_activity,
            'note' => $reservation->note_activity,
            'description' => $reservation->description_activity,
            'prenotare' => $reservation->prenotare_activity,
            'coupon_code' => $reservation->coupon_code,
            'expiration_date' => $reservation->data_scadenza,
            'title' => "Il suo buono regalo per " . $location->name,
            'logo' => $location->logo,
            'telefono' => $location->telefono,
            'location_name' => $location->name,
            'ccn' => 'shop@antoninocannavacciuolo.it',
        ];

        $dataEmail['fileName'] = 'Voucher ' . $dataEmail['name'] . ' ' . $dataEmail['surname'] . ' - ' . $dataEmail['coupon_code'] . '.pdf';
        PDF::loadView('emails.pdf', $dataEmail)->setPaper('a4', 'portrait')->save('vouchers/' . $dataEmail['fileName']);

        return response()->json(['success' => 'Voucher regenerated successfully', 'fileName' => $dataEmail['fileName']]);
    }

    public function testIpratico($id)
    {

        $reservation = Reservation::findOrFail($id);
        $ipraticoClientId = $reservation->ipratico_client_id;

        $iPraticoClient = IpraticoAPIService::api('GET', 'business-actors/' . $ipraticoClientId);

        if (!$iPraticoClient) {
            return response()->json(['error' => 'Nessuna risposta da iPratico'], 500);
        }

        // Debug visivo
        return response()->json($iPraticoClient);
    }

}
