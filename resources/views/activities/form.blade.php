@extends('layouts.app_admin')

@section('content')
    <div class="container mt-4">
        <h1 class="mb-4">
            @if ($mode === 'edit')
                Modifica Prodotto
            @elseif ($mode === 'duplicate')
                Duplica Prodotto
            @else
                Nuova Prodotto
            @endif
        </h1>

        <form method="POST"
            action="
        @if ($mode === 'edit') {{ route('activities.update', $activity->id) }}
        @elseif ($mode === 'duplicate')
            {{ route('activities.replicate', $activity->id) }}
        @else
            {{ route('activities.store') }} @endif
    ">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif

            {{-- TAB LINGUA --}}
            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="it-tab" data-bs-toggle="tab" data-bs-target="#tab-it"
                        type="button" role="tab">🇮🇹 Italiano</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="en-tab" data-bs-toggle="tab" data-bs-target="#tab-en" type="button"
                        role="tab">🇬🇧 English</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="fr-tab" data-bs-toggle="tab" data-bs-target="#tab-fr" type="button"
                        role="tab">🇫🇷 Français</button>
                </li>
            </ul>


            <div class="tab-content border rounded-bottom p-3 bg-light" id="langTabsContent">
                {{-- ITALIANO --}}
                <div class="tab-pane fade show active" id="tab-it" role="tabpanel">
                    @include('activities.partials.fields', ['lang' => '', 'activity' => $activity])
                </div>

                {{-- INGLESE --}}
                <div class="tab-pane fade" id="tab-en" role="tabpanel">
                    @include('activities.partials.fields', ['lang' => '_en', 'activity' => $activity])
                </div>

                {{-- FRANCESE --}}
                <div class="tab-pane fade" id="tab-fr" role="tabpanel">
                    @include('activities.partials.fields', ['lang' => '_fr', 'activity' => $activity])
                </div>
            </div>


            {{-- SEZIONE COMUNE --}}
            <hr class="my-4">
            <h5>Dati Generali</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="location_id" class="form-label">Location</label>
                    <select class="form-select" name="location_id" required>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}"
                                {{ old('location_id', $activity->location_id ?? '') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="product_value" class="form-label">Valore Prodotto</label>
                    <input type="number" class="form-control" name="product_value" step="0.01"
                        value="{{ old('product_value', $activity->product_value ?? '') }}">
                </div>
            </div>


            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('activities.index') }}" class="btn btn-secondary me-2">Annulla</a>
                <button type="submit" class="btn btn-primary">
                    @if ($mode === 'edit')
                        Salva Modifiche
                    @elseif ($mode === 'duplicate')
                        Duplica Attività
                    @else
                        Crea Attività
                    @endif
                </button>
            </div>
        </form>
    </div>
@endsection
