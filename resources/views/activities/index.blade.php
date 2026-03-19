@extends('layouts.app_admin')

@section('content')
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>{{ session('success') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Prodotto</h2>
            <a href="{{ route('activities.create') }}" class="btn btn-success">
                Nuovo Prodotto
            </a>
        </div>
        @if ($is_admin)
            <div class="mb-3">
                <select id="location" class="form-select" style="width: 200px;">
                    <option value="">Tutte le Location</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <table class="table table-bordered table-striped yajra-datatable">

            <thead>
                <tr>
                    <th style="width: 250px;">Nome</th>

                    <th>Location</th>
                    <th>SKU</th>
                    <th>Valore</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
   

    
   
    <script>
        $(function() {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json',
                },
                ajax: {
                    url: '{{ route('getactivities') }}', // Rotta da definire
                    data: function(d) {
                        d.location_id = $('#location').val();
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'location_name',
                        name: 'location.name'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'product_value',
                        name: 'product_value'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#location').change(function() {
                table.ajax.reload();
            });
        });
    </script>
   <!-- Form nascosto per DELETE -->
<form id="delete-activity-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // delega per pulsanti anche se caricati da datatable
    document.body.addEventListener('click', function (e) {
        if (e.target.matches('.btn-delete-activity')) {
            const button = e.target;
            const activityId = button.dataset.id;
            const activityName = button.dataset.name;

            Swal.fire({
                title: 'Sei sicuro?',
                text: `Vuoi eliminare l'attività "${activityName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sì, elimina',
                cancelButtonText: 'Annulla'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-activity-form');
                    form.action = `/activities/${activityId}`;
                    form.submit();
                }
            });
        }
    });
});
</script>

@endsection
