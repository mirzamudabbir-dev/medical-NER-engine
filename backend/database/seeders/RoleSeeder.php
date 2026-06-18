<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $reviewer = Role::firstOrCreate(['name' => 'Reviewer']);
        $uploader = Role::firstOrCreate(['name' => 'Uploader']);

        // Assign Admin role to existing users to not break current access
        $users = User::all();
        foreach($users as $user) {
            if (!$user->role_id) {
                $user->role_id = $admin->id;
                $user->save();
            }
        }
    }
}
