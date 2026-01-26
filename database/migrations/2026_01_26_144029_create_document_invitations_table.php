<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'sent', 'opened', 'completed', 'expired'])->default('pending');
            $table->boolean('sms_sent')->default(false);
            $table->string('sms_message_id')->nullable();
            $table->string('sms_status')->nullable();
            $table->text('sms_error')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->string('email_status')->nullable();
            $table->text('email_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at');
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['token', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_invitations');
    }
};
