@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
    <div class="card card-p3 p-4">
        <h4 class="fw-bold mb-3"><i class="bi bi-hdd me-1" style="color:#00A868"></i>Storage usage</h4>
        <p class="small text-muted">Total stored: {{ number_format($total / 1024 / 1024, 2) }} MB</p>
        <table class="table table-sm">
            <thead><tr><th>Department</th><th>Documents</th><th>Size</th></tr></thead>
            <tbody>
            @foreach($usage as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->document_count }}</td>
                    <td>{{ number_format($row->total_bytes / 1024 / 1024, 2) }} MB</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection


