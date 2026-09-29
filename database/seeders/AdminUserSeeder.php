<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        $password = config('admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('Defina ADMIN_EMAIL e ADMIN_PASSWORD no .env antes de rodar o seeder.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD precisa ter pelo menos 12 caracteres: o sistema guarda dados financeiros.');
        }

        // Só pode existir um usuário. Se o e-mail mudar no .env, o registro existente é atualizado.
        $user = User::query()->first() ?? new User();

        $user->forceFill([
            'name' => config('admin.name'),
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ])->save();

        $this->command?->info("Usuário {$email} pronto.");
    }
}
