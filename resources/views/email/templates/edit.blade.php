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
        const response = await fetch('{{ route("email.templates.preview", $template) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ variables: {} }),
        });
        const data = await response.json();
        document.getElementById('previewContent').innerHTML = data.html;
    }
</script>
@endpush
