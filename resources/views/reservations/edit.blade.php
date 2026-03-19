@extends('layouts.app_admin')

@section('content')

    <div class="container">
        @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">

                <strong>{{ $message }}</strong>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="row">
            <div class="col-6 m-5"> <b>Gestisci Coupon: </b>{{ $reservation->coupon_code }}. <b> Stato:</b>
                {{ $reservation->status }}.
            </div>

            <div class="col-4 m-5 d-flex justify-content-end ">
                @if (auth()->user()->is_admin)
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                        Cancella
                    </button>
                @endif
            </div>

            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Conferma cancellazione</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Sei sicuro di voler cancellare questo coupon?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                            <form action="{{ route('coupon.destroy', ['reservation' => $reservation->id]) }}"
                                method="POST">

                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Cancella</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card border-0 mx-auto" style="max-width: 90%; ">
                    <div class="card-body bg-light m-2 text-center">
                        <h2 class="card-title">{{ $reservation->coupon_code }}</h2>
                        <p class="card-text">{{ $reservation->nome_activity }}</p>
                        <p class="card-text">€ {{ $reservation->amount }}</p>
                        <p class="card-text">Scadenza:
                            {{ \Carbon\Carbon::parse($reservation->data_scadenza)->format('d/m/Y') }}</p>
                        <h5 class="card-title">Elenco servizi</h5>
                        <p class="card-text">{{ $reservation->details_activity }}</p>
                        <button class="btn btn-primary send-mail" id="sendMailBtn"
                            data-reservation-id="{{ $reservation->id }}">
                            Reinvia Email ad Acquirente
                            <i class="bi bi-envelope-fill"></i>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>

                    </div>
                    <script>
                        $(document).ready(function() {
                            $('.send-mail').on('click', function() {
                                var reservationId = $(this).data(
                                    'reservation-id'); // Recupera l'ID della prenotazione dal pulsante

                                // Mostra lo spinner
                                $(this).find('.spinner-border').removeClass('d-none');

                                // Disabilita il pulsante durante l'invio della mail
                                $(this).prop('disabled', true);

                                // Effettua una chiamata AJAX per inviare la mail
                                axios.post('{{ route('api.send.mail') }}', {
                                        _token: '{{ csrf_token() }}',
                                        reservation_id: reservationId // Passa l'ID della prenotazione nella richiesta
                                    })
                                    .then(function(response) {
                                        // Nascondi lo spinner
                                        $('#sendMailBtn .spinner-border').addClass('d-none');

                                        // Abilita nuovamente il pulsante
                                        $('#sendMailBtn').prop('disabled', false);

                                        // Cambia il testo del pulsante
                                        $('#sendMailBtn').html(
                                            'Email Inviata! <i class="bi bi-check-circle-fill"></i>');


                                    })
                                    .catch(function(error) {
                                        // Nascondi lo spinner
                                        $('#sendMailBtn .spinner-border').addClass('d-none');

                                        // Abilita nuovamente il pulsante
                                        $('#sendMailBtn').prop('disabled', false);

                                        $('#sendMailBtn').html(
                                            'Errore Invio Mail <i class="bi bi-exclamation-triangle-fill"></i>');
                                        $('#sendMailBtn').removeClass('btn-primary').addClass('btn-danger');
                                    });
                            });
                        });
                    </script>


                </div>
            </div>
            <div class="col-8">

                <form class="row g-3" method="POST"
                    action="{{ route('coupon.update', ['reservation' => $reservation->id]) }}">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label for="databooking">Data Arrivo</label>
                        <input type="date" class="form-control" id="databooking" name="databooking"
                            value="{{ $reservation->databooking }}" @if ($reservation->status != 'Cancellato') required @endif>
                    </div>
                    <div class="col-md-6">
                        <label for="ora">Ora Arrivo</label>
                        <input type="text" class="form-control" id="orabooking" name="orabooking"
                            value="{{ $reservation->orabooking }}" @if ($reservation->status != 'Cancellato') required @endif>
                        <script>
                            $(document).ready(function() {
                                var $j = jQuery.noConflict();
                                $('#orabooking').timepicker({
                                    icons: {
                                        up: 'bi bi-caret-up-fill',
                                        down: 'bi bi-caret-down-fill'
                                    },
                                    minuteStep: 15,
                                    showSeconds: false,
                                    showMeridian: false,
                                    defaultTime: false
                                });
                            });
                        </script>
                    </div>
                    <div class="col-md-6">
                        <label for="Nome">Nome</label>
                        <input type="text" class="form-control" id="nome_beneficiario"
                            value="{{ $reservation->nome_beneficiario }}" name="nome_beneficiario">
                    </div>
                    <div class="col-md-6">
                        <label for="Cognome">Cognome</label>
                        <input type="text" class="form-control" id="nome_beneficiario"
                            value="{{ $reservation->cognome_beneficiario }}" name="cognome_beneficiario">

                    </div>
                    <div class="col-md-6">
                        <label for="email_beneficiario">Email</label>
                        <input type="text" class="form-control" id="email_beneficiario"
                            value="{{ $reservation->email_beneficiario }}" name="email_beneficiario">
                    </div>
                    <div class="col-md-6">
                        <label for="telefono_beneficiario">Telefono</label>
                        <input type="text" class="form-control" id="telefono_beneficiario"
                            value="{{ $reservation->telefono_beneficiario }}" name="telefono_beneficiario">

                    </div>
                    <div class="col-md-6">
                        <label for="n_tavolo">Numero Tavolo</label>
                        <input type="text" class="form-control" id="n_tavolo" value="{{ $reservation->n_tavolo }}"
                            name="n_tavolo">
                    </div>
                    <div class="col-md-6">
                        <label for="n_camera">Numero Camera</label>
                        <input type="text" class="form-control" id="n_camera" value="{{ $reservation->n_camera }}"
                            name="n_camera">

                    </div>
                    <div class="col-md-6">
                        <label for="data_fattura">Data Fattura</label>
                        <input type="date" class="form-control" id="data_fattura" name="data_fattura"
                            value="{{ \Carbon\Carbon::parse($reservation->data_fattura)->format('Y-m-d') }}" disabled>

                    </div>
                    <div class="col-md-6">
                        <label for="data_scadenza">Data Scadenza</label>
                        <input type="date" class="form-control" id="data_scadenza" name="data_scadenza"
                            value="{{ $reservation->data_scadenza }}" >

                    </div>
                    <div class="col-12">
                        <label for="note">Note</label>
                        <textarea class="form-control" id="note_beneficiario" name="note_beneficiario" rows="3">{{ $reservation->note_beneficiario }}</textarea>
                    </div>



                    <div class="col-12  mt-3 d-flex justify-content-between ">
                        @if ($reservation->status == 'In Arrivo')
                            <button type="submit" name="action" value="segna_usufruito" class="btn btn-warning">Segna
                                come
                                Usufruito</button>
                            <button type="submit" name="action" value="rimetti_attesa" class="btn btn-info">Rimetti in
                                attesa</button>
                        @endif
                        @if ($reservation->status == 'Usufruito')
                            <button type="submit" name="action" value="rimetti_arrivo" class="btn btn-info">Rimetti in
                                arrivo</button>
                        @endif
                        @if ($reservation->status == 'Cancellato')
                            <button type="submit" name="action" value="rimetti_attesa" class="btn btn-info">Rimetti in
                                attesa</button>
                        @endif

                        <button type="submit" name="action" value="salva" class="btn btn-success">Salva</button>


                    </div>

            </div>
            </form>
        </div>

    </div>
    </div>


@endsection
