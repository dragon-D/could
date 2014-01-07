<?php

namespace raincious\Permit;

error_reporting(E_ALL);

require('Could.php');

$defaultPermits = [
    'TotalPermission' => true,

    'User' => [
        'Profile' => false,
        'Message' => false,

        'Security' => [
            'ChangePassword' => false,
            'ChangeEmail' => false,
        ],
    ],

    'Account' => [
        'Login' => true,
        'Register' => true,
    ],

    'News' => [
        'Add' => false,
        'Edit' => false,
        'Delete' => false,
    ],
];

$userPermissions = [
    'User' => [
        'Profile' => true,

        'Security' => [
            'ChangePassword' => true,
        ],
    ],

    'Account' => [
        'Login' => true,
        'Register' => true,
    ],
];

$userGroupPermission = [
    'User' => [
        'Profile' => false,
    ],

    'News' => [
        'Add' => true,
        'Edit' => true,
        'Delete' => true,
    ],
];

$bannedGroupPermission = [
    'Account' => [
        'Login' => false,
        'Register' => false,
    ],
];

$canDo = new Could($defaultPermits);

echo "<h2>Default permission</h2>";

var_dump($canDo->export());

echo "<h2>Test result</h2>";

$canDo->authorize($userGroupPermission)->either($userPermissions)->both($bannedGroupPermission);

if ($canDo->can('Account.Login')) {
    echo 'I can login';
} else {
    echo 'I can\'t login';
}

echo '<br />';

if ($canDo->can('User.Profile')) {
    echo 'I can change my profile';
} else {
    echo 'I can\'t change my profile';
}

echo "<h2>Permission data</h2>";

var_dump($canDo->export());

echo "<h2>Change permission with flat key</h2>";

if ($canDo->lets(array(
    'Account.Login' => true,
))) {
    echo 'Changed: ';
} else {
    echo 'Unchange: ';
}

if ($canDo->can('Account.Login')) {
    echo 'I can login';
} else {
    echo 'I can\'t login';
}

echo "<h2>New permission data</h2>";

var_dump($canDo->export());
