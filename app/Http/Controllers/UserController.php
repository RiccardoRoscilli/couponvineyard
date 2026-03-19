<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth'); // Assicura che l'utente sia autenticato
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user && $user->is_admin !== 1) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function create()
    {

        $locations = Location::orderBy('name')->get();
        return view('users.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'ruolo' =>  'required',
            // Aggiungi qui altre regole di validazione per gli altri campi
        ]);

        // Verifica se il ruolo è "Concierge" e se una location è stata selezionata
        if ($request->input('ruolo') === 'Concierge' && !$request->has('location_id')) {
            return redirect()->back()->withErrors('Seleziona una location quando il ruolo è "Concierge".')->withInput();
        }

        $is_admin = $request->input('ruolo') === 'Booking' ? 1 : 0;



        $user = User::create([
            'name' => $request->input('nome'),
            'cognome' => $request->input('cognome'),
            'email' => $request->input('email'),
            'is_admin' => $is_admin,
            'location_id' => $request->input('location_id'),
        ]);
        // Verifica se è stata fornita una password
        if ($request->filled('password')) {
            // Se sì, crittografa la password e aggiorna l'utente
            $user->password = Hash::make($request->input('password'));
            $user->save();
        }
        if ($user) {
            // Se il salvataggio ha avuto successo, reindirizza alla vista degli utenti
            return redirect()->route('user.index')->with('success', 'Utente creato con successo.');
        } else {
            // Se si è verificato un errore, reindirizza al form di creazione con i vecchi dati e gli errori di validazione
            return redirect()->route('user.create')->withErrors('Errore durante il salvataggio dell\'utente.');
        }
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('users.show', compact('user'));
    }


    public function edit($id)
    {
        $user = User::findOrFail($id);
        $locations = Location::all();
        return view('users.create', compact('user', 'locations'));
    }

    public function update(Request $request, $id)
    {
        // Trova l'utente da aggiornare
        $user = User::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'ruolo' =>  'required',
            // Aggiungi qui altre regole di validazione per gli altri campi
        ]);

        // Verifica se il ruolo è "Concierge" e se una location è stata selezionata
        if ($request->input('ruolo') === 'Concierge' && !$request->has('location_id')) {
            return redirect()->back()->withErrors('Seleziona una location quando il ruolo è "Concierge".')->withInput();
        }

        $is_admin = $request->input('ruolo') === 'Booking' ? 1 : 0;



        // Aggiorna l'utente con i nuovi dati
        $user->update([
            'name' => $request->input('nome'),
            'cognome' => $request->input('cognome'),
            'email' => $request->input('email'),
            'location_id' => $request->input('location_id'),
            'is_admin' => $is_admin,
        ]);

        // Aggiorna la password solo se fornita
        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
            $user->save();
        }

        // Redirect in base al risultato dell'aggiornamento
        if ($user) {
            return redirect()->route('user.index')->with('success', 'Utente aggiornato con successo.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Si è verificato un errore durante l\'aggiornamento dell\'utente.');
        }
    }

    public function destroy($id)
    {
        // Trova l'utente per ID
        $user = User::findOrFail($id);

        // Cancella l'utente
        $user->delete();

        // Reindirizza alla pagina degli utenti o ad un'altra pagina
        return redirect()->route('user.index')->with('success', 'Utente cancellato con successo.');
    }

    public function getUsers(Request $request)
    {


        // elenco delle colonne usate nella table
        $columns = array(
            0 => 'name',
            1 => 'cognome',
            2 => 'email',
            3 => 'ruolo',
        );


        // processa tutte le variabili inviate get in ajax
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');


        $totalData =  $totalFiltered =  User::all()
            ->count();

        $totalFiltered = $totalData;

        $user = auth()->user(); // Ottieni l'utente autenticato


        // controlla se ci sono  dei parametri di ricerca
        if (empty($request->input('search.value'))) {

            $query = User::offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);

            $users = $query->get();
            $totalFiltered =  User::all()
                ->count();
            //  dd($sql);
        } else { // bisogna mettere i criteri per ogni campo su cui cercare
            $search = $request->input('search.value');

            $query = User::where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('cognome', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
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

            $users = $query->get();

            $totalFiltered = User::where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('cognome', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
                // Aggiungi altre colonne dove cercare, se necessario
            })
                ->count();
        }

        $data = array();
        if (!empty($users)) {
            foreach ($users as $user) {
                // $show =  route('admin.services.show', $post->id);
                //       $edit =  route('admin.services.edit', $user->id);
                //       $delete =  route('admin.services.delete', $user->id);

                $nestedData['id'] = $user->id;
                $nestedData['name'] = $user->name;
                $nestedData['cognome'] = $user->cognome;
                $nestedData['email'] = $user->email;
                $nestedData['ruolo'] = ($user->is_admin) ? 'Booking' : 'Concierge';

                $data[] = $nestedData;
            }
        }
        //  $data = Reservation::where('status', 'In Attesa')->latest()->get(); // Filtra le locations per l'utente autenticato

        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );

        return json_encode($json_data);
    }
}
