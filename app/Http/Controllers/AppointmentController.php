<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Events\BookingCreated;
use App\Events\StatusUpdated;


class AppointmentController extends Controller
{

    public function index()
    {
        $user = auth()->user();
        if ($user->hasRole('employee')) {
            $appointments = Appointment::with(['employee.user', 'service', 'branch'])->where('created_by_id', $user->id)->latest()->get();
        } elseif ($user->hasRole('view_only')) {
            $appointments = Appointment::with(['employee.user', 'service', 'branch'])->latest()->get();
        } else {
            $appointments = Appointment::with(['employee.user', 'service', 'branch'])->latest()->get();
        }
        return view('backend.appointment.index', compact('appointments'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_id' => 'required|exists:employees,id',
            'service_id' => 'required|exists:services,id',
            'spid' => 'required|string|digits:10',
            'sample_person_name' => 'required|string|max:255',
            'mobile_number' => ['required', 'regex:/^\d{10,15}$/'],
            'interviewer_id' => 'required|string|max:100',
            'supervisor_id' => 'required|string|max:100',
            'visit_stage' => 'required|in:first_visit,second_visit,third_visit',
            'phone' => ['nullable', 'regex:/^\d{10,15}$/'],
            'notes' => 'nullable|string',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            'status' => 'required|string',
        ], [
            'mobile_number.regex' => 'Mobile number must contain only digits and be between 10 and 15 digits.',
            'phone.regex' => 'Phone number must contain only digits and be between 10 and 15 digits.',
        ]);

        $service = \App\Models\Service::with('category')->findOrFail($validated['service_id']);
        $branch = $service->category;

        $validated['branch_id'] = $branch->id;
        $validated['branch_address_snapshot'] = $branch->address;
        $validated['branch_map_link_snapshot'] = $branch->map_link;
        $validated['name'] = $validated['sample_person_name'];
        $validated['booking_id'] = 'BK-' . strtoupper(uniqid());

        $isPrivilegedRole = auth()->check() && (
            auth()->user()->hasRole('admin') ||
            auth()->user()->hasRole('subscriber') ||
            auth()->user()->hasRole('employee')
        );

        if ($isPrivilegedRole) {
            $validated['user_id'] = null;
            if (auth()->user()->hasRole('employee')) {
                $validated['created_by_id'] = auth()->id();
            } else {
                $validated['created_by_id'] = null;
            }
        } elseif (auth()->check() && !$request->has('user_id')) {
            $validated['user_id'] = auth()->id();
            $validated['created_by_id'] = auth()->id();
        } else {
            $validated['created_by_id'] = null;
        }

        $appointment = Appointment::create($validated);

        event(new BookingCreated($appointment));

        return response()->json([
            'success' => true,
            'message' => 'Appointment booked successfully!',
            'booking_id' => $appointment->booking_id,
            'appointment' => $appointment
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Appointment $appointment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        //
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'status' => 'required|string',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);

        if (auth()->user()->hasRole('view_only')) {
            return redirect()->back()->withErrors('You do not have permission to update appointment status.');
        }
        if (auth()->user()->hasRole('employee') && $appointment->created_by_id !== auth()->id()) {
            return redirect()->back()->withErrors('You can only update appointments you created.');
        }

        $appointment->status = $request->status;
        $appointment->save();

        event(new StatusUpdated($appointment));

        return redirect()->back()->with('success', 'Appointment status updated successfully.');
    }

}
