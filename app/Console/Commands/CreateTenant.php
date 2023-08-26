<?php
namespace App\Console\Commands;
use App\Notifications\TenantCreated;
use App\Tenant;
use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\AyudaWallet;
use App\Models\Commission;
use App\Models\Location;
use App\Models\Role;
use App\Models\SystemUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
class CreateTenant extends Command
{
    protected $signature = 'tenant:create {name} {email} {locations}';
    protected $description = 'Creates a tenant with the provided name and email of super admin e.g. php artisan tenant:create ghana xyz@ayuda.com';
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $location = $this->argument(('locations'));
        if (Tenant::tenantExists($name)) {
            $this->error("A tenant with name '{$name}' already exists.");
            return;
        }
        $file_path=storage_path("geo/{$location}.txt");
        if(!file_exists($file_path)){
            $this->error("location file does not exist");
            return;
        }
        $tenant = Tenant::registerTenant($name);
        $this->info("Tenant '{$name}' is created and is now accessible at {$tenant->hostname->fqdn}");
        // invite admin
        // $tenant->admin->notify(new TenantCreated($tenant->hostname));

        $this->setupPermissions($email);
        $this->loadLocations($tenant,$location);
    }

    private function setupPermissions($email){
        Permission::truncate();
        $permissions = Permission::defaultPermissions();

        foreach ($permissions as $perms) {
            Permission::firstOrCreate(['name' => $perms, 'guard_name' => 'web']);
        }

        $this->info('Default Permissions added.');

        //Create wallet for Ayuda
        $this->info('Creating default Ayuda Main Wallet');
        AyudaWallet::firstOrCreate([
            'wallet_number'=>"AW_MAIN_001",'amount' => 0.0,'ayuda_id' => 1,'currency' => 'GH','country_id' => 1]);


        //Create Commissions
        $this->info('Creating default Commissions if does not exist');
        $commissions=Commission::getEntities();

        foreach($commissions as $commission){
            Commission::FirstOrCreate(
                ['entity'=>$commission['entity'],'ayuda'=>$commission['ayuda'], 'client'=>$commission['client'], 'worker'=>$commission['worker'], 'supplier'=>$commission['supplier']]
            );
        }

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
