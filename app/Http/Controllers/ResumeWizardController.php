<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use App\Models\EmailVerification;
use App\Models\Experience;
use App\Models\Resume;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ResumeWizardController extends Controller
{
    /**
     * GET /api/resumes
     */
    public function index(Request $request): JsonResponse
    {
        $resumes = Resume::forUser($request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (Resume $resume) => $this->formatResume($resume));

        return response()->json([
            'data'  => $resumes,
            'count' => $resumes->count(),
        ]);
    }

    /**
     * POST /api/resumes
     */
    public function store(Request $request): JsonResponse
    {
        $resume = Resume::create([
            'user_id' => $request->user()->id,
            'title'   => $request->input('title', 'Нове резюме'),
            'status'  => 'draft',
        ]);

        return response()->json([
            'data'    => $this->formatResume($resume),
            'message' => 'Резюме створено',
        ], 201);
    }

    /**
     * GET /api/resumes/{resume}
     */
    public function show(Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        return response()->json([
            'data' => $this->formatResumeDetailed($resume),
        ]);
    }

    /**
     * PATCH /api/resumes/{resume}
     */
    public function update(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $input = $request->only([
            'personal_info',
            'location',
            'notifications',
            'additional_info',
            'title',
        ]);

        try {
            if (isset($input['personal_info'])) {
                $this->validatePersonalInfo($input['personal_info']);
                $resume->updatePersonalInfo($input['personal_info']);
            }

            if (isset($input['location'])) {
                $this->validateLocation($input['location']);
                $resume->updateLocation($input['location']);
            }

            if (isset($input['notifications'])) {
                $resume->updateNotifications($input['notifications']);
            }

            if (isset($input['additional_info'])) {
                $resume->updateAdditionalInfo($input['additional_info']);
            }

            if (isset($input['title'])) {
                $resume->update(['title' => $input['title']]);
            }

            $resume->refresh();

            return response()->json([
                'data'     => $this->formatResumeDetailed($resume),
                'saved_at' => $resume->last_saved_at,
                'message'  => 'Збережено',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Помилка при збереженні: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/resumes/{resume}
     */
    public function destroy(Resume $resume): JsonResponse
    {
        $this->authorize('delete', $resume);

        if ($resume->status === 'published') {
            return response()->json([
                'error' => 'Неможливо видалити опубліковане резюме',
            ], 403);
        }

        $resume->delete();

        return response()->json(['message' => 'Резюме видалено']);
    }

    /**
     * POST /api/resumes/{resume}/send-verification-code
     */
    public function sendVerificationCode(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');

        $verification = EmailVerification::updateOrCreate(
            ['email' => $email],
            [
                'code'            => EmailVerification::generateCode(),
                'code_expires_at' => now()->addMinutes(10),
                'is_verified'     => false,
                'verified_at'     => null,
            ]
        );

        try {
            Mail::to($email)->send(new VerificationCodeMail($verification->code));
        } catch (\Exception) {
            return response()->json(['error' => 'Помилка при надіслані email'], 500);
        }

        return response()->json([
            'message'         => 'Код верифікації надісланий на ' . $email,
            'code_expires_at' => $verification->code_expires_at,
        ]);
    }

    /**
     * POST /api/resumes/{resume}/verify-email
     */
    public function verifyEmail(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $verification = EmailVerification::where('email', $request->input('email'))->first();

        if (!$verification) {
            return response()->json(['error' => 'Код верифікації не знайдено'], 404);
        }

        if (!$verification->verifyCode($request->input('code'))) {
            return response()->json(['error' => 'Невірний код або код скінчився'], 422);
        }

        $resume->updatePersonalInfo([
            'email'             => $request->input('email'),
            'email_verified_at' => now()->toISOString(),
        ]);

        return response()->json([
            'data'    => $this->formatResumeDetailed($resume->fresh()),
            'message' => 'Email верифіковано',
        ]);
    }

    /**
     * POST /api/resumes/{resume}/experiences
     */
    public function storeExperience(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($resume->experiences()->count() >= 5) {
            return response()->json(['error' => 'Максимум 5 записів про досвід роботи'], 422);
        }

        $validated = $request->validate([
            'position'         => 'required|string|max:255',
            'company_name'     => 'required|string|max:255',
            'company_industry' => 'nullable|string|max:255',
            'start_date'       => 'required|date',
            'end_date'         => 'nullable|date|after:start_date',
            'is_current'       => 'boolean',
        ]);

        $experience = $resume->experiences()->create($validated);

        return response()->json([
            'data'    => $experience,
            'message' => 'Досвід додано',
        ], 201);
    }

    /**
     * PATCH /api/resumes/{resume}/experiences/{experience}
     */
    public function updateExperience(Request $request, Resume $resume, Experience $experience): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($experience->resume_id !== $resume->id) {
            return response()->json(['error' => 'Досвід не належить цьому резюме'], 403);
        }

        $validated = $request->validate([
            'position'         => 'nullable|string|max:255',
            'company_name'     => 'nullable|string|max:255',
            'company_industry' => 'nullable|string|max:255',
            'start_date'       => 'nullable|date',
            'end_date'         => 'nullable|date|after:start_date',
            'is_current'       => 'boolean',
        ]);

        $experience->update($validated);

        return response()->json([
            'data'    => $experience->fresh(),
            'message' => 'Досвід оновлено',
        ]);
    }

    /**
     * DELETE /api/resumes/{resume}/experiences/{experience}
     */
    public function destroyExperience(Resume $resume, Experience $experience): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($experience->resume_id !== $resume->id) {
            return response()->json(['error' => 'Досвід не належить цьому резюме'], 403);
        }

        $experience->delete();

        return response()->json(['message' => 'Досвід видалено']);
    }

    /**
     * POST /api/resumes/{resume}/skills
     */
    public function storeSkill(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $validated = $request->validate([
            'skill_name' => 'required|string|max:255',
        ]);

        $skill = $resume->skills()->create($validated);

        return response()->json([
            'data'    => $skill,
            'message' => 'Навичка додана',
        ], 201);
    }

    /**
     * DELETE /api/resumes/{resume}/skills/{skill}
     */
    public function destroySkill(Resume $resume, Skill $skill): JsonResponse
    {
        $this->authorize('update', $resume);

        if ($skill->resume_id !== $resume->id) {
            return response()->json(['error' => 'Навичка не належить цьому резюме'], 403);
        }

        $skill->delete();

        return response()->json(['message' => 'Навичка видалена']);
    }

    /**
     * POST /api/resumes/{resume}/publish
     */
    public function publish(Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        if (!$resume->isPublishable()) {
            return response()->json([
                'error'          => 'Неможливо опублікувати резюме. Будь ласка, заповніть критичні поля.',
                'stepper_status' => $resume->getStepperStatus(),
            ], 422);
        }

        $resume->update(['status' => 'published']);

        return response()->json([
            'data'    => $this->formatResumeDetailed($resume->fresh()),
            'message' => 'Резюме опубліковано',
        ]);
    }

    /**
     * GET /api/resumes/{resume}/stepper-status
     */
    public function stepperStatus(Resume $resume): JsonResponse
    {
        $this->authorize('view', $resume);

        return response()->json([
            'data'           => $resume->getStepperStatus(),
            'is_publishable' => $resume->isPublishable(),
        ]);
    }

    // ===== Private helpers =====

    private function formatResume(Resume $resume): array
    {
        $info = $resume->personal_info ?? [];

        return [
            'id'               => $resume->id,
            'title'            => $resume->title,
            'status'           => $resume->status,
            'full_name'        => trim(($info['first_name'] ?? '') . ' ' . ($info['last_name'] ?? '')),
            'experiences_count' => $resume->experiences()->count(),
            'skills_count'     => $resume->skills()->count(),
            'last_saved_at'    => $resume->last_saved_at?->diffForHumans(),
            'updated_at'       => $resume->updated_at,
        ];
    }

    private function formatResumeDetailed(Resume $resume): array
    {
        return [
            'id'              => $resume->id,
            'title'           => $resume->title,
            'status'          => $resume->status,
            'personal_info'   => $resume->personal_info,
            'location'        => $resume->location,
            'notifications'   => $resume->notifications,
            'additional_info' => $resume->additional_info,
            'experiences'     => $resume->experiences()
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(fn (Experience $exp) => [
                    'id'               => $exp->id,
                    'position'         => $exp->position,
                    'company_name'     => $exp->company_name,
                    'company_industry' => $exp->company_industry,
                    'start_date'       => $exp->start_date?->format('Y-m-d'),
                    'end_date'         => $exp->end_date?->format('Y-m-d'),
                    'is_current'       => $exp->is_current,
                ]),
            'skills'          => $resume->skills()->pluck('skill_name'),
            'stepper_status'  => $resume->getStepperStatus(),
            'is_publishable'  => $resume->isPublishable(),
            'last_saved_at'   => $resume->last_saved_at,
            'created_at'      => $resume->created_at,
            'updated_at'      => $resume->updated_at,
        ];
    }

    private function validatePersonalInfo(array $data): void
    {
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Невірний формат email');
        }
    }

    private function validateLocation(array $data): void
    {
        if (isset($data['latitude'], $data['longitude'])) {
            $lat = $data['latitude'];
            $lng = $data['longitude'];

            if (!is_numeric($lat) || !is_numeric($lng)
                || $lat < -90 || $lat > 90
                || $lng < -180 || $lng > 180
            ) {
                throw new \InvalidArgumentException('Невірні GPS координати');
            }
        }
    }
}
