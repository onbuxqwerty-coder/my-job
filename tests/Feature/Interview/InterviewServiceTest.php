<?php

declare(strict_types=1);

namespace Tests\Feature\Interview;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewRequestStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\InterviewRequest;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\AsyncInterviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InterviewServiceTest extends TestCase
{
    use RefreshDatabase;

    private AsyncInterviewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AsyncInterviewService::class);
    }

    private function makeApplication(): Application
    {
        $employer  = User::factory()->employer()->create();
        $company   = Company::factory()->create(['user_id' => $employer->id]);
        $vacancy   = Vacancy::factory()->active()->create(['company_id' => $company->id]);
        $candidate = User::factory()->create(['role' => UserRole::Candidate]);

        return Application::factory()->create([
            'vacancy_id' => $vacancy->id,
            'user_id'    => $candidate->id,
            'status'     => ApplicationStatus::Pending,
        ]);
    }

    #[Test]
    public function employer_can_send_interview_request(): void
    {
        $application = $this->makeApplication();
        $questions   = ['Розкажіть про свій досвід?', 'Чому хочете працювати у нас?'];

        $request = $this->service->send($application, $questions, null);

        $this->assertInstanceOf(InterviewRequest::class, $request);
        $this->assertSame($application->id, $request->application_id);
        $this->assertSame($questions, $request->questions);
        $this->assertSame(InterviewRequestStatus::Pending, $request->status);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Interview, $application->status);
    }

    #[Test]
    public function cannot_send_duplicate_interview_request(): void
    {
        $application = $this->makeApplication();
        $questions   = ['Перше питання?'];

        $this->service->send($application, $questions, null);

        $this->expectException(\RuntimeException::class);

        $this->service->send($application, ['Друге питання?'], null);
    }

    #[Test]
    public function candidate_can_save_draft_response(): void
    {
        $application = $this->makeApplication();
        $request     = $this->service->send($application, ['Питання 1?'], null);
        $candidate   = $application->candidate ?? User::find($application->user_id);

        $answers = [['question_index' => 0, 'text' => 'Моя відповідь']];

        $response = $this->service->saveResponse($request, $candidate, $answers, submit: false);

        $this->assertNull($response->submitted_at);
        $this->assertSame(InterviewRequestStatus::Pending, $request->fresh()->status);
    }

    #[Test]
    public function candidate_can_submit_response(): void
    {
        $application = $this->makeApplication();
        $request     = $this->service->send($application, ['Питання 1?'], null);
        $candidate   = User::find($application->user_id);

        $answers = [['question_index' => 0, 'text' => 'Моя фінальна відповідь']];

        $response = $this->service->saveResponse($request, $candidate, $answers, submit: true);

        $this->assertNotNull($response->submitted_at);
        $this->assertTrue($response->isSubmitted());
    }

    #[Test]
    public function submitted_response_changes_request_status_to_answered(): void
    {
        $application = $this->makeApplication();
        $request     = $this->service->send($application, ['Питання 1?'], null);
        $candidate   = User::find($application->user_id);

        $answers = [['question_index' => 0, 'text' => 'Відповідь кандидата']];

        $this->service->saveResponse($request, $candidate, $answers, submit: true);

        $this->assertSame(InterviewRequestStatus::Answered, $request->fresh()->status);
    }

    #[Test]
    public function expired_interviews_are_marked_correctly(): void
    {
        $app1 = $this->makeApplication();
        $app2 = $this->makeApplication();
        $app3 = $this->makeApplication();

        $pastDeadline   = Carbon::now()->subDay();
        $futureDeadline = Carbon::now()->addDay();

        $expiredRequest = $this->service->send($app1, ['Питання?'], $pastDeadline);
        $activeRequest  = $this->service->send($app2, ['Питання?'], $futureDeadline);
        $noDeadline     = $this->service->send($app3, ['Питання?'], null);

        InterviewRequest::where('id', $expiredRequest->id)
            ->update(['deadline_at' => $pastDeadline]);

        $count = $this->service->markExpired();

        $this->assertSame(1, $count);
        $this->assertSame(InterviewRequestStatus::Expired, $expiredRequest->fresh()->status);
        $this->assertSame(InterviewRequestStatus::Pending, $activeRequest->fresh()->status);
        $this->assertSame(InterviewRequestStatus::Pending, $noDeadline->fresh()->status);
    }

    #[Test]
    public function candidate_cannot_edit_submitted_response(): void
    {
        $application = $this->makeApplication();
        $request     = $this->service->send($application, ['Питання 1?'], null);
        $candidate   = User::find($application->user_id);

        $answers = [['question_index' => 0, 'text' => 'Перша відповідь']];
        $this->service->saveResponse($request, $candidate, $answers, submit: true);

        $this->expectException(\RuntimeException::class);

        $this->service->saveResponse($request, $candidate, [
            ['question_index' => 0, 'text' => 'Спроба редагування'],
        ], submit: false);
    }
}
