@extends('adminlte::page')

@section('title', 'All Appointments')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>All Apointments</h1>
        </div>

    </div>
@stop

@section('content')
    <!-- Modal -->
    <form id="appointmentStatusForm" method="POST" action="{{ route('appointments.update.status') }}">
        @csrf
        <input type="hidden" name="appointment_id" id="modalAppointmentId">

        <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Appointment Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p><strong>Client:</strong> <span id="modalAppointmentName">N/A</span></p>
                        <p><strong>SPID:</strong> <span id="modalSpid">N/A</span></p>
                        <p><strong>Service:</strong> <span id="modalService">N/A</span></p>
                        <p><strong>Phone / Mobile:</strong> <span id="modalPhone">N/A</span></p>
                        <p><strong>Staff:</strong> <span id="modalStaff">N/A</span></p>
                        <p><strong>Start:</strong> <span id="modalStartTime">N/A</span></p>
                        <p><strong>Interviewer ID:</strong> <span id="modalInterviewerId">N/A</span></p>
                        <p><strong>Supervisor ID:</strong> <span id="modalSupervisorId">N/A</span></p>
                        <p><strong>Visit Stage:</strong> <span id="modalVisitStage">N/A</span></p>
                        <p><strong>Branch:</strong> <span id="modalBranch">N/A</span></p>
                        <p><strong>Notes:</strong> <span id="modalNotes">N/A</span></p>
                        <p><strong>Current Status:</strong> <span id="modalStatusBadge">N/A</span></p>


                        <div class="form-group ">
                            <label><strong>Status:</strong></label>
                            <select name="status" class="form-control" id="modalStatusSelect">
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Completed">Completed</option>
                                <option value="On Hold">On Hold</option>
                                <option value="No Show">No Show</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        @if(!auth()->user()->hasRole('view_only'))
                        <button type="submit" onclick="return confirm('Are you sure you want to update booking status?')"
                            class="btn btn-danger">Update Status</button>
                        @endif
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>
    </form>
    <div class="">
        @if (session('success'))
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>{{ session('success') }}</strong>
            </div>
        @endif
        <!-- Content Header (Page header) -->
        <!-- Content Header (Page header) -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card py-2 px-2">

                            <div class="card-body p-0">
                                <table id="myTable" class="table table-striped projects ">
                                    <thead>
                                        <tr>
                                            <th style="width: 1%">#</th>
                                            <th style="width: 10%">Name</th>
                                            <th style="width: 8%">SPID</th>
                                            <th style="width: 8%">Phone</th>
                                            <th style="width: 8%">Staff</th>
                                            <th style="width: 10%">Service</th>
                                            <th style="width: 8%">Branch</th>
                                            <th style="width: 8%">Visit</th>
                                            <th style="width: 8%">Date</th>
                                            <th style="width: 8%">Time</th>
                                            <th style="width: 10%" class="text-center">Status</th>
                                            <th style="width: 15%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $statusColors = [
                                                'Pending' => '#f39c12',
                                                'Processing' => '#3498db',
                                                'Confirmed' => '#2ecc71',
                                                'Cancelled' => '#ff0000',
                                                'Completed' => '#008000',
                                                'On Hold' => '#95a5a6',
                                                'Rescheduled' => '#f1c40f',
                                                'No Show' => '#e67e22',
                                            ];
                                        @endphp
                                        @foreach ($appointments as $appointment)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <a>{{ $appointment->sample_person_name ?? $appointment->name }}</a>
                                                    <br><small>{{ $appointment->created_at->format('d M Y') }}</small>
                                                </td>
                                                <td>{{ $appointment->spid ?? '—' }}</td>
                                                <td>{{ $appointment->mobile_number ?? $appointment->phone }}</td>
                                                <td>{{ $appointment->employee->user->name }}</td>
                                                <td>{{ $appointment->service->title ?? 'NA' }}</td>
                                                <td>{{ $appointment->branch->title ?? '—' }}</td>
                                                <td>
                                                    @if($appointment->visit_stage)
                                                        {{ str_replace('_', ' ', ucfirst($appointment->visit_stage)) }}
                                                    @else —
                                                    @endif
                                                </td>
                                                <td>{{ $appointment->booking_date }}</td>
                                                <td>{{ $appointment->booking_time }}</td>
                                                <td>
                                                    @php
                                                        $status = $appointment->status;
                                                        $color = $statusColors[$status] ?? '#7f8c8d';
                                                    @endphp
                                                    <span class="badge px-2 py-1"
                                                        style="background-color: {{ $color }}; color: white;">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm py-0 px-1 view-appointment-btn"
                                                        data-toggle="modal" data-target="#appointmentModal"
                                                        data-id="{{ $appointment->id }}"
                                                        data-name="{{ $appointment->sample_person_name ?? $appointment->name }}"
                                                        data-spid="{{ $appointment->spid }}"
                                                        data-service="{{ $appointment->service->title ?? 'MA' }}"
                                                        data-phone="{{ $appointment->mobile_number ?? $appointment->phone }}"
                                                        data-employee="{{ $appointment->employee->user->name }}"
                                                        data-start="{{ $appointment->booking_date . ' ' . $appointment->booking_time }}"
                                                        data-notes="{{ $appointment->notes }}"
                                                        data-status="{{ $appointment->status }}"
                                                        data-interviewer-id="{{ $appointment->interviewer_id }}"
                                                        data-supervisor-id="{{ $appointment->supervisor_id }}"
                                                        data-visit-stage="{{ $appointment->visit_stage }}"
                                                        data-branch="{{ $appointment->branch->title ?? '—' }}">View</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>
                            <!-- /.card-body -->
                        </div>
                    </div>
                    <!-- /.col -->

                </div>
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
    </div>
@stop

@section('css')

@stop

@section('js')

    {{-- hide notifcation --}}
    <script>
        $(document).ready(function() {
            $(".alert").delay(6000).slideUp(300);
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                responsive: true
            });

        });
    </script>



    <script>
        $(document).on('click', '.view-appointment-btn', function() {
            $('#modalAppointmentId').val($(this).data('id'));
            $('#modalAppointmentName').text($(this).data('name'));
            $('#modalSpid').text($(this).data('spid') || '—');
            $('#modalService').text($(this).data('service'));
            $('#modalPhone').text($(this).data('phone') || '—');
            $('#modalStaff').text($(this).data('employee'));
            $('#modalStartTime').text($(this).data('start'));
            $('#modalNotes').text($(this).data('notes') || '—');
            $('#modalInterviewerId').text($(this).data('interviewer-id') || '—');
            $('#modalSupervisorId').text($(this).data('supervisor-id') || '—');
            $('#modalVisitStage').text($(this).data('visit-stage') ? $(this).data('visit-stage').replace('_', ' ') : '—');
            $('#modalBranch').text($(this).data('branch') || '—');

            var status = $(this).data('status');
            $('#modalStatusSelect').val(status);

            var statusColors = {
                'Pending': '#f39c12',
                'Pending': '#f39c12',
                'Processing': '#3498db',
                'Confirmed': '#2ecc71',
                'Cancelled': '#ff0000',
                'Completed': '#008000',
                'On Hold': '#95a5a6',
                'Rescheduled': '#f1c40f',
                'No Show': '#e67e22',
            };

            var badgeColor = statusColors[status] || '#7f8c8d';
            $('#modalStatusBadge').html(
                `<span class="badge px-2 py-1" style="background-color: ${badgeColor}; color: white;">${status}</span>`
            );
        });
    </script>
@endsection
