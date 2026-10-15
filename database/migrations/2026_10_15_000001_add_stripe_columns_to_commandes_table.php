<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Stripe session & payment tracking
            $table->string('stripe_session_id')->nullable()->after('mode_paiement');
            $table->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
            $table->string('statut_paiement')->nullable()->default('EN_ATTENTE')
                  ->after('stripe_payment_intent');
            // EN_ATTENTE | PAYE | ECHOUE | REMBOURSE
            $table->timestamp('date_paiement')->nullable()->after('statut_paiement');

            // Index for fast webhook lookups
            $table->index('stripe_session_id');
            $table->index('stripe_payment_intent');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex(['stripe_session_id']);
            $table->dropIndex(['stripe_payment_intent']);
            $table->dropColumn([
                'stripe_session_id',
                'stripe_payment_intent',
                'statut_paiement',
                'date_paiement',
            ]);
        });
    }
};
