@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Help Center</h1>

    <div class="accordion" id="faqAccordion">
        @forelse ($faqs as $faq)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $faq->id }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $faq->id }}" aria-expanded="false" aria-controls="collapse{{ $faq->id }}">
                        {{ $faq->question }}
                        @if($faq->category)
                            <span class="badge bg-secondary ms-2">{{ $faq->category }}</span>
                        @endif
                    </button>
                </h2>
                <div id="collapse{{ $faq->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $faq->id }}" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        {!! nl2br(e($faq->answer)) !!}
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No FAQs available yet.</div>
        @endforelse
    </div>
</div>
@endsection
