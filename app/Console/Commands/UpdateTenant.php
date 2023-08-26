<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\AyudaWallet;
use App\Models\Commission;
use App\Models\Country;
use App\Models\Role;
use App\Models\SystemUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Website;

class UpdateTenant extends Command
{
    protected $signature = 'tenant:update {tenant} {name} {--email=}';
    protected $description = 'Update a specific section of a tenant';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tenant= $this->argument('tenant');
        $name = $this->argument('name');
        $email = $this->option('email');

        $website = Website::where('uuid',$tenant)->first();
        app(Environment::class)->tenant($website);

        if($name=='permissions'){
            $this->setupPermissions();
        }else if($name=='main_wallet'){
            $this->createAyudaWallet();
        }else if($name=='commission'){
            $this->createCommissions();
        }else if($name=='super_admin'){
            $this->createSuperAdmin($email);
        }else{
            $this->error("the update for {$name} does not exist");
            return;
        }
    }

    private function setupPermissions(){
        Permission::truncate();
        $permissions = Permission::defaultPermissions();

        foreach ($permissions as $perms) {
            Permission::firstOrCreate(['name' => $perms, 'guard_name' => 'web']);
        }

        $this->info('Default Permissions added.');
    }

    private function createAyudaWallet(){
        //Create wallet for Ayuda
        $this->info('Creating default Ayuda Main Wallet');
        AyudaWallet::firstOrCreate([
            'wallet_number'=>"AW_MAIN_001",'amount' => 0.0,'ayuda_id' => 1,'currency' => 'GH','country_id' => 1]);
    }

    private function createCommissions(){
        //Create Commissions
        $this->info('Creating default Commissions if does not exist');
        $commissions=Commission::getEntities();
        foreach($commissions as $commission){
            Commission::FirstOrCreate(
                ['entity'=>$commission['entity'],'ayuda'=>$commission['ayuda'], 'client'=>$commission['client'], 'worker'=>$commission['worker'], 'supplier'=>$commission['supplier']]
            );
            $this->info("created commission for {$commission['entity']}");
        }
    }

    private function createSuperAdmin($email){
        $role = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $$email = $this->ask('Enter email for super admin.', $email);

        if( $role->name == 'SuperAdmin' ) {
            // assign all permissions
            $role->syncPermissions(Permission::all());

            // create super admin user
            $this->createSuperAdminUser($role, $email);
            $this->info('Super Admin granted all the permissions');
        }
    }
    /**
     * Create a user with given role
     *
     * @param $role
     */
    private function createSuperAdminUser($role, $email)
    {
        $user = SystemUser::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'user_name' => 'super admin',
            'email' => $email,
            'password' => Hash::make('secret77'),
        ]);
        $user->assignRole($role->name);
        $user->save();

        $this->info('Here is your super admin details to login:');
        $this->warn($user->email);
        $this->warn($user->password);
    }

    public function loadLocations($tenant, $location){
        $this->info("Loading locations ... {$tenant->website->id}");
        Artisan::call("tenancy:run location:seed --argument=country={$location} --option=append=append --tenant={$tenant->website->id}");
    }
}
