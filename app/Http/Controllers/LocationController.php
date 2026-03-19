<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DataTables;
use Illuminate\Support\Str;
use App\Services\IpraticoAPIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::all();
        return view('locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        // $locations = Location::orderBy('name')->get();
        return view('locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'suffix' => 'required|string|max:2',
            'ipratico_key' => 'nullable|string|max:255',
            'utente_mail' => 'nullable|string|email|max:255',
            'password_mail' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $location = new Location();
        $location->fill($request->all());

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = $file->getClientOriginalName();
            $file->move(public_path('logos'), $fileName);
            $location->logo = $fileName;
        }

        $location->save();

        // Gestisci le chiusure per ferie
        $ferieStartDates = $request->input('ferie_start', []);
        $ferieEndDates = $request->input('ferie_end', []);

        foreach ($ferieStartDates as $index => $startDate) {
            Closure::create([
                'location_id' => $location->id,
                'start_date' => $startDate,
                'end_date' => $ferieEndDates[$index]
            ]);
        }

        return redirect()->route('locations.index')->with('success', 'Location created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'suffix' => 'required|string|max:2',
            'ipratico_key' => 'nullable|string|max:255',
            'utente_mail' => 'nullable|string|email|max:255',
            'password_mail' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $location = Location::findOrFail($id);
        $location->fill($request->all());

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = $file->getClientOriginalName();
            $file->move(public_path('logos'), $fileName);
            $location->logo = $fileName;
        }

        $location->save();

        // Gestisci le chiusure per ferie
        Closure::where('location_id', $id)->delete(); // Rimuovi chiusure esistenti

        $ferieStartDates = $request->input('ferie_start', []);
        $ferieEndDates = $request->input('ferie_end', []);

        foreach ($ferieStartDates as $index => $startDate) {
            Closure::create([
                'location_id' => $location->id,
                'start_date' => $startDate,
                'end_date' => $ferieEndDates[$index]
            ]);
        }

        return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $location = Location::findOrFail($id);

        return view('locations.create', compact('location'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getLocations(Request $request)
    {
        $columns = array(
            0 => 'name',
            1 => 'telefono',
            2 => 'utente_mail',
            3 => 'logo',

        );


        // processa tutte le variabili inviate get in ajax
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');


        $totalData =  $totalFiltered =  Location::all()
            ->count();

        $totalFiltered = $totalData;

        $user = auth()->user(); // Ottieni l'utente autenticato


        // controlla se ci sono  dei parametri di ricerca
        if (empty($request->input('search.value'))) {

            $query = Location::offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);

            $users = $query->get();
            $totalFiltered =  Location::all()
                ->count();
            //  dd($sql);
        } else { // bisogna mettere i criteri per ogni campo su cui cercare
            $search = $request->input('search.value');

            $query = Location::where(function ($query) use ($search) {
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

            $totalFiltered = Location::where(function ($query) use ($search) {
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
                $nestedData['telefono'] = $user->telefono;
                $nestedData['utente_mail'] = $user->utente_mail;
                $nestedData['logo'] =  $user->logo;

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
