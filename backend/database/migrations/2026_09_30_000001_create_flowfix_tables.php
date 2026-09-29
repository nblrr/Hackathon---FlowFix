<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn(Blueprint $t) => $t->string('role')->default('student'));
        Schema::create('personal_access_tokens', function(Blueprint $t) { $t->id(); $t->morphs('tokenable'); $t->text('name'); $t->string('token',64)->unique(); $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamp('expires_at')->nullable()->index(); $t->timestamps(); });
        Schema::create('submissions', function(Blueprint $t) { $t->id(); $t->foreignId('user_id')->constrained(); $t->string('service_type')->default('internship'); $t->json('form_data'); $t->string('status')->default('DRAFT'); $t->unsignedInteger('data_version')->default(1); $t->string('sop_version'); $t->timestamps(); });
        Schema::create('messages', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->string('speaker'); $t->text('content'); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create('documents', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->string('type'); $t->string('original_filename'); $t->string('storage_key'); $t->unsignedInteger('version'); $t->unsignedInteger('data_version'); $t->boolean('is_current')->default(true); $t->string('extraction_status'); $t->json('extraction_metadata')->nullable(); $t->json('pages')->nullable(); $t->timestamps(); $t->index(['submission_id','type','is_current']); });
        Schema::create('precheck_runs', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->unsignedInteger('data_version'); $t->string('sop_version'); $t->string('status'); $t->json('snapshot'); $t->json('result')->nullable(); $t->string('error_code')->nullable(); $t->timestamps(); });
        Schema::create('reviews', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->foreignId('user_id')->constrained(); $t->unsignedInteger('data_version'); $t->string('decision'); $t->text('comment'); $t->timestamps(); $t->unique(['submission_id','data_version']); });
        Schema::create('revision_plans', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->unsignedInteger('data_version'); $t->string('source')->default('revision'); $t->string('status')->default('PROPOSED'); $t->json('snapshot'); $t->json('proposal'); $t->json('selected_fields')->nullable(); $t->timestamps(); });
        Schema::create('reviewer_summaries', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->unsignedInteger('data_version'); $t->json('result'); $t->timestamps(); });
        Schema::create('audit_events', function(Blueprint $t) { $t->id(); $t->foreignId('submission_id')->constrained(); $t->foreignId('user_id')->constrained(); $t->string('action'); $t->unsignedInteger('data_version'); $t->json('details'); $t->timestamps(); });
    }
    public function down(): void { foreach(['audit_events','reviewer_summaries','revision_plans','reviews','precheck_runs','documents','messages','submissions','personal_access_tokens'] as $table) Schema::dropIfExists($table); Schema::table('users', fn(Blueprint $t)=>$t->dropColumn('role')); }
};
