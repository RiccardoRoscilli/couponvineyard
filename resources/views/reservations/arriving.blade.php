@extends('layouts.app_admin')

@section('content')
    <div class="container">


        @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">

                <strong>{{ $message }}</strong>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            </div>
        @endif

    </div>
    <!-- Abilita i tooltip di Bootstrap -->
    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
    <div class="container mt-5">
        @if (auth()->user()->is_admin)
            <div class="form-group mb-4">
                <select id='location' class="form-control" style="width: 200px">
                    <option value="">Tutte le Location</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <table class="table table-bordered yajra-datatable">
            <thead>
                <tr>
                    <th>Coupon</th>
                    <th>Esperienza</th>
                    <th>Acquirente</th>
                    <th>Nome Cliente (nascosto)</th>
                    <th>Cognome Cliete (nascosto)</th>
                    <th>Beneficiario</th>
                    <th>Nome Beneficiario(nascosto)</th>
                    <th>Cognome Beneficiario (nascosto)</th>
                    <th>In Arrivo Il</th>
                    <th>Alle Ore</th>
                    <th>N. Tavolo</th>
                    <th>N. Camera</th>
                    <th>Scadenza</th>
                    <th>Valore</th>
                    <th>Voucher</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <!--Modal cancellazione -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Conferma eliminazione</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Sei sicuro di voler eliminare questo record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancelBtn">Annulla</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Elimina</button>
                </div>
            </div>
        </div>
    </div>



    <script type="text/javascript">
        $(function() {

            var table = $('.yajra-datatable').DataTable({
                "rowCallback": function(row, data) {
                    $(row).css('cursor', 'pointer');
                    $(row).on('click', function(event) {
                        var target = $(event.target).closest(
                            '.btn'); // Assicurati di selezionare il pulsante

                        // Se il target non è un pulsante, reindirizza alla pagina di modifica location
                        if (target.length === 0) {
                            var locationId = data
                                .id; // Assicurati che 'data.id' contenga l'ID della location
                            var editUrl = '{{ route('coupon.edit', ':id') }}';
                            editUrl = editUrl.replace(':id', locationId);
                            window.location.href = editUrl;
                        } else {
                            // Se il target è un pulsante, gestisci l'azione specifica
                            var action = target.data('action');
                            console.log(action);

                            if (action === 'getclient') {
                                // Esegui le operazioni per ottenere i dati del cliente
                                var customer_email = data.customer_email;
                                var id = data.id;
                                console.log('customer_email: ' + customer_email);
                                populateModalWithData(id);
                            } else if (action === 'regenerate') {
                                // Mostra lo spinner
                                var spinner = $(
                                    '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
                                );
                                target.after(spinner);
                                target.hide();

                                // Chiamata Axios per rigenerare il coupon
                                var reservationId = target.data('id');
                                axios.post('/regenerate-voucher', {
                                        reservation_id: reservationId
                                    })
                                    .then(function(response) {
                                        console.log(response.data);
                                        // Nascondi lo spinner
                                        spinner.remove();

                                        // Mostra il link al PDF
                                        var pdfLink = $('<a></a>')
                                            .attr('href', '/vouchers/' + response.data
                                                .fileName)
                                            .attr('target', '_blank')
                                            .html(
                                                '<i class="fas fa-file-pdf text-center">Voucher</i>'
                                            );

                                        target.after(pdfLink);
                                    })
                                    .catch(function(error) {
                                        console.error(error);
                                        // Gestisci l'errore
                                        alert('Errore durante la generazione del PDF');

                                        // Nascondi lo spinner e mostra di nuovo il pulsante in caso di errore
                                        spinner.remove();
                                        target.show();
                                    });
                            } else if (action === 'delete') {
                                // Logica per eliminare il coupon
                                console.log('Elimina Coupon');
                                // Aggiungi qui la tua funzione per eliminare il coupon
                            }
                        }

                    });
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json',
                },
                stripeClasses: ['bg-light', 'bg-white'],
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('getreservationsarriving') }}",
                    data: function(d) {
                        d.location_id = $('#location').val()
                    }
                },

                columns: [{
                        data: 'coupon_code',
                        name: 'coupon_code',
                        orderable: true
                    },
                    {
                        data: 'nome_activity',
                        name: 'nome_activity',
                        orderable: true
                    },
                    {
                        data: 'acquirente',
                        name: 'acquirente',
                        orderable: true

                    },
                    {
                        data: 'nome_cliente',
                        name: 'nome_cliente',
                        orderable: false,
                        visible: false
                    },
                    {
                        data: 'cognome_cliente',
                        name: 'cognome_cliente',
                        orderable: false,
                        visible: false
                    },
                    {
                        data: 'beneficiario',
                        name: 'beneficiario',
                        orderable: true
                    },
                    {
                        data: 'nome_beneficiario',
                        name: 'nome_beneficiario',
                        orderable: false,
                        visible: false
                    },
                    {
                        data: 'cognome_beneficiario',
                        name: 'cognome_beneficiario',
                        orderable: false,
                        visible: false
                    },
                    {
                        data: 'databooking',
                        name: 'databooking',
                        orderable: true
                    },
                    {
                        data: 'orabooking',
                        name: 'orabooking',
                        orderable: false
                    },
                    {
                        data: 'n_tavolo',
                        name: 'n_tavolo',
                        orderable: true
                    },
                    {
                        data: 'n_camera',
                        name: 'n_camera',
                        orderable: true
                    },
                    {
                        data: 'data_scadenza',
                        name: 'data_scadenza',
                        orderable: true
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        orderable: true
                    },
                    {
                        data: 'voucher',
                        name: 'voucher',
                        orderable: false
                    },

                ],

            });
            // Gestisci l'evento di cambio della selezione della location
            $('#location').change(function() {
                // Ricarica i dati della DataTable con la nuova location
                table.ajax.reload();
            });

        });

        function populateModalWithData(ipratico_client_id) {
            // Esegui la chiamata API
            $.ajax({
                url: '/api/getIpraticoClientData/' + ipratico_client_id,
                method: 'GET',
                success: function(response) {
                    // Popola la modal con i dati ottenuti dalla chiamata API

                    var modalContent = `
    <!--Modal ipratico dati cliente -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dati di ${response.client.name} ${response.client.surname}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
`;

                    // Aggiungi ogni valore del client al contenuto della modal
                    for (var key in response.client) {
                        if (response.client.hasOwnProperty(key)) {
                            modalContent += `<p>${key}: ${response.client[key]}</p>`;
                        }
                    }

                    modalContent += `
                </div>
                <div class="stokazzo"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>
`;



                    // Rimuovi la modal esistente, se presente
                    $('#myModal').remove();

                    // Aggiungi la modal al corpo del documento
                    $('body').append(modalContent);

                    // Apri la modal
                    $('#myModal').modal('show');
                },
                error: function(xhr, status, error) {
                    // Gestisci eventuali errori
                    console.error(error);
                    alert('Si è verificato un errore durante il recupero dei dati.');
                }
            });
        }
    </script>

@endsection
