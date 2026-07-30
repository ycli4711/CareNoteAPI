<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
        {--name= : Administrator display name}
        {--email= : Administrator email address}
        {--password= : Administrator password}
        {--role=super-admin : Initial administrator role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a CareNote administrator without storing default credentials';

    public function handle(): int
    {
        $name = $this->stringOption('name') ?: $this->ask('Administrator name');
        $email = $this->stringOption('email') ?: $this->ask('Administrator email');
        $password = $this->stringOption('password') ?: $this->secret('Administrator password');
        $roleName = $this->stringOption('role') ?: 'super-admin';

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $roleName,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admin_users,email'],
            'password' => ['required', 'string', Password::default()],
            'role' => ['required', 'in:super-admin,administrator'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $admin = AdminUser::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);

        $role = Role::findOrCreate($roleName, 'admin');
        $admin->assignRole($role);

        $this->components->info("Administrator {$admin->email} created with role {$roleName}.");

        return self::SUCCESS;
    }

    /**
     * Read a console option as a trimmed string.
     */
    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
