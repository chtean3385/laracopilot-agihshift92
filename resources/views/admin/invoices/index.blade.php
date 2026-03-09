@extends('layouts.admin')
@section('title','Invoices')
@section('page-title','Invoices')
@section('page-subtitle','All generated invoices')

@section('content')
<livewire:invoice-search />
@endsection
