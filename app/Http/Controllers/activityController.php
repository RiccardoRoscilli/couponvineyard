<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Services\IpraticoAPIService;
use Illuminate\Database\QueryException;

use DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
        $activities = Activity::with('location')->get();
        $locations = Location::all();
        $is_admin = auth()->user()?->is_admin === 1;

        return view('activities.index', compact('activities', 'locations', 'is_admin'));
    }


    public function getActivitiesDatatable(Request $request)
    {
        $columns = [
            'name',            // 0
            'sku',             // 1
            'location_id',     // 2
            'product_value',   // 3
            'created_at',      // 4
        ];

        $limit = $request->input('length');
        $start = $request->input('start');

        $orderColumnIndex = $request->input('order.0.column');
        $order = $columns[$orderColumnIndex] ?? 'created_at';
        $dir = $request->input('order.0.dir') ?? 'desc';

        $location_id = $request->input('location_id');
        $user = auth()->user();

        if ($user->is_admin == 0) {
            $location_id = $user->location_id;
        }

        if (empty($location_id)) {
            $location_id = Location::pluck('id')->implode(',');
        }

        $baseQuery = Activity::with('location')->whereIn('location_id', explode(',', $location_id));

        $totalData = $baseQuery->count();
        $totalFiltered = $totalData;

        if (empty($request->input('search.value'))) {
            $activities = $baseQuery
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');

            $baseQuery = $baseQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });

            $totalFiltered = $baseQuery->count();

            $activities = $baseQuery
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        }

        $data = [];
        foreach ($activities as $activity) {
            $nestedData = [];
            $nestedData['id'] = $activity->id;
            $nestedData['name'] = $activity->name;
            $nestedData['sku'] = $activity->sku;
            $nestedData['location_name'] = $activity->location->name ?? '-';
            $nestedData['product_value'] = $activity->product_value;

            $nestedData['actions'] = '
            <div class="text-center">
                <a href="' . route('activities.edit', $activity->id) . '" class="btn btn-sm btn-primary me-1">Modifica</a>
                <a href="' . route('activities.duplicate', $activity->id) . '" class="btn btn-sm btn-info me-1">Duplica</a>
                <button type="button" class="btn btn-sm btn-danger btn-delete-activity" 
                        data-id="' . $activity->id . '" 
                        data-name="' . e($activity->name) . '">
                    Elimina
                </button>
            </div>';

            $data[] = $nestedData;
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ]);
    }
    public function create()
    {
        $locations = Location::all();
        $product_categories = IpraticoAPIService::api('GET', 'product-categories');

        if ($product_categories == 'error') {
            return view('activities.error_api');
        }

        return view('activities.form', [
            'mode' => 'create',
            'activity' => null,
            'locations' => $locations,
            'product_categories' => $product_categories
        ]);
    }

    public function edit(Activity $activity)
    {
        $locations = Location::all();
        $product_categories = IpraticoAPIService::api('GET', 'product-categories');
        //   dd($product_categories);

        if ($product_categories === 'error') {
            return view('activities.error_api');
        }
        return view('activities.form', [
            'mode' => 'edit',
            'activity' => $activity,
            'locations' => $locations,
            'product_categories' => $product_categories
        ]);
    }
    public function show(Activity $activity)
    {


        return;
    }
    public function duplicate(Activity $activity)
    {
        $locations = Location::all();
        $product_categories = IpraticoAPIService::api('GET', 'product-categories');

        if ($product_categories === 'error') {
            return view('activities.error_api');
        }

        return view('activities.form', [
            'mode' => 'duplicate',
            'activity' => $activity,
            'locations' => $locations,
            'product_categories' => $product_categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'location_id' => 'required',
            'sku' => 'required|string|unique:activities,sku',
        ]);

        $activity = new Activity();
        $activity->location_id = $request->location_id;
        $activity->name = $request->name;
        $activity->name_en = $request->name_en;
        $activity->name_fr = $request->name_fr;
        $activity->sku = str_replace(' ', '', $request->sku);
        $activity->description = $request->description;
        $activity->description_en = $request->description_en;
        $activity->description_fr = $request->description_fr;
        $activity->details = $request->details;
        $activity->details_en = $request->details_en;
        $activity->details_fr = $request->details_fr;
        $activity->note = $request->note;
        $activity->note_en = $request->note_en;
        $activity->note_fr = $request->note_fr;
        $activity->prenotare = $request->prenotare;
        $activity->prenotare_en = $request->prenotare_en;
        $activity->prenotare_fr = $request->prenotare_fr;
        $activity->ipratico_id = $request->activity_ipratico_id;
        $activity->ipratico_category_id = $request->category_ipratico_id;
        $activity->product_value = $request->product_value;
        $activity->save();

        return redirect()->route('activities.index')->with('success', 'Attività creata con successo.');
    }


    public function update(Request $request, Activity $activity)
    {
        $request->validate([
            'name' => 'required',
            'location_id' => 'required',
            'sku' => 'required|string|unique:activities,sku,' . $activity->id,
        ]);

        $activity->fill([
            'name' => $request->name,
            'name_en' => $request->name_en,
            'name_fr' => $request->name_fr,
            'description' => $request->description,
            'description_en' => $request->description_en,
            'description_fr' => $request->description_fr,
            'details' => $request->details,
            'details_en' => $request->details_en,
            'details_fr' => $request->details_fr,
            'note' => $request->note,
            'note_en' => $request->note_en,
            'note_fr' => $request->note_fr,
            'prenotare' => $request->prenotare,
            'prenotare_en' => $request->prenotare_en,
            'prenotare_fr' => $request->prenotare_fr,
            'sku' => str_replace(' ', '', $request->sku),
            'location_id' => $request->location_id,
            'ipratico_id' => $request->activity_ipratico_id,
            'ipratico_category_id' => $request->category_ipratico_id,
            'product_value' => $request->product_value,
        ]);

        $activity->save();

        return redirect()->route('activities.index')->with('success', 'Attività aggiornata con successo.');
    }



    public function replicate(Request $request, $id)
    {
        try {
            $oldactivity = Activity::findOrFail($id);
            $activity = $oldactivity->replicate();

            $activity->fill([
                'name' => $request->name,
                'name_en' => $request->name_en,
                'name_fr' => $request->name_fr,
                'description' => $request->description,
                'description_en' => $request->description_en,
                'description_fr' => $request->description_fr,
                'details' => $request->details,
                'details_en' => $request->details_en,
                'details_fr' => $request->details_fr,
                'note' => $request->note,
                'note_en' => $request->note_en,
                'note_fr' => $request->note_fr,
                'prenotare' => $request->prenotare,
                'prenotare_en' => $request->prenotare_en,
                'prenotare_fr' => $request->prenotare_fr,
                'location_id' => $request->location_id,
                'ipratico_id' => $request->activity_ipratico_id,
                'ipratico_category_id' => $request->category_ipratico_id,
                'product_value' => $request->product_value,
            ]);

            $activity->sku = str_replace(' ', '', $request->sku);

            $request->validate([
                'name' => 'required',
                'location_id' => 'required',
                'sku' => 'required|string|unique:activities,sku',
            ]);

            $activity->save();

            return redirect()->route('activities.index')->with('success', 'Attività duplicata con successo.');
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                return redirect()->back()->withInput()->withErrors([
                    'sku' => 'SKU già esistente. Modifica lo SKU prima di salvare.'
                ]);
            }

            throw $e;
        }
    }



    public function destroy(Activity $activity)
    {
        $activity->delete();
        return redirect()->route('activities.index')->with('success', 'Attività eliminata.');
    }

    public function getProductsiPratico(Request $request)
    {
        $categoryId = $request->input('category_id');
        $body = ['productCategoryId' => $categoryId];

        $products = IpraticoAPIService::api('GET', 'products', $body);

        if ($products === 'error') {
            return view('activities.error_api');
        }

        return response()->json($products);
    }
}
