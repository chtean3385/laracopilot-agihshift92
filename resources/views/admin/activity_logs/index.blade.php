@extends('layouts.admin')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')
@section('page-subtitle', 'Track every action performed in the CRM')

@section('content')
<livewire:activity-log-search />
@endsection
