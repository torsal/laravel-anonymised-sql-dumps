<?php

/**
 * mapping the DB columns to anonymise with faker
 * (see list of properties available:
 * vendor/fzaninotto/faker/src/Faker/Generator.php)
 *
 * 'TableName' => [
 *     'column' => [
 *         'type' => 'fakerProperty',
 *         'void' => true | false,
 *         'anonymise' => true | false
 *     ]
 * ]
 */

return [
    'users' => [
        'first_name' => ['type' => 'firstName'],
        'last_name' => ['type' => 'lastName'],
        'phone_number' => ['type' => 'phoneNumber'],
        'email' => ['type' => 'email'], 
    ]
];
