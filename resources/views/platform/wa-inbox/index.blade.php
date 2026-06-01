@extends('layouts.platform')

@section('title', 'WA Inbox')
@section('page-title', 'WhatsApp Inbox')
@section('page-subtitle', 'Two-way conversations with hotel owners')

@section('content')
<div id="wa-inbox-root" style="min-height:500px;"></div>
@endsection

@push('scripts')
@vite('resources/js/platform-wa-inbox.jsx')
@endpush
