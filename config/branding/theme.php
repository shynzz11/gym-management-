<?php

return [
    'name' => 'Laravel Factory',
    'dark_mode' => true,
    'logo' => 'images/logo.png',

    'login' => [
        'headline' => 'Empower your workflow.',
        'subheadline' => 'Join thousands of professionals using our platform to streamline operations, enhance security, and drive digital transformation across their entire organization.',
        'features' => [
            'Enterprise-grade security',
            'Real-time data synchronization',
            'Collaborative workspace tools',
        ],
        'form_title' => 'Sign In',
        'form_subtitle' => 'Please enter your details to access your dashboard.',
        'welcome_back' => 'Welcome Back', // Mobile only
        'login_as_admin' => 'Login as Admin',
        'login_as_customer' => 'Login as Customer',
    ],

    'admin_login' => [
        'headline' => 'Command Center Access.',
        'subheadline' => 'Secure access for system administrators. Monitor performance, manage users, and configure system settings.',
        'features' => [
            'System-wide Configuration',
            'User & Role Management',
            'Performance Analytics',
        ],
        'form_title' => 'Admin Portal',
        'form_subtitle' => 'Please enter your credentials to access the admin dashboard.',
        'welcome_back' => 'Admin Access',
        'login_as_customer' => 'Login as Customer',
    ],

    'admin' => [
        'sidebar' => [
            'dashboard' => 'dashboard',
            'account' => 'account',
            'branding' => 'branding',
            'security' => 'security',
            'collapse' => 'collapse',
            'support' => 'support',
            'tier_title' => 'tier_title',
            'tier_subtitle' => 'tier_subtitle',
            'system_section' => 'system_section',
            'health_metrics' => 'health_metrics',
            'resource_usage' => 'resource_usage',
            'identity_section' => 'identity_section',
            'business_branding' => 'business_branding',
            'management_section' => 'management_section',
            'management_subtitle' => 'management_subtitle',
            'member_directory' => 'member_directory',
            'access_roles' => 'access_roles',
            'gym_management' => 'gym_management',
            'my_profile' => 'my_profile',
        ],
        'header' => [
            'title' => 'System Performance',
            'subtitle' => 'Global infrastructure monitoring',
            'refresh' => 'Refresh',
            'signed_in_as' => 'Signed in as',
            'settings' => 'Settings',
            'logout' => 'Logout',
        ],
        'dashboard' => [
            'overview_title' => 'Dashboard Overview',
            'overview_subtitle' => "Welcome back! Here's what's happening.",
            'new_report' => '+ New Report',
            'widgets' => [
                'revenue' => [
                    'title' => 'Total Revenue',
                    'value' => '$54,230',
                    'change' => '+12.5%',
                ],
                'active_users' => [
                    'title' => 'Active Users',
                    'value' => '1,240',
                    'change' => '+3.2%',
                ],
                'new_orders' => [
                    'title' => 'New Orders',
                    'value' => '456',
                    'change' => '-1.5%',
                ],
                'system_health' => [
                    'title' => 'System Health',
                    'value' => '99.9%',
                    'status' => 'Stable',
                ],
            ],
            'recent_transactions' => [
                'title' => 'Recent Transactions',
                'view_all' => 'View All',
                'columns' => [
                    'customer' => 'Customer',
                    'status' => 'Status',
                    'amount' => 'Amount',
                ],
            ],
            'profile' => [
                'title' => 'Personal Details',
                'system_admin' => 'System Admin',
                'full_name' => 'Full Name',
                'email' => 'Email',
                'save_button' => 'Save Profile',
            ],
            'company' => [
                'title' => 'Company Name',
            ],
            'generic' => [
                'title' => 'Under Construction',
            ],
        ],
    ],

    'register' => [
        'headline' => 'Join the future of enterprise.',
        'subheadline' => 'Create your account today to access the ecosystem and accelerate your organization\'s growth.',
        'social_proof' => 'Join 10,000+ professionals',
        'social_proof_sub' => 'Trusted by industry leaders across 40+ countries.',
        'security_badge' => 'Secure SSL',
        'registration_status' => 'Registration Open',
        'form_title' => 'Create Account',
        'form_subtitle' => 'Enter your personal details to create your account',
    ],

    'home' => [
        'hero' => [
            'badge' => 'v1.0 Now Available',
            'title_line_1' => 'Build Faster with',
            'title_line_2' => 'Modern Tools',
            'description' => 'A comprehensive boilerplate featuring Laravel 12, Vue 3, Tailwind CSS, and robust role-based access control. Start your next big idea today.',
            'cta_primary' => 'Start Building Now',
            'cta_secondary' => 'Watch Demo',
        ],
        'video' => [
            'title' => 'See it in Action',
            'description' => 'Experience the smooth performance and intuitive interface.',
        ],
        'features' => [
            'title' => 'Everything you need',
            'description' => 'We\'ve handled the boilerplate so you can focus on your unique business logic.',
            'items' => [
                [
                    'title' => 'Modern Architecture',
                    'description' => 'Built with Laravel 12, Vue 3, and Inertia.js for a seamless SPA experience.',
                    'icon' => '⚡',
                ],
                [
                    'title' => 'Role Management',
                    'description' => 'Integrated multi-panel system for Admins, Customers, and Guests with secure access control.',
                    'icon' => '🛡️',
                ],
                [
                    'title' => 'Global Ready',
                    'description' => 'Native RTL support and multi-language capabilities built right in.',
                    'icon' => '🌍',
                ],
            ],
        ],
        'testimonials' => [
            'title' => 'Trusted by Developers',
            'description' => 'Here\'s what people are saying about our boilerplate.',
            'items' => [
                [
                    'name' => 'Sarah Johnson',
                    'role' => 'CTO at TechFlow',
                    'content' => 'This boilerplate saved us weeks of development time. The code quality is outstanding.',
                    'avatar' => 'https://ui-avatars.com/api/?name=Sarah+Johnson&background=random',
                ],
                [
                    'name' => 'Michael Chen',
                    'role' => 'Indie Developer',
                    'content' => 'The multi-panel system is exactly what I needed. Highly recommended!',
                    'avatar' => 'https://ui-avatars.com/api/?name=Michael+Chen&background=random',
                ],
                [
                    'name' => 'Emily Davis',
                    'role' => 'Product Manager',
                    'content' => 'Beautiful design and seamless user experience. A perfect foundation for our SaaS.',
                    'avatar' => 'https://ui-avatars.com/api/?name=Emily+Davis&background=random',
                ],
            ],
        ],
        'pricing' => [
            'title' => 'Simple, Transparent Pricing',
            'description' => 'Choose the plan that fits your needs.',
            'plans' => [
                [
                    'name' => 'Starter',
                    'price' => 'Free',
                    'features' => ['Up to 5 Users', 'Basic Support', '10GB Storage'],
                    'cta' => 'Get Started',
                    'highlight' => false,
                ],
                [
                    'name' => 'Pro',
                    'price' => '$49',
                    'features' => ['Unlimited Users', 'Priority Support', '100GB Storage', 'Analytics'],
                    'cta' => 'Start Free Trial',
                    'highlight' => true,
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Custom',
                    'features' => ['Dedicated Server', '24/7 Support', 'Unlimited Storage', 'Custom Integrations'],
                    'cta' => 'Contact Sales',
                    'highlight' => false,
                ],
            ],
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'items' => [
                [
                    'question' => 'Is this production ready?',
                    'answer' => 'Yes! We follow strict security practices and use stable versions of Laravel and Vue.',
                ],
                [
                    'question' => 'Can I customize the design?',
                    'answer' => 'Absolutely. We use Tailwind CSS, so you can easily customize the theme in app.css.',
                ],
                [
                    'question' => 'Do you support dark mode?',
                    'answer' => 'Yes, the system is built with dark mode support in mind.',
                ],
            ],
        ],
        'footer' => [
            'copyright' => '© '.date('Y').' All rights reserved.',
        ],
    ],
];
