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
        <div class="form-group mb-4">
            <a href="{{ route('locations.create') }}" class="btn btn-primary">Crea Nuova Location</a>
        </div>
        <table class="table table-bordered yajra-datatable">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Telefono</th>
                    <th>Utente Mail</th>
                    <th>Logo</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>




    <script type="text/javascript">
        $(function() {

            var table = $('.yajra-datatable').DataTable({
                "rowCallback": function(row, data) {
                    $(row).css('cursor', 'pointer');
                    $(row).on('click', function(event) {
                        var locationId = data
                        .id; // Assicurati che 'data.id' contenga l'ID della location
                        var editUrl = '{{ route('locations.edit', ':id') }}';
                        editUrl = editUrl.replace(':id', locationId);
                        window.location.href = editUrl;
                    });
                },
                stripeClasses: ['bg-light', 'bg-white'],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json',
                },

                processing: true,
                serverSide: true,

                ajax: {
                    url: "{{ route('getlocations') }}",
                    data: function(d) {
                        d.location_id = $('#location').val()
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name',
                        orderable: true
                    },
                    {
                        data: 'telefono',
                        name: 'telefono',
                        orderable: true
                    },
                    {
                        data: 'utente_mail',
                        name: 'utente_mail',
                        orderable: true

                    },
                    {
                        data: 'logo',
                        name: 'logo',
                        orderable: false,
                        visible: true
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
