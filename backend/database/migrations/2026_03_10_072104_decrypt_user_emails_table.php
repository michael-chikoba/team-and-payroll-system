<?php
// database/migrations/2024_01_01_000000_decrypt_user_emails.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\EncryptionService;

return new class extends Migration
{
    public function up(): void
    {
        $encryption = app(EncryptionService::class);

        DB::table('users')->orderBy('id')->chunk(100, function ($users) use ($encryption) {
            foreach ($users as $user) {
                $email = $user->email;

                if ($email && $encryption->isEncrypted($email)) {
                    $decrypted = $encryption->decryptRaw($email);

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email' => $decrypted]);
                }
            }
        });
    }

    public function down(): void
    {
        // Re-encrypting on rollback is intentionally omitted —
        // you're moving away from encrypted emails permanently.
    }
};