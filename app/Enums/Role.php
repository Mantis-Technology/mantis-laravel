<?php

namespace App\Enums;

enum Role: string
{
    case TENANT_ADMINISTRATOR = 'tenant_admin';
    case MAINTENANCE_CHIEF = 'maintenance_chief';
    case TECHNICIAN = 'technician';
    case OPERATOR = 'operator';
}