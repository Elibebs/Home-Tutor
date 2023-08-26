<?php

namespace App;

use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Hash;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;

/**
 * @property Website website
 * @property Hostname hostname
 */
class Tenant
{
    public function __construct(Website $website = null, Hostname $hostname = null)
    {
        $this->website = $website;
        $this->hostname = $hostname;
    }

    public static function delete($name)
    {
        $baseUrl = env('APP_URL_BASE','');
        $name = "{$name}.{$baseUrl}";
        if ($tenant = Hostname::where('fqdn', $name)->firstOrFail()) {
            app(HostnameRepository::class)->delete($tenant, true);
            app(WebsiteRepository::class)->delete($tenant->website, true);
            return "Tenant {$name} successfully deleted.";
        }
    }

    public static function deleteByFqdn($fqdn)
    {
        if ($tenant = Hostname::where('fqdn', $fqdn)->firstOrFail()) {
            app(HostnameRepository::class)->delete($tenant, true);
            app(WebsiteRepository::class)->delete($tenant->website, true);
            return "Tenant {$fqdn} successfully deleted.";
        }
    }

    public static function registerTenant($name): Tenant
    {
        // Convert all to lowercase
        $name = strtolower($name);

        $website = new Website();
        $website->uuid=$name;
        app(WebsiteRepository::class)->create($website);

        // associate the website with a hostname
        $hostname = new Hostname;
        $baseUrl = env('APP_URL_BASE','');
        $hostname->fqdn = "{$name}.{$baseUrl}";
        app(HostnameRepository::class)->attach($hostname, $website);

        // make hostname current
        app(Environment::class)->tenant($hostname->website);

        return new Tenant($website, $hostname);
    }

    public static function tenantExists($name)
    {
        $name = $name . '.' . config('tenancy.hostname.default');
        return Hostname::where('fqdn', $name)->exists();
    }
}
