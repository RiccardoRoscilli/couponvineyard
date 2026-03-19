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
                <form method="POST" action="{{ isset($location) ? route('locations.update', $location->id) : route('locations.store') }}"
                    class="mx-auto" enctype="multipart/form-data">
                    @csrf
                    @if (isset($location))
                        @method('PUT')
                    @endif
                    <div class="row">
                        <!-- Prima colonna -->
                        <div class="col-md-6">
                            <div class="form-group m-2">
                                <label for="name">Nome</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name', isset($location) ? $location->name : '') }}" required>
                            </div>
                            <div class="form-group m-2">
                                <label for="utente_mail">User Email</label>
                                <input type="email" class="form-control" id="utente_mail" name="utente_mail"
                                    value="{{ old('utente_mail', isset($location) ? $location->utente_mail : '') }}">
                            </div>
                            <div class="form-group m-2">
                                <label for="telefono">Telefono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono"
                                    value="{{ old('telefono', isset($location) ? $location->telefono : '') }}">
                            </div>
                        </div>

                        <!-- Seconda colonna -->
                        <div class="col-md-6">
                            <div class="form-group m-2">
                                <label for="suffix">Prefisso per coupon (2 lettere)</label>
                                <input type="text" class="form-control" id="suffix" name="suffix"
                                    value="{{ old('suffix', isset($location) ? $location->suffix : '') }}" required>
                            </div>
                            <div class="form-group m-2">
                                <label for="password_mail">Password Email</label>
                                <input type="text" class="form-control" id="password_mail" name="password_mail"
                                    value="{{ old('password_mail', isset($location) ? $location->password_mail : '') }}">
                            </div>
                            <div class="form-group m-2">
                                <label for="ipratico_key">Chiave Ipratico</label>
                                <input type="text" class="form-control" id="ipratico_key" name="ipratico_key"
                                    value="{{ old('ipratico_key', isset($location) ? $location->ipratico_key : '') }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="form-group m-2">
                            <label for="logo">Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            @if (isset($location) && $location->logo)
                                <img src="{{ asset('logos/' . $location->logo) }}" alt="Logo" class="img-fluid mt-2">
                            @endif
                        </div>
                    </div>
                    <div class="col-12 mt-5">
                        <h5>Chiusure</h5>
                        <table class="table table-bordered" id="ferie-table">
                            <thead>
                                <tr>
                                    <th>Data Inizio</th>
                                    <th>Data Fine</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($location))
                                    @foreach ($location->closures as $closure)
                                        <tr>
                                            <td><input type="date" name="ferie_start[]" class="form-control" value="{{ $closure->start_date }}" required></td>
                                            <td><input type="date" name="ferie_end[]" class="form-control" value="{{ $closure->end_date }}" required></td>
                                            <td><button type="button" class="btn btn-danger remove-row">-</button></td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success" id="add-row">+</button>
                    </div>

                    <div class="col-12 mt-3 d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary">
                            {{ isset($location) ? 'Modifica Location' : 'Aggiungi Location' }}
                        </button>

                        @if (isset($location))
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#deleteUserModal">Cancella Location</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal per cancellazione -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Conferma cancellazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteUserForm" action="{{ route('locations.destroy', isset($location) ? $location->id : '') }}"
                    method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        Sei sicuro di voler cancellare questa location?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-danger">Conferma Cancellazione</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript per gestire le righe dinamiche -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addRowButton = document.getElementById('add-row');
            const ferieTableBody = document.querySelector('#ferie-table tbody');

            addRowButton.addEventListener('click', function() {
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="date" name="ferie_start[]" class="form-control" required></td>
                    <td><input type="date" name="ferie_end[]" class="form-control" required></td>
                    <td><button type="button" class="btn btn-danger remove-row">-</button></td>
                `;
                ferieTableBody.appendChild(newRow);
            });

            ferieTableBody.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-row')) {
                    event.target.closest('tr').remove();
                }
            });
        });
    </script>
@endsection
