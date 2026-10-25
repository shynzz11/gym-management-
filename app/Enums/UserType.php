<?php

namespace App\Enums;

enum UserType: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
}
