@extends('adminlte::page')

@section('title', 'Branch Report - ' . $category->title)

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-1">
            <div class="col-sm-6">
                <h1 class="m-0">Branch Report: {{ $category->title }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('category.index') }}">Branches</a></li>
                    <li class="breadcrumb-item active">Report</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <strong>{{ session('success') }}</strong>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Appointments for {{ $category->title }}</h3>
                <button type="button" class="btn btn-sm btn-default no-print" onclick="window.print();">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Appointment Date</th>
                            <th>Appointment Time</th>
                            <th>SPID</th>
                            <th>Sample Person Name</th>
                            <th>Mobile Number</th>
                            <th>Interviewer ID</th>
                            <th>Supervisor ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $a)
                            <tr>
                                <td>{{ $category->title }}</td>
                                <td>{{ $a->booking_date ? \Carbon\Carbon::parse($a->booking_date)->format('d M Y') : 'N/A' }}</td>
                                <td>{{ $a->booking_time ?? 'N/A' }}</td>
                                <td>{{ $a->spid ?? '—' }}</td>
                                <td>{{ $a->sample_person_name ?? $a->name ?? '—' }}</td>
                                <td>{{ $a->mobile_number ?? $a->phone ?? '—' }}</td>
                                <td>{{ $a->interviewer_id ?? '—' }}</td>
                                <td>{{ $a->supervisor_id ?? '—' }}</td>
                                <td><span class="badge badge-secondary">{{ $a->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">No appointments for this branch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        @media print {
            .no-print, .sidebar, .navbar, .main-footer { display: none !important; }
        }
    </style>
@stop
