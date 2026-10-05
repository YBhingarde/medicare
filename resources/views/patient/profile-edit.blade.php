@extends('layouts.admin')
@section('title', 'Edit profile')
@section('content')
    @php $v = fn ($f, $d = '') => old($f, $profile->$f ?? $d); @endphp
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('patient.profile') }}" class="text-decoration-none text-secondary"><i class="bi bi-chevron-left"></i>
            My profile</a>
    </div>
    <h1 class="page-title mb-3">Edit profile</h1>
    <form method="post" enctype="multipart/form-data" action="{{ route('patient.profile.update') }}">@csrf @method('PUT')

        <div class="ad-sec">
            <h2>Personal information</h2>
            <div class="ad-photo-up mb-3 d-flex align-items-center gap-3">
                <div class="pp-photo" style="width:72px;height:72px">
                    @if ($profile->photo_url)
                    <img src="{{ $profile->photo_url }}" alt="">@else<span>{{ $user->initials }}</span>
                    @endif
                </div>
                <div><label class="form-label mb-1">Profile photo</label><input type="file" name="photo"
                        accept="image/*" class="form-control form-control-sm">
                    <div class="small text-secondary mt-1">JPG or PNG, up to 2 MB.</div>
                    @error('photo')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Name *</label><input name="name"
                        value="{{ old('name', $user->name) }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email"
                        value="{{ old('email', $user->email) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Phone</label><input name="phone"
                        value="{{ old('phone', $user->phone) }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Date of birth</label><input type="date" name="dob"
                        value="{{ old('dob', $profile->dob?->format('Y-m-d')) }}"
                        max="{{ now()->subDay()->toDateString() }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Gender</label><select name="gender" class="form-select">
                        <option value="">Select</option>
                        @foreach (['Male', 'Female', 'Other'] as $g)
                            <option @selected($v('gender') === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Blood group</label><select name="blood_group"
                        class="form-select">
                        <option value="">Select</option>
                        @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g)
                            <option @selected($v('blood_group') === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="ad-sec">
            <h2>Address</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Address</label><input name="address"
                        value="{{ $v('address') }}" class="form-control" placeholder="House no., street, area"></div>
                <div class="col-md-4"><label class="form-label">City</label><input name="city"
                        value="{{ $v('city') }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">Pincode</label><input name="pincode"
                        value="{{ $v('pincode') }}" class="form-control"></div>
            </div>
        </div>

        <div class="ad-sec">
            <h2>Doctor &amp; emergency</h2>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Preferred doctor</label>
                    <select name="preferred_doctor_id" class="form-select">
                        <option value="">None</option>
                        @foreach ($doctors as $d)
                            <option value="{{ $d->id }}" @selected($v('preferred_doctor_id') == $d->id)>{{ $d->user->name }} -
                                {{ $d->speciality->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Emergency contact name</label><input name="emergency_name"
                        value="{{ $v('emergency_name') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Emergency contact phone</label><input name="emergency_phone"
                        value="{{ $v('emergency_phone') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Allergies</label>
                    <textarea name="allergies" rows="2" class="form-control" placeholder="e.g. Penicillin, peanuts">{{ $v('allergies') }}</textarea>
                </div>
                <div class="col-md-6"><label class="form-label">Medical notes</label>
                    <textarea name="medical_notes" rows="2" class="form-control"
                        placeholder="Long-term conditions, current medicines...">{{ $v('medical_notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="ad-sec">
            <h2>Vital signs <span class="small text-secondary fw-normal">(self-reported)</span></h2>
            <div class="row g-3">
                <div class="col-6 col-md-2"><label class="form-label">Blood pressure</label><input name="bp"
                        value="{{ $v('bp') }}" placeholder="120/80" class="form-control"></div>
                <div class="col-6 col-md-2"><label class="form-label">Heart rate (bpm)</label><input type="number"
                        name="heart_rate" value="{{ $v('heart_rate') }}" class="form-control"></div>
                <div class="col-6 col-md-2"><label class="form-label">SPO2 (%)</label><input type="number"
                        name="spo2" value="{{ $v('spo2') }}" class="form-control"></div>
                <div class="col-6 col-md-2"><label class="form-label">Temperature (&deg;C)</label><input type="number"
                        step="0.1" name="temperature" value="{{ $v('temperature') }}" class="form-control"></div>
                <div class="col-6 col-md-2"><label class="form-label">Resp. rate (rpm)</label><input type="number"
                        name="respiratory_rate" value="{{ $v('respiratory_rate') }}" class="form-control"></div>
                <div class="col-6 col-md-2"><label class="form-label">Weight (kg)</label><input type="number"
                        step="0.1" name="weight" value="{{ $v('weight') }}" class="form-control"></div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save
                profile</button><a class="btn btn-light" href="{{ route('patient.profile') }}">Cancel</a></div>
    </form>
@endsection
