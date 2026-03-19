<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Nome</label>
        <input type="text" class="form-control" name="name{{ $lang }}"
            value="{{ old("name$lang", $activity->{'name' . $lang} ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">SKU</label>
        <input type="text" class="form-control @error('sku') is-invalid @enderror" name="sku"
            value="{{ old('sku', $activity->sku ?? '') }}" @if ($lang !== '') disabled @endif>
        {{-- SKU modificabile solo su lingua principale --}}

        @error('sku')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="mb-3">
    <label class="form-label">Descrizione</label>
    <textarea class="form-control" name="description{{ $lang }}" rows="2">{{ old("description$lang", $activity->{'description' . $lang} ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Dettagli</label>
    <textarea class="form-control" name="details{{ $lang }}" rows="2">{{ old("details$lang", $activity->{'details' . $lang} ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Note</label>
    <textarea class="form-control" name="note{{ $lang }}" rows="2">{{ old("note$lang", $activity->{'note' . $lang} ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Prenotare</label>
    <textarea class="form-control" name="prenotare{{ $lang }}" rows="2">{{ old("prenotare$lang", $activity->{'prenotare' . $lang} ?? '') }}</textarea>
</div>
