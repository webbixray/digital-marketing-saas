@extends('layouts.unified')
@section('title', 'Edit Template')

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push("scripts")
<script>
    async function previewTemplate() {
        try {
            const response = await dmsaas.request('{{ route(email.templates.preview, ) }}', {
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
