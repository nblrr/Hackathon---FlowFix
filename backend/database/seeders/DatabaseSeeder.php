<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        if(!app()->environment(['local','testing'])) throw new \RuntimeException('Demo seeding is local-only.');
        User::firstOrCreate(['email'=>'mahasiswa@flowfix.test'],['name'=>'Arga Pratama','role'=>'student','password'=>'FlowFix-demo-2026!']);
        User::firstOrCreate(['email'=>'peninjau@flowfix.test'],['name'=>'Ratna Kusuma','role'=>'reviewer','password'=>'FlowFix-demo-2026!']);
    }
}
