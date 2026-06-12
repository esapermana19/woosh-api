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
        Schema::create('stations', function (Blueprint $table) {
            $table->id('station_id');
            $table->string('station_name', 100);
            $table->string('city', 100)->nullable();
            $table->string('code', 10)->unique()->nullable();
        });

        Schema::create('trains', function (Blueprint $table) {
            $table->id('train_id');
            $table->string('train_name', 100);
            $table->string('train_code', 20)->unique()->nullable();
            $table->integer('total_seats')->default(600);
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('train_id')->nullable();
            $table->unsignedBigInteger('departure_station')->nullable();
            $table->unsignedBigInteger('arrival_station')->nullable();
            $table->dateTime('departure_time')->nullable();
            $table->dateTime('arrival_time')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            
            $table->foreign('train_id')->references('train_id')->on('trains');
            $table->foreign('departure_station')->references('station_id')->on('stations');
            $table->foreign('arrival_station')->references('station_id')->on('stations');
        });

        Schema::create('seats', function (Blueprint $table) {
            $table->id('seat_id');
            $table->unsignedBigInteger('train_id')->nullable();
            $table->string('seat_number', 10)->nullable();
            $table->enum('class', ['economy', 'business', 'vip'])->default('economy');

            $table->foreign('train_id')->references('train_id')->on('trains');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('booking_code', 20)->unique()->nullable();
            $table->timestamp('booking_date')->useCurrent();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->enum('status', ['pending', 'paid', 'cancelled', 'completed'])->default('pending');

            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('schedule_id')->references('schedule_id')->on('schedules');
        });

        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->id('passenger_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('full_name', 100)->nullable();
            $table->string('id_number', 30)->nullable();
            $table->unsignedBigInteger('seat_id')->nullable();

            $table->foreign('booking_id')->references('booking_id')->on('bookings');
            $table->foreign('seat_id')->references('seat_id')->on('seats');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->enum('payment_method', ['bank_transfer', 'ewallet', 'credit_card'])->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('payment_status', ['pending', 'success', 'failed'])->default('pending');

            $table->foreign('booking_id')->references('booking_id')->on('bookings');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('qr_code', 255)->nullable();
            $table->timestamp('issued_at')->useCurrent();

            $table->foreign('booking_id')->references('booking_id')->on('bookings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_passengers');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('seats');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('trains');
        Schema::dropIfExists('stations');
    }
};
