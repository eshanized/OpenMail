@extends('layouts.app')

@section('title', 'Mailbox')

@section('content')
    <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900">Welcome, {{ auth()->user()->name }}!</h2>
        <p class="mt-4 text-lg text-gray-600">Your mailbox will appear here in Phase 2.</p>
    </div>
@endsection