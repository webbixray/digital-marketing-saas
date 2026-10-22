@extends('layouts.unified')
@section('title', 'Edit Template')

@section('content')
<x-flash-messages />
<div class="space-y-6">
</div>
@endsection

@push("scripts")
<script nonce="{{ $cspNonce ?? '' }}">
    async function previewTemplate() {
        try {
            const response = await dmsaas.request('{{ route("email.templates.preview", $template) }}', {
                method: 'POST',
                body: JSON.stringify({ variables: {} }),
            });
            const data = await response.json();
            document.getElementById('previewContent').innerHTML = data.html;
        } catch (err) {
            dmsaas.toast('Failed to load preview.', 'error');
        }
    }
</script>
@endpush
