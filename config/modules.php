<?php

// Developer-maintained module registry. Access management assigns these modules; it does not create application code.
return [
    'people' => ['name' => 'Users & Teams', 'parent' => null, 'delegable' => false, 'icon' => 'users', 'accent' => 'blue', 'description' => 'Your people, account access, and recovery requests.'],
    'people.users' => ['name' => 'Users', 'parent' => 'people', 'delegable' => false],
    'people.teams' => ['name' => 'Teams', 'parent' => 'people', 'delegable' => false],
    'people.invitations' => ['name' => 'Invitations & reviews', 'parent' => 'people', 'delegable' => false],
    'people.mailboxes' => ['name' => 'Mailbox readiness', 'parent' => 'people', 'delegable' => false],
    'people.recovery' => ['name' => 'Recovery requests', 'parent' => 'people', 'delegable' => false],
    'people.history' => ['name' => 'Activity & error history', 'parent' => 'people', 'delegable' => false],
    'settings' => ['name' => 'Settings', 'parent' => null, 'delegable' => false, 'icon' => 'settings', 'accent' => 'purple', 'description' => 'Company details and controlled access to your workspace.'],
    'settings.company' => ['name' => 'Company details', 'parent' => 'settings', 'delegable' => false],
    'settings.departments' => ['name' => 'Departments', 'parent' => 'settings', 'delegable' => false],
    'settings.components' => ['name' => 'Component showcase', 'parent' => 'settings', 'delegable' => false],
    'settings.access' => ['name' => 'Groups & module access', 'parent' => 'settings', 'delegable' => false],
    'help' => ['name' => 'Help Centre', 'parent' => null, 'delegable' => true, 'icon' => 'book', 'accent' => 'green', 'description' => 'Guidance for signing in, recovering access, and using permissions.'],
    'help.manual' => ['name' => 'User manual', 'parent' => 'help', 'delegable' => true],
    'finance' => ['name' => 'Finance', 'parent' => null, 'delegable' => true, 'icon' => 'grid', 'accent' => 'purple', 'description' => 'A module workspace for verifying access. Financial workflows come later.'],
    'finance.overview' => ['name' => 'Finance overview', 'parent' => 'finance', 'delegable' => true],
];
