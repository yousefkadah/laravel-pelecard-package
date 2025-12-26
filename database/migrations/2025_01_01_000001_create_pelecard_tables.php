<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Pelecard columns to users table
        if (! Schema::hasColumn('users', 'pelecard_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('pelecard_id')->nullable()->index();
                $table->string('pm_type')->nullable(); // payment method type
                $table->string('pm_last_four', 4)->nullable(); // last 4 digits
                $table->timestamp('trial_ends_at')->nullable();
            });
        }

        // Create pelecard_credentials table for multi-tenancy
        Schema::create('pelecard_credentials', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner'); // owner_type, owner_id
            $table->string('terminal');
            $table->string('user');
            $table->text('password'); // encrypted
            $table->string('environment')->default('sandbox'); // sandbox or production
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'is_active']);
        });

        // Create subscriptions table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // subscription name (e.g., 'default', 'premium')
            $table->string('pelecard_subscription_id')->nullable();
            $table->string('pelecard_plan'); // plan identifier
            $table->integer('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // cancellation date
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        // Create subscription_items table
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('pelecard_plan'); // plan identifier
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['subscription_id', 'pelecard_plan']);
        });

        // Create pelecard_transactions table for logging
        Schema::create('pelecard_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pelecard_transaction_id')->nullable()->index();
            $table->string('type'); // charge, refund, authorize, etc.
            $table->integer('amount'); // in agorot/cents
            $table->string('currency', 3)->default('ILS');
            $table->string('status'); // completed, failed, pending
            $table->json('metadata')->nullable(); // full API response
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelecard_transactions');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('pelecard_credentials');

        if (Schema::hasColumn('users', 'pelecard_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'pelecard_id',
                    'pm_type',
                    'pm_last_four',
                    'trial_ends_at',
                ]);
            });
        }
    }
};
