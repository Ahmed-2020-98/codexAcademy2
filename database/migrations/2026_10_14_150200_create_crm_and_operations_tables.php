<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Mini-CRM -------------------------------------------------------
        Schema::create('dropout_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 32)->nullable()->index();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('study_mode', 16)->nullable(); // recorded | live | offline
            $table->string('source', 24)->default('site'); // google_forms | site | manual | referral
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->boolean('is_overdue')->default(false)->index();
            $table->timestamp('overdue_notified_at')->nullable();
            $table->timestamp('first_contacted_at')->nullable();
            $table->dateTime('assessment_at')->nullable();
            $table->string('assessment_level', 32)->nullable();
            $table->foreignId('dropout_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->text('dropout_notes')->nullable();
            $table->timestamp('dropout_approved_at')->nullable();
            $table->foreignId('dropout_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24); // created | status_change | note | sla_breach | whatsapp
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
        });

        // ---- Personalized task board ---------------------------------------
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('task_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_no')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('student_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_template_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('week_no')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'week_no']);
        });

        // ---- Finance ---------------------------------------------------------
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 24)->unique();
            $table->unsignedTinyInteger('discount_percent')->default(10);
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 16)->index(); // recorded | live | offline
            $table->string('source', 16)->default('online'); // online | manual
            $table->string('method', 24)->default('card'); // card | cash | transfer | wallet
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2);
            $table->foreignId('referral_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('status', 16)->default('paid')->index(); // paid | pending | refunded
            $table->timestamp('paid_at')->nullable()->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 9)->default('#0A5A9E');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('spent_on')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('instructor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('hours', 8, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---- Instructor performance -----------------------------------------
        Schema::create('instructor_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_session_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('status', 16); // absent | excused | late
            $table->text('reason')->nullable();
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();
        });

        Schema::create('upskilling_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->date('month');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('material_url')->nullable();
            $table->string('status', 16)->default('assigned'); // assigned | in_progress | done
            $table->date('due_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ---- Automation log --------------------------------------------------
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('to', 32);
            $table->text('body');
            $table->string('event', 48)->nullable();
            $table->string('provider', 24);
            $table->string('status', 16)->default('queued');
            $table->json('response')->nullable();
            $table->nullableMorphs('related');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['whatsapp_messages', 'upskilling_tasks', 'instructor_attendance_logs', 'instructor_payouts', 'expenses', 'expense_categories', 'referral_redemptions', 'payments', 'referral_codes', 'student_tasks', 'task_template_items', 'task_templates', 'lead_activities', 'leads', 'dropout_reasons'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
