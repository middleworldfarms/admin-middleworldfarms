@extends('layouts.app')

@section('title', 'Simple Gantt Chart')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Simple Gantt Chart View</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6>Redirecting to Enhanced Planting Chart</h6>
                        <p>The simple Gantt chart has been superseded by our enhanced Planting Chart with better functionality.</p>
                        <a href="{{ route('admin.farmos.planting-chart') }}" class="btn btn-primary">
                            <i class="fas fa-seedling"></i> Go to Planting Chart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-redirect after 3 seconds
setTimeout(function() {
    window.location.href = '{{ route('admin.farmos.planting-chart') }}';
}, 3000);
</script>
@endsection
