{{-- View + PDF download buttons for a prescription. expects $a (Appointment with a prescription) --}}
<a class="btn btn-sm btn-outline-primary" href="{{ route('prescription.show', $a) }}"><i
        class="bi bi-file-earmark-medical"></i> View</a>
<a class="btn btn-sm btn-primary" href="{{ route('prescription.pdf', $a) }}" title="Download PDF"><i
        class="bi bi-file-earmark-pdf"></i> PDF</a>
