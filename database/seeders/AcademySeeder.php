<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\CourseType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Enums\UpskillingStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\DropoutReason;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Group;
use App\Models\InstructorProfile;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\ReferralCode;
use App\Models\Review;
use App\Models\Setting;
use App\Models\StudentTask;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\Learning\ProgressService;
use App\Services\Tasks\TaskTemplateAssigner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Arabic demo data so the platform can be shown end-to-end right after install.
 * Every demo account uses the password "password".
 */
class AcademySeeder extends Seeder
{
    private string $password;

    private array $firstNames = ['أحمد', 'محمد', 'سارة', 'مريم', 'يوسف', 'نور', 'عمر', 'ليلى', 'خالد', 'هبة', 'مصطفى', 'رنا', 'كريم', 'دينا', 'ياسين', 'سلمى', 'زياد', 'منة', 'حسن', 'فرح', 'علي', 'جنى', 'طارق', 'ملك'];

    private array $lastNames = ['عبد الله', 'السيد', 'إبراهيم', 'حسين', 'منصور', 'الشريف', 'فؤاد', 'جمال', 'سامي', 'عادل', 'نبيل', 'رمضان', 'شاكر', 'الدسوقي'];

    public function run(): void
    {
        mt_srand(2026);
        $this->password = Hash::make('password');

        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::put($key, $value);
        }

        $staff = $this->staff();
        $departments = $this->departments();
        $instructors = $this->instructors($departments);
        $courses = $this->courses($departments, $instructors);
        $students = $this->students(84);
        $groups = $this->groups($courses, $students);

        $this->recordedLearning($courses, $students);
        $this->tasks($groups, $staff['admin']);
        $this->crm($departments, $courses, $staff);
        $this->finance($courses, $students, $staff);
        $this->instructorOps($instructors, $staff['admin']);
        $this->reviews($courses, $students);
    }

    private function person(string $email, Role $role, ?string $name = null, ?string $phone = null): User
    {
        $user = User::create([
            'name' => $name ?? Arr::random($this->firstNames).' '.Arr::random($this->lastNames),
            'email' => $email,
            'phone' => $phone ?? '+2010'.mt_rand(10000000, 99999999),
            'password' => $this->password,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role->value);

        return $user;
    }

    private function staff(): array
    {
        return [
            'admin' => $this->person('admin@codex.academy', Role::SuperAdmin, 'م. أحمد جمال'),
            'sales' => $this->person('sales@codex.academy', Role::Sales, 'نورهان سعيد'),
            'sales2' => $this->person('care@codex.academy', Role::Sales, 'محمود عاطف'),
        ];
    }

    private function departments(): array
    {
        $rows = [
            ['name' => 'اللغة الإنجليزية', 'name_en' => 'English', 'slug' => 'english', 'language_code' => 'en', 'color' => '#0A5A9E'],
            ['name' => 'البرمجة وتطوير الويب', 'name_en' => 'Programming', 'slug' => 'programming', 'language_code' => null, 'color' => '#33B8F6'],
            ['name' => 'اللغة الألمانية', 'name_en' => 'German', 'slug' => 'german', 'language_code' => 'de', 'color' => '#1E3A5F'],
            ['name' => 'اللغة الإيطالية', 'name_en' => 'Italian', 'slug' => 'italian', 'language_code' => 'it', 'color' => '#2B7A78'],
        ];

        return collect($rows)->map(fn ($row, $i) => Department::create($row + [
            'sort' => $i,
            'description' => 'مسارات تعليمية متدرجة من المبتدئ حتى الاحتراف مع متابعة شخصية لكل طالب.',
        ]))->keyBy('slug')->all();
    }

    private function instructors(array $departments): array
    {
        $rows = [
            ['أ. ريم الشافعي', 'english', 'مدربة IELTS معتمدة', 9, 260, ['IELTS', 'Conversation', 'Business English']],
            ['أ. كريم عبد الرحمن', 'programming', 'مهندس برمجيات أول ومدرب Full-Stack', 11, 320, ['Laravel', 'React', 'System Design']],
            ['أ. ياسمين فاروق', 'english', 'متخصصة تأسيس اللغة للأطفال والكبار', 6, 200, ['Grammar', 'Phonics']],
            ['Dr. Lukas Weber', 'german', 'Goethe-zertifizierter Trainer', 8, 300, ['A1–B2', 'Goethe Prep']],
            ['أ. عمر حلمي', 'programming', 'مطور Python وعلوم البيانات', 7, 280, ['Python', 'Data Analysis', 'AI']],
            ['Prof.ssa Giulia Rossi', 'italian', 'Docente madrelingua certificata CILS', 10, 290, ['A1–B1', 'CILS']],
        ];

        return collect($rows)->map(function ($row, $i) use ($departments) {
            [$name, $dept, $headline, $years, $rate, $skills] = $row;
            $user = $this->person('instructor'.($i + 1).'@codex.academy', Role::Instructor, $name);

            InstructorProfile::create([
                'user_id' => $user->id,
                'department_id' => $departments[$dept]->id,
                'slug' => Str::slug(Str::ascii($name)) ?: 'instructor-'.($i + 1),
                'headline' => $headline,
                'bio' => "{$name} — {$headline} بخبرة تتجاوز {$years} سنوات في التدريس. يركّز على التعلم التطبيقي، ومتابعة تقدم كل طالب أسبوعياً، وتحويل المفاهيم المعقدة إلى خطوات بسيطة وواضحة.",
                'experience_years' => $years,
                'specialties' => $skills,
                'qualifications' => ['شهادة تدريب دولية معتمدة', 'أكثر من '.($years * 120).' طالب تم تدريبهم'],
                'hourly_rate' => $rate,
                'intro_video_id' => (string) Str::uuid(),
                'demo_video_id' => (string) Str::uuid(),
                'demo_video_title' => 'محاضرة تجريبية: '.$skills[0],
                'rating' => mt_rand(44, 50) / 10,
                'ratings_count' => mt_rand(40, 260),
            ]);

            return $user;
        })->all();
    }

    private function courses(array $departments, array $instructors): array
    {
        $rows = [
            // title, dept, type, instructor idx, price, level, hours, featured
            ['الإنجليزية من الصفر حتى الطلاقة', 'english', CourseType::Recorded, 2, 1200, 'مبتدئ', 36, true],
            ['التحضير الشامل لاختبار IELTS', 'english', CourseType::Live, 0, 3400, 'متوسط', 40, true],
            ['محادثة إنجليزية مكثفة — حضوري', 'english', CourseType::Offline, 0, 2800, 'متوسط', 24, false],
            ['تطوير الويب Full-Stack بـ Laravel', 'programming', CourseType::Recorded, 1, 2400, 'متوسط', 52, true],
            ['React و Next.js التفاعلي', 'programming', CourseType::Live, 1, 3900, 'متقدم', 48, true],
            ['أساسيات Python وتحليل البيانات', 'programming', CourseType::Recorded, 4, 1500, 'مبتدئ', 30, false],
            ['معسكر البرمجة الحضوري للمبتدئين', 'programming', CourseType::Offline, 4, 4500, 'مبتدئ', 60, false],
            ['الألمانية A1 — Goethe', 'german', CourseType::Live, 3, 3200, 'مبتدئ', 42, true],
            ['الألمانية A2 — مسجل', 'german', CourseType::Recorded, 3, 1400, 'مبتدئ', 28, false],
            ['الإيطالية للمبتدئين A1', 'italian', CourseType::Live, 5, 2900, 'مبتدئ', 36, false],
            ['قواعد الإنجليزية الذكية', 'english', CourseType::Recorded, 2, 650, 'مبتدئ', 14, false],
            ['Business English للمحترفين', 'english', CourseType::Live, 0, 3600, 'متقدم', 30, false],
        ];

        return collect($rows)->map(function ($row, $i) use ($departments, $instructors) {
            [$title, $dept, $type, $ins, $price, $level, $hours, $featured] = $row;

            $course = Course::create([
                'department_id' => $departments[$dept]->id,
                'instructor_id' => $instructors[$ins]->id,
                'title' => $title,
                'slug' => 'course-'.($i + 1).'-'.Str::slug(Str::ascii($title)),
                'subtitle' => $type->description(),
                'description' => "برنامج «{$title}» مصمم بمنهجية عملية تبدأ من الأساسيات وتنتهي بمشروع تطبيقي. يحصل كل طالب على لوحة مهام أسبوعية، ومتابعة شخصية، وشهادة إتمام معتمدة من أكاديمية كودكس.",
                'outcomes' => ['خطة تعلم أسبوعية واضحة', 'تطبيقات عملية بعد كل وحدة', 'متابعة شخصية من المحاضر', 'شهادة إتمام قابلة للتحقق'],
                'type' => $type,
                'level' => $level,
                'price' => $price,
                'sale_price' => $i % 3 === 0 ? round($price * 0.8) : null,
                'duration_hours' => $hours,
                'location' => $type === CourseType::Offline ? 'مقر الأكاديمية — مدينة نصر، القاهرة (قاعة '.($i % 3 + 1).')' : null,
                'is_featured' => $featured,
            ]);

            if ($type === CourseType::Recorded) {
                $this->curriculum($course);
            }

            return $course;
        })->all();
    }

    private function curriculum(Course $course): void
    {
        $sections = ['البداية والتأسيس', 'المفاهيم الأساسية', 'التطبيق العملي', 'المشروع النهائي'];
        $sort = 0;

        foreach ($sections as $s => $title) {
            $section = $course->sections()->create(['title' => $title, 'sort' => $s]);

            foreach (range(1, 3) as $n) {
                $course->lessons()->create([
                    'course_section_id' => $section->id,
                    'title' => 'الدرس '.($sort + 1).': '.Arr::random(['مقدمة عملية', 'شرح تفصيلي', 'تمرين تطبيقي', 'مراجعة وأسئلة', 'دراسة حالة']),
                    'video_id' => (string) Str::uuid(),
                    'duration_minutes' => mt_rand(8, 26),
                    'content' => 'ملخص الدرس والملفات المرفقة متاحة أسفل المشغل.',
                    'is_preview' => $sort === 0,
                    'sort' => $sort++,
                ]);
            }
        }

        $quiz = $course->quiz()->create(['title' => 'الاختبار النهائي — '.$course->title, 'passing_score' => 70]);

        foreach (range(1, 5) as $q) {
            $quiz->questions()->create([
                'question' => "السؤال {$q}: اختر الإجابة الصحيحة وفق ما تم شرحه في الوحدة {$q}.",
                'options' => ['الإجابة الأولى', 'الإجابة الثانية', 'الإجابة الثالثة', 'الإجابة الرابعة'],
                'correct_index' => $q % 4,
                'sort' => $q,
            ]);
        }
    }

    private function students(int $count): array
    {
        $students = [$this->person('student@codex.academy', Role::Student, 'يوسف عبد الله', '+201012345678')];

        foreach (range(2, $count) as $i) {
            $students[] = $this->person("student{$i}@codex.academy", Role::Student);
        }

        foreach ($students as $student) {
            ReferralCode::issueFor($student);
        }

        return $students;
    }

    private function groups(array $courses, array $students): array
    {
        $groups = [];
        $pool = collect($students);

        foreach ($courses as $course) {
            if (! $course->type->isInteractive()) {
                continue;
            }

            foreach (range(1, 2) as $n) {
                $startsOn = now()->subWeeks($n === 1 ? 5 : 1)->startOfWeek();

                $group = Group::create([
                    'course_id' => $course->id,
                    'instructor_id' => $course->instructor_id,
                    'name' => 'دفعة '.($n === 1 ? 'سبتمبر' : 'أكتوبر').' — '.Str::limit($course->title, 28, ''),
                    'mode' => $course->type,
                    'meeting_url' => $course->type === CourseType::Live ? Arr::random(['https://zoom.us/j/', 'https://meet.google.com/']).Str::lower(Str::random(10)) : null,
                    'whatsapp_url' => 'https://chat.whatsapp.com/'.Str::random(22),
                    'location' => $course->location,
                    'capacity' => 25,
                    'starts_on' => $startsOn,
                    'ends_on' => $startsOn->copy()->addWeeks(8),
                ]);

                $members = $pool->random(mt_rand(10, 16));
                if ($course->id === $courses[1]->id && $n === 1) {
                    $members->prepend($students[0]); // demo student is in the IELTS live group
                }

                $group->students()->syncWithoutDetaching($members->pluck('id'));

                foreach ($members->unique('id') as $student) {
                    $student->enrollments()->firstOrCreate(['course_id' => $course->id], [
                        'group_id' => $group->id,
                        'enrolled_at' => $startsOn,
                        'progress' => mt_rand(10, 80),
                    ]);
                }

                // 16 sessions: twice a week. Past ones completed, future ones scheduled.
                foreach (range(0, 15) as $s) {
                    $startsAt = $startsOn->copy()->addDays(intdiv($s, 2) * 7 + ($s % 2 ? 3 : 0))->setTime(19, 0);
                    $status = $startsAt->isPast() ? (mt_rand(1, 20) === 1 ? SessionStatus::Cancelled : SessionStatus::Completed) : SessionStatus::Scheduled;

                    $session = $group->sessions()->create([
                        'title' => 'المحاضرة '.($s + 1),
                        'starts_at' => $startsAt,
                        'duration_hours' => 2,
                        'status' => $status,
                    ]);

                    if ($status === SessionStatus::Completed) {
                        foreach ($members->unique('id') as $student) {
                            $session->attendances()->create([
                                'user_id' => $student->id,
                                'status' => Arr::random([AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Present, AttendanceStatus::Late, AttendanceStatus::Absent]),
                            ]);
                        }
                    }
                }

                foreach ($members->unique('id') as $student) {
                    $group->grades()->create(['user_id' => $student->id, 'title' => 'اختبار منتصف المستوى', 'score' => mt_rand(55, 98), 'recorded_by' => $course->instructor_id]);
                }

                $groups[] = $group;
            }
        }

        return $groups;
    }

    private function recordedLearning(array $courses, array $students): void
    {
        $recorded = collect($courses)->filter(fn ($c) => $c->type === CourseType::Recorded)->values();
        $progress = app(ProgressService::class);

        foreach ($students as $i => $student) {
            $picks = $i === 0 ? $recorded->take(2) : $recorded->random(mt_rand(0, 2));

            foreach ($picks as $course) {
                $student->enrollments()->firstOrCreate(['course_id' => $course->id], ['enrolled_at' => now()->subDays(mt_rand(5, 120))]);
                $lessons = $course->lessons()->get();
                $watched = $i === 0 && $course->is($recorded->first()) ? 7 : mt_rand(0, $lessons->count());

                foreach ($lessons->take($watched) as $lesson) {
                    $student->lessonProgress()->create(['lesson_id' => $lesson->id, 'completed_at' => now()->subDays(mt_rand(1, 30))]);
                }

                if ($watched === $lessons->count() && $course->quiz) {
                    $answers = $course->quiz->questions->mapWithKeys(fn ($q) => [$q->id => $q->correct_index])->all();
                    $progress->submitQuiz($student, $course->quiz, $answers);
                } else {
                    $progress->recalculate($student, $course);
                }
            }
        }
    }

    private function tasks(array $groups, User $admin): void
    {
        $template = TaskTemplate::create([
            'name' => 'خطة الأسابيع الأربعة الأولى',
            'description' => 'قالب جاهز يُسند لكل دفعة جديدة.',
            'created_by' => $admin->id,
        ]);

        $items = [
            [1, 'مشاهدة محاضرة التأسيس وتدوين الملاحظات'], [1, 'حل تمرين الوحدة الأولى'], [1, 'الانضمام لمجموعة الواتساب وتعريف نفسك'],
            [2, 'مراجعة المفردات/المفاهيم الجديدة'], [2, 'تسليم الواجب الأسبوعي'], [2, 'حضور جلسة المراجعة'],
            [3, 'تطبيق عملي قصير وتسجيله'], [3, 'اختبار قصير على الوحدة الثالثة'],
            [4, 'تجهيز المشروع المصغر'], [4, 'مراجعة شاملة قبل اختبار المستوى'],
        ];

        foreach ($items as $sort => [$week, $title]) {
            $template->items()->create(['week_no' => $week, 'title' => $title, 'sort' => $sort]);
        }

        $assigner = app(TaskTemplateAssigner::class);

        foreach ($groups as $group) {
            $assigner->assignToGroup($template, $group, $admin);
        }

        // Students have ticked off part of their past-week tasks.
        StudentTask::query()->where('due_on', '<', now()->toDateString())->inRandomOrder()->limit(1400)
            ->update(['completed_at' => now()->subDays(2)]);
    }

    private function crm(array $departments, array $courses, array $staff): void
    {
        $reasons = collect(['السعر أعلى من الميزانية', 'تعارض المواعيد', 'اختار جهة أخرى', 'ظروف شخصية / سفر', 'المستوى غير مناسب', 'لا يرد على التواصل'])
            ->map(fn ($name, $i) => DropoutReason::create(['name' => $name, 'sort' => $i]));

        $distribution = [
            LeadStatus::Pending->value => 14,
            LeadStatus::AssessmentScheduled->value => 9,
            LeadStatus::AssessmentCompleted->value => 8,
            LeadStatus::OnHold->value => 6,
            LeadStatus::Enrolled->value => 14,
            LeadStatus::DroppedOut->value => 11,
        ];

        $sales = [$staff['sales'], $staff['sales2']];
        $n = 0;

        foreach ($distribution as $statusValue => $count) {
            $status = LeadStatus::from($statusValue);

            foreach (range(1, $count) as $i) {
                $n++;
                $course = Arr::random($courses);
                $created = now()->subHours($status === LeadStatus::Pending ? mt_rand(1, 40) : mt_rand(30, 24 * 60));

                Carbon::setTestNow($created);
                $lead = Lead::create([
                    'name' => Arr::random($this->firstNames).' '.Arr::random($this->lastNames),
                    'email' => "lead{$n}@example.com",
                    'phone' => '+2011'.mt_rand(10000000, 99999999),
                    'department_id' => $course->department_id,
                    'course_id' => $course->id,
                    'study_mode' => $course->type,
                    'source' => Arr::random([LeadSource::GoogleForms, LeadSource::GoogleForms, LeadSource::Site, LeadSource::Manual, LeadSource::Referral]),
                    'assigned_to' => Arr::random($sales)->id,
                ]);
                $lead->activities()->create(['type' => 'created', 'to_status' => 'pending', 'body' => 'تم استلام التسجيل عبر '.$lead->source->getLabel()]);
                Carbon::setTestNow();

                if ($status === LeadStatus::Pending) {
                    if ($lead->sla_due_at->isPast()) {
                        $lead->update(['is_overdue' => true, 'overdue_notified_at' => $lead->sla_due_at->copy()->addMinutes(5)]);
                        $lead->activities()->create(['type' => 'sla_breach', 'body' => 'تجاوز مؤشر الـ SLA دون تواصل — نُقل إلى قائمة المتأخرين.']);
                    }

                    continue;
                }

                $contacted = $created->copy()->addHours(mt_rand(1, 30));
                $isDropped = $status === LeadStatus::DroppedOut;

                $lead->forceFill([
                    'status' => $status,
                    'first_contacted_at' => $contacted,
                    'is_overdue' => $contacted->gt($lead->sla_due_at),
                    'assessment_at' => in_array($status, [LeadStatus::AssessmentScheduled], true) ? now()->addDays(mt_rand(1, 6))->setTime(mt_rand(10, 20), 0) : $contacted->copy()->addDays(2),
                    'assessment_level' => in_array($status, [LeadStatus::AssessmentCompleted, LeadStatus::Enrolled, LeadStatus::OnHold], true) ? Arr::random(['A1', 'A2', 'B1', 'B2', 'مبتدئ', 'متوسط']) : null,
                    'dropout_reason_id' => $isDropped ? $reasons->random()->id : null,
                    'dropout_notes' => $isDropped ? 'تم التواصل ثلاث مرات، ويمكن إعادة استهدافه بعرض الدفعة القادمة.' : null,
                    'dropout_approved_at' => $isDropped && $i % 3 !== 0 ? $contacted->copy()->addDay() : null,
                    'dropout_approved_by' => $isDropped && $i % 3 !== 0 ? $staff['admin']->id : null,
                ])->save();

                $lead->activities()->create([
                    'user_id' => $lead->assigned_to,
                    'type' => 'status_change',
                    'from_status' => 'pending',
                    'to_status' => $status->value,
                    'body' => 'تم التواصل هاتفياً وتحديث الحالة.',
                    'created_at' => $contacted,
                ]);
            }
        }
    }

    private function finance(array $courses, array $students, array $staff): void
    {
        // Online purchases + manual bookings for the last 6 months.
        foreach (range(0, 180) as $daysAgo) {
            foreach (range(1, mt_rand(2, 5)) as $_) {
                $course = Arr::random($courses);
                $manual = $course->type !== CourseType::Recorded && mt_rand(0, 1);
                $gross = $course->effectivePrice();
                $discount = mt_rand(1, 8) === 1 ? round($gross * 0.1, 2) : 0;
                $paidAt = now()->subDays($daysAgo)->setTime(mt_rand(9, 22), mt_rand(0, 59));

                Payment::create([
                    'user_id' => Arr::random($students)->id,
                    'course_id' => $course->id,
                    'channel' => $course->type,
                    'source' => $manual ? 'manual' : 'online',
                    'method' => $manual ? Arr::random([PaymentMethod::Cash, PaymentMethod::Transfer]) : Arr::random([PaymentMethod::Card, PaymentMethod::Wallet]),
                    'gross_amount' => $gross,
                    'discount_amount' => $discount,
                    'amount' => $gross - $discount,
                    'reference' => ($manual ? 'MAN-' : 'PAY-').Str::upper(Str::random(8)),
                    'status' => $daysAgo < 2 && $manual ? PaymentStatus::Pending : PaymentStatus::Paid,
                    'paid_at' => $paidAt,
                    'recorded_by' => $manual ? Arr::random([$staff['sales'], $staff['sales2']])->id : null,
                    'approved_at' => $manual && $daysAgo >= 2 ? $paidAt->copy()->addHours(3) : null,
                    'approved_by' => $manual && $daysAgo >= 2 ? $staff['admin']->id : null,
                    'created_at' => $paidAt,
                ]);
            }
        }

        $categories = collect([
            ['إيجار القاعات', '#0A5A9E', [9000, 9000]],
            ['حملات إعلانية', '#33B8F6', [4000, 14000]],
            ['اشتراكات الخوادم والبرمجيات', '#4F46E5', [1800, 3200]],
            ['رواتب إدارية', '#64748B', [22000, 24000]],
            ['مصروفات تشغيلية متنوعة', '#D97706', [800, 3500]],
        ])->map(fn ($row) => [ExpenseCategory::create(['name' => $row[0], 'color' => $row[1]]), $row[2]]);

        foreach (range(0, 5) as $m) {
            $month = now()->subMonths($m)->startOfMonth();

            foreach ($categories as [$category, [$min, $max]]) {
                Expense::create([
                    'expense_category_id' => $category->id,
                    'title' => $category->name.' — '.$month->translatedFormat('F Y'),
                    'amount' => mt_rand($min, $max),
                    'spent_on' => $month->copy()->addDays(mt_rand(0, 20))->min(now()),
                    'created_by' => $staff['admin']->id,
                ]);
            }
        }
    }

    private function instructorOps(array $instructors, User $admin): void
    {
        $plans = [
            ['ورشة: أساليب التعلم النشط', 'https://www.coursera.org/learn/active-learning'],
            ['قراءة: تصميم التقييمات الفعالة', 'https://www.edutopia.org/assessment'],
            ['تطبيق: إعداد اختبار تفاعلي لكل وحدة', null],
            ['حضور جلسة مراجعة الأداء الشهرية', null],
        ];

        foreach ($instructors as $instructor) {
            foreach (range(0, 2) as $m) {
                foreach (Arr::random($plans, 2) as [$title, $url]) {
                    $done = $m > 0 || mt_rand(0, 1);
                    $instructor->upskillingTasks()->create([
                        'month' => now()->subMonths($m)->startOfMonth(),
                        'title' => $title,
                        'description' => 'ضمن خطة رفع الكفاءة الشهرية للكادر التدريسي.',
                        'material_url' => $url,
                        'status' => $done ? UpskillingStatus::Done : Arr::random([UpskillingStatus::Assigned, UpskillingStatus::InProgress]),
                        'due_on' => now()->subMonths($m)->endOfMonth(),
                        'completed_at' => $done ? now()->subMonths($m)->endOfMonth()->subDays(3) : null,
                        'assigned_by' => $admin->id,
                    ]);
                }
            }

            foreach (range(1, mt_rand(0, 3)) as $_) {
                $status = Arr::random([AttendanceStatus::Excused, AttendanceStatus::Late, AttendanceStatus::Absent]);
                $instructor->instructorAttendanceLogs()->create([
                    'date' => now()->subDays(mt_rand(3, 80)),
                    'status' => $status,
                    'reason' => Arr::random(['ظرف صحي طارئ', 'تعارض مع اختبار دولي', 'انقطاع الإنترنت', 'ظرف عائلي']),
                    'is_accepted' => $status !== AttendanceStatus::Absent,
                ]);
            }
        }
    }

    private function reviews(array $courses, array $students): void
    {
        $comments = [
            'شرح ممتاز ومنظم جداً، والمتابعة الأسبوعية فرقت معايا كتير.',
            'المحاضر صبور جداً والتطبيقات العملية مفيدة.',
            'لوحة المهام ساعدتني ألتزم بالخطة لأول مرة.',
            'محتوى قوي وأسعار مناسبة مقارنة بالمستوى.',
            'تجربة رائعة، أنصح بها لأي حد عايز يبدأ صح.',
        ];

        foreach ($courses as $course) {
            foreach (Arr::random($students, 4) as $student) {
                Review::create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'instructor_id' => $course->instructor_id,
                    'rating' => mt_rand(4, 5),
                    'comment' => Arr::random($comments),
                ]);
            }
        }
    }
}
