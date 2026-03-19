@extends('layouts.app_admin')

@section('content')
    <div class="container">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" action="{{ isset($user) ? route('user.update', $user->id) : route('user.store') }}"
                    class="mx-auto">
                    @csrf
                    @if (isset($user))
                        @method('PUT')
                    @endif

                    <div class="row">
                        <!-- Prima colonna -->
                        <div class="col-md-6">
                            <div class="form-group m-2">
                                <label for="nome">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome"
                                    value="{{ old('nome', isset($user) ? $user->name : '') }}" required>
                            </div>

                            <div class="form-group m-2">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ old('email', isset($user) ? $user->email : '') }}"
                                    placeholder="Inserisci l'email">
                            </div>
                            <div class="form-group m-2">
                                <label for="ruolo">Ruolo</label>
                                <select class="form-control" id="ruolo" name="ruolo" required>
                                    <option value="">Seleziona Ruolo</option>
                                    <option value="Booking" {{ isset($user) && $user->is_admin == '1' ? 'selected' : '' }}>
                                        Booking</option>
                                    <option value="Concierge"
                                        {{ isset($user) && $user->is_admin == '0' ? 'selected' : '' }}>Concierge</option>

                                </select>
                            </div>

                        </div>

                        <!-- Seconda colonna -->
                        <div class="col-md-6">
                            <div class="form-group m-2">
                                <label for="cognome">Cognome</label>
                                <input type="text" class="form-control" id="cognome" name="cognome"
                                    value="{{ old('cognome', isset($user) ? $user->cognome : '') }}" required>
                            </div>
                            <div class="form-group m-2">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" value="">
                            </div>


                            <!-- Altri campi qui -->
                        </div>
                        <!-- Gruppo di radio button per le location -->
                        <div class="form-group m-3" id="locations-group" style="display: none;">
                            <label>Seleziona la location:</label><br>
                            <div class="row">
                                @foreach ($locations as $location)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="location_id"
                                                id="location_{{ $location->id }}" value="{{ $location->id }}"
                                                {{ isset($user) && $user->location_id == $location->id ? 'checked' : '' }}>
                                            <label class="form-check-label" for="location_{{ $location->id }}">
                                                {{ $location->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="col-12  mt-3 d-flex justify-content-between ">
                        <button type="submit"
                            class="btn btn-primary">{{ isset($user) ? 'Modifica Utente' : 'Aggiungi Utente' }}</button>

                        @if (isset($user))
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteUserModal">Cancella Utente</button>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- Pulsante di cancellazione -->

    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Conferma cancellazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteUserForm" action="{{ route('user.destroy', isset($user) ? $user->id : '') }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        Sei sicuro di voler cancellare questo utente?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-danger">Conferma Cancellazione</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Script JavaScript -->
    <script>
        $(document).ready(function() {
            // Check the value of the ruolo select on page load
            checkRuolo();

            // Bind change event to the ruolo select
            $('#ruolo').change(function() {
                checkRuolo();
            });

            function checkRuolo() {
                var selectedRuolo = $('#ruolo').val();
                if (selectedRuolo === 'Concierge') {
                    $('#locations-group').show();
                } else {
                    $('#locations-group').hide();
                }
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ruoloSelect = document.getElementById('ruolo');
            const locationsGroup = document.getElementById('locations-group');

            ruoloSelect.addEventListener('change', function() {
                if (this.value === 'Concierge') {
                    // Mostra il gruppo delle location
                    locationsGroup.style.display = 'block';
                } else {
                    // Nascondi il gruppo delle location
                    locationsGroup.style.display = 'none';
                }
            });
        });
    </script>
@endsection
