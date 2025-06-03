@extends('layouts.app')

@section('title', 'Simbiosis Admin Dashboard')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h1 class="mb-4">Welcome to the Simbiosis Admin Dashboard</h1>
                    <p class="lead">Laravel is installed and working!<br>Database connection and sessions are working.<br><strong>{{ date('Y-m-d H:i:s') }}</strong></p>
                    <div class="alert alert-success mt-4">
                        <strong>Next step:</strong> Add your first admin feature!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
