<?php

namespace App;

use Hyn\Tenancy\Traits\UsesTenantConnection;

class Activity extends \Spatie\Activitylog\Models\Activity
{
    use UsesTenantConnection;
}
