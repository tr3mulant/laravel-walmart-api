<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalmartApiCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     * Note that the text columns are such for allow room for encrypted strings.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('walmart_api_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('seller_id')->constrained('walmart_api_sellers')
                ->cascadeOnDelete();

            $table->string('client_id');

            $table->text('client_secret');

            $table->string('consumer_id')->nullable();

            $table->text('private_key')->nullable();

            $table->string('channel_type')->nullable();

            $table->string('partner_id')->nullable();

            $table->text('refresh_token')->nullable();

            $table->enum('grant_type', ['authorization_code', 'refresh_token', 'client_credentials'])
                ->default('client_credentials');

            $table->enum('country', [Walmart\Enums\Country::all()])
                ->default(Walmart\Enums\Country::US);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('walmart_api_credentials');
    }
}
