<?php
namespace Tests\Feature;

use App\Models\{Appointment, Doctor, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsultRoomTest extends TestCase
{
    use RefreshDatabase;

    private User $patient, $other, $admin;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-10 10:00:00'));
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $this->doctor = Doctor::with('user')->first();
        $this->patient = User::create(['name' => 'Pat One', 'email' => 'p1@t.test', 'password' => 'x', 'role' => 'patient']);
        $this->other = User::create(['name' => 'Pat Two', 'email' => 'p2@t.test', 'password' => 'x', 'role' => 'patient']);
        $this->admin = User::where('role', 'admin')->first() ?? User::create(['name' => 'Adm', 'email' => 'a@t.test', 'password' => 'x', 'role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function visit(string $at = '10:30', string $status = 'confirmed', string $mode = 'Online'): Appointment
    {
        return Appointment::create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id, 'date' => '2026-10-10',
            'time' => $at, 'status' => $status, 'mode' => $mode]);
    }

    public function test_only_patient_and_doctor_of_the_visit_get_in(): void
    {
        $a = $this->visit('10:05');
        $this->get(route('consult.show', $a))->assertRedirect(route('login'));
        $this->actingAs($this->other)->get(route('consult.show', $a))->assertForbidden();
        $this->actingAs($this->admin)->get(route('consult.show', $a))->assertForbidden();
        $this->actingAs($this->other)->getJson(route('consult.state', $a))->assertForbidden();
        $this->actingAs($this->patient)->get(route('consult.show', $a))->assertOk();
        $this->actingAs($this->doctor->user)->get(route('consult.show', $a))->assertOk();
        // another doctor
        $d2 = Doctor::with('user')->where('id', '!=', $this->doctor->id)->first();
        $this->actingAs($d2->user)->get(route('consult.show', $a))->assertForbidden();
    }

    public function test_room_opens_ten_minutes_before_and_closes_when_the_visit_ends(): void
    {
        $a = $this->visit('10:30'); // slot 20 min -> ends 10:50
        $this->actingAs($this->patient);
        $this->assertSame('early', $a->consultPhase());
        $this->get(route('consult.show', $a))->assertOk()->assertSee('Not started yet');

        Carbon::setTestNow('2026-10-10 10:19:59');
        $this->assertSame('early', $a->fresh()->consultPhase());
        Carbon::setTestNow('2026-10-10 10:20:00');
        $this->assertSame('waiting', $a->fresh()->consultPhase());
        $this->get(route('consult.show', $a))->assertSee('Waiting for the doctor');

        Carbon::setTestNow('2026-10-10 10:50:00');
        $this->assertSame('ended', $a->fresh()->consultPhase());
        $this->get(route('consult.show', $a))->assertSee('Visit ended');
    }

    public function test_pending_visit_has_no_room(): void
    {
        $a = $this->visit('10:05', 'pending');
        $this->actingAs($this->patient)->get(route('consult.show', $a))->assertSee('Not confirmed yet');
    }

    public function test_full_flow_start_join_in_progress_complete(): void
    {
        $a = $this->visit('10:05');

        // patient cannot start; doctor can
        $this->actingAs($this->patient)->post(route('consult.start', $a))->assertForbidden();
        $this->actingAs($this->doctor->user)->get(route('consult.show', $a))->assertSee('Start consultation');
        $this->actingAs($this->doctor->user)->post(route('consult.start', $a))->assertRedirect(route('consult.show', $a));
        $a->refresh();
        $this->assertNotNull($a->consult_started_at);
        $this->assertSame('confirmed', $a->status); // not "in progress" until the patient is in

        // doctor sees the video; still confirmed
        $this->actingAs($this->doctor->user)->get(route('consult.show', $a))->assertSee('<iframe', false)->assertSee($a->jitsiRoom());
        $this->assertSame('confirmed', $a->fresh()->status);

        // patient joins -> In progress
        $this->actingAs($this->patient)->get(route('consult.show', $a))->assertSee('<iframe', false)->assertSee('In progress');
        $a->refresh();
        $this->assertSame('in_progress', $a->status);
        $this->assertNotNull($a->consult_joined_at);

        // slot is over but the visit is running: the room stays open until the doctor completes
        Carbon::setTestNow('2026-10-10 10:40:00');
        $this->assertSame('live', $a->fresh()->consultPhase());
        $this->actingAs($this->patient)->getJson(route('consult.state', $a))->assertJson(['phase' => 'live', 'status' => 'in_progress']);

        // patient cannot complete; doctor can
        $this->actingAs($this->patient)->post(route('consult.complete', $a))->assertForbidden();
        $this->actingAs($this->doctor->user)->post(route('consult.complete', $a))->assertRedirect(route('doctor.appointments'));
        $this->assertSame('completed', $a->fresh()->status);
        $this->actingAs($this->patient)->get(route('consult.show', $a))->assertSee('Visit ended')->assertDontSee('<iframe', false);
    }

    public function test_doctor_cannot_start_outside_the_window(): void
    {
        $a = $this->visit('11:30');
        $this->actingAs($this->doctor->user)->post(route('consult.start', $a))->assertSessionHasErrors('consult');
        $this->assertNull($a->fresh()->consult_started_at);
    }

    public function test_in_person_visit_has_no_room(): void
    {
        $a = $this->visit('10:05', 'confirmed', 'In-person');
        $this->actingAs($this->patient)->get(route('consult.show', $a))->assertNotFound();
    }

    public function test_dashboard_buttons_follow_the_window(): void
    {
        $early = $this->visit('10:30');
        $html = view('partials.consult-button', ['a' => $early, 'who' => 'patient'])->render();
        $this->assertStringContainsString('disabled', $html);
        Carbon::setTestNow('2026-10-10 10:21:00');
        $html = view('partials.consult-button', ['a' => $early->fresh(), 'who' => 'patient'])->render();
        $this->assertStringContainsString(route('consult.show', $early), $html);
        Carbon::setTestNow('2026-10-10 11:00:00');
        $this->assertSame('', trim(view('partials.consult-button', ['a' => $early->fresh(), 'who' => 'patient'])->render()));
    }

    public function test_confirming_creates_the_room_link_and_pages_render(): void
    {
        $a = $this->visit('15:00', 'pending');
        $a->changeStatus('confirmed', 'doctor');
        $this->assertSame(route('consult.show', $a), $a->fresh()->meeting_link);

        $this->actingAs($this->patient)->get(route('patient.appointments'))->assertOk();
        $this->actingAs($this->patient)->get(route('patient.dashboard'))->assertOk();
        $this->actingAs($this->doctor->user)->get(route('doctor.appointments'))->assertOk()->assertSee('Start consultation', false);
    }
}
