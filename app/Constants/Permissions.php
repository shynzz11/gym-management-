<?php

namespace App\Constants;

class Permissions
{
    // Section: System
    public const SECTION_SYSTEM = 'system_section';

    public const DASHBOARD_VIEW = 'dashboard_view';

    public const ACTIVITY_LOG = 'activity_log';

    // Section: Account
    public const SECTION_ACCOUNT = 'account_section';

    public const MEMBER_DIRECTORY = 'staff_directory';

    public const ACCESS_ROLES = 'access_roles';

    // Section: Business
    public const SECTION_BUSINESS = 'business_settings';

    public const BUSINESS_BRANDING = 'business_branding';

    public const LEGAL_MANAGEMENT = 'legal_management';

    public const BUSINESS_LOGIC = 'business_logic';

    // Section: Gym Management
    public const SECTION_GYM = 'gym_section';

    public const MANAGE_PLANS = 'manage_plans';

    public const MANAGE_SUBSCRIPTIONS = 'manage_subscriptions';

    public const VIEW_PAYMENTS = 'view_payments';

    public const VIEW_PENDING_PAYMENTS = 'view_pending_payments';

    public const VIEW_REPORTS = 'view_reports';

    public const MANAGE_PAYROLL = 'manage_payroll';

    public const MANAGE_ACCESS_TYPES = 'manage_access_types';

    public const MANAGE_FACILITIES = 'manage_facilities';

    public static function getAll(): array
    {
        return [
            self::SECTION_GYM => [
                'label' => 'gym_management',
                'icon' => 'Dumbbell',
                'sub_modules' => [
                    self::DASHBOARD_VIEW => [
                        'label' => 'dashboard',
                        'description' => 'View system health and metrics',
                        'route' => 'admin.dashboard',
                        'icon' => 'Activity',
                    ],
                    self::MEMBER_DIRECTORY => [
                        'label' => 'customer_list',
                        'description' => 'Browse registered customers and membership status.',
                        'route' => 'admin.customers.index',
                        'icon' => 'Users',
                    ],
                    self::MANAGE_SUBSCRIPTIONS => [
                        'label' => 'Subscriptions',
                        'description' => 'View active customer subscriptions.',
                        'route' => 'admin.subscriptions.index',
                        'icon' => 'Repeat',
                    ],
                    self::VIEW_PENDING_PAYMENTS => [
                        'label' => 'pending_payments',
                        'description' => 'View pending customer payment requests.',
                        'route' => 'admin.payments.pending',
                        'icon' => 'History',
                    ],
                    self::VIEW_PAYMENTS => [
                        'label' => 'Payments',
                        'description' => 'View all received subscription payments.',
                        'route' => 'admin.payments.index',
                        'icon' => 'CreditCard',
                    ],
                    self::VIEW_REPORTS => [
                        'label' => 'Reports',
                        'description' => 'View membership expiry, renewal, and monthly plan reports.',
                        'route' => 'admin.reports.index',
                        'icon' => 'BarChart2',
                    ],
                    self::MANAGE_PAYROLL => [
                        'label' => 'Staff Payroll',
                        'description' => 'View and manage staff salary periods and payment history.',
                        'route' => 'admin.payroll.index',
                        'icon' => 'Wallet',
                    ],
                    self::MANAGE_PLANS => [
                        'label' => 'Manage Plans',
                        'description' => 'Create and configure membership plans.',
                        'route' => 'admin.plans.index',
                        'icon' => 'ClipboardList',
                    ],
                    self::MANAGE_ACCESS_TYPES => [
                        'label' => 'Plan Access Types',
                        'description' => 'Manage dynamic access windows (e.g. Morning Only).',
                        'route' => 'admin.plans.access-types.index',
                        'icon' => 'Timer',
                    ],
                    self::MANAGE_FACILITIES => [
                        'label' => 'Facilities',
                        'description' => 'Manage facilities available for plan assignment.',
                        'route' => 'admin.plans.facilities.index',
                        'icon' => 'Settings',
                    ],
                ],
            ],
            self::SECTION_ACCOUNT => [
                'label' => 'account', // Using 'account' as section label key, or management_section? Sidebar uses management_section for title, but account for rail tooltip.
                // Sidebar uses 'account' for rail tooltip, 'management_section' for Tier 2 title.
                // Permissions structure has label 'account'. I'll stick to 'account' and handle Tier 2 title logic in frontend or mapping.
                'icon' => 'Users',
                'sub_modules' => [
                    self::MEMBER_DIRECTORY => [
                        'label' => 'staff_directory',
                        'description' => 'Manage member identities and access levels',
                        'route' => 'admin.staff.index',
                        'icon' => 'Users',
                    ],
                    self::ACCESS_ROLES => [
                        'label' => 'access_roles',
                        'description' => 'Configure roles and security boundaries',
                        'route' => 'admin.roles.index',
                        'icon' => 'ShieldCheck',
                    ],
                ],
            ],
            self::SECTION_BUSINESS => [
                'label' => 'business_settings',
                'icon' => 'Briefcase',
                'sub_modules' => [
                    self::BUSINESS_BRANDING => [
                        'label' => 'business_branding',
                        'description' => 'Manage organization visual identity',
                        'route' => 'admin.business.branding', // Check route name
                        'icon' => 'Palette',
                    ],
                    self::LEGAL_MANAGEMENT => [
                        'label' => 'legal_management',
                        'description' => 'Manage legal documents (Privacy, Terms, etc.)',
                        'route' => 'admin.legal.index',
                        'icon' => 'Scale',
                    ],
                    self::BUSINESS_LOGIC => [
                        'label' => 'business_logic',
                        'description' => 'Manage business settings (Currency, Timezone, Country)',
                        'route' => 'admin.business.settings.index',
                        'icon' => 'Settings',
                    ],
                    self::ACTIVITY_LOG => [
                        'label' => 'activity_log',
                        'description' => 'View system activity logs and audit trail',
                        'route' => 'admin.activity_logs.index',
                        'icon' => 'History',
                    ],
                ],
            ],
        ];
    }

    public static function getPermissionNames(): array
    {
        $permissionNames = [];

        foreach (self::getAll() as $section) {
            foreach (array_keys($section['sub_modules'] ?? []) as $permissionName) {
                $permissionNames[$permissionName] = $permissionName;
            }
        }

        return array_values($permissionNames);
    }
}
