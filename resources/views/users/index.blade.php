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
            <a href="{{ route('user.create') }}" class="btn btn-primary">Crea Nuovo Utente</a>
        </div>
        <table class="table table-bordered yajra-datatable">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Login</th>
                    <th>Ruolo</th>

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
                    $(row).on('click', function() {
                        window.location.href = '/user/' + data.id +
                            '/edit'; // Sostituisci con il percorso corretto
                    });
                },
                stripeClasses: ['bg-light', 'bg-white'],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json',
                },

                processing: true,
                serverSide: true,

                ajax: {
                    url: "{{ route('getusers') }}",
                },
                columns: [{
                        data: 'name',
                        name: 'name',
                        orderable: true
                    },
                    {
                        data: 'cognome',
                        name: 'cognome',
                        orderable: true
                    },
                    {
                        data: 'email',
                        name: 'email',
                        orderable: true

                    },
                    {
                        data: 'ruolo',
                        name: 'ruolo',
                        orderable: false
                    },


                ],

            });


        });
    </script>
@endsection
