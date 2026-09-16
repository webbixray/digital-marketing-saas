@extends('layouts.unified')
@section('title', $template->name)

@section('content')
<div class="space-y-6">
<div class="content-wrapper">
    
    </div>
    <div class="content">
        
            <div class="grid grid-cols-12 gap-4>
                <div class="col-span-12 md:col-span-8">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <div class="p-6">
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
                                <tr><th>Subject</th><td>{{ $template->subject }}</td></tr>
                                <tr><th>Category</th><td>{{ ucfirst($template->category) }}</td></tr>
                            </table></div>
                            <h5>HTML Content</h5>
                            <div class="card bg-light"><div class="p-6">{{ $template->html_content }}
                            @if($template->plain_text_content)
                                <h5 class="mt-3">Plain Text</h5>
                                <div class="card bg-light"><div class="p-6">{{ $template->plain_text_content }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

