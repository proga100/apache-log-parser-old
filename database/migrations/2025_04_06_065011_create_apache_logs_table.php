<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apache_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('request_method');
            $table->text('request_path');
            $table->integer('status_code');
            $table->integer('response_size')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->timestamp('request_time');
            $table->timestamps();
            
            // Индексы для оптимизации поиска
            $table->index('ip_address');
            $table->index('status_code');
            $table->index('request_time');
        });
    }

    public function down()
    {
        Schema::dropIfExists('apache_logs');
    }
}; 