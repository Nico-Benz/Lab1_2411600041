<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer')->after('password'); // admin or viewer
        });

        // Make the first account and the test account admins so nobody is locked out
        $firstId = DB::table('users')->min('id');
        if ($firstId) {
            DB::table('users')->where('id', $firstId)->update(['role' => 'admin']);
        }
        DB::table('users')->where('email', 'test@example.com')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
