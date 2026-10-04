<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWorkerComplianceDocumentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('worker_compliance_documents')) {
            return;
        }

        Schema::create('worker_compliance_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('document_type_id')->index();
            $table->string('document_number')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending_review', 'verified', 'expired', 'rejected'])
                  ->default('pending_review');
            $table->unsignedInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            if (Schema::hasTable('compliance_document_types')) {
                $table->foreign('document_type_id')->references('id')->on('compliance_document_types')->onDelete('cascade');
            }
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('worker_compliance_documents');
    }
}
