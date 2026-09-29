<?php

/*
 * Credenciais do único usuário do sistema.
 * Lidas do .env só aqui: fora de arquivos de config, env() retorna null
 * depois de rodar "php artisan config:cache".
 */
return [
    'name' => env('ADMIN_NAME', 'Guilherme'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
