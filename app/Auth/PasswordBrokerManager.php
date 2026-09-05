<?php

namespace App\Auth;

class PasswordBrokerManager extends \Illuminate\Auth\Passwords\PasswordBrokerManager
{
    protected function resolve($name)
    {
        $config = $this->getConfig($name);
        if (! $config || ($config['driver'] ?? 'database') !== 'database') {
            throw new \InvalidArgumentException('A configured database password broker is required.');
        }
        $key = $this->app['config']['app.key'];
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return new AtomicPasswordBroker(
            new BoundPasswordTokenRepository(
                $this->app['db']->connection($config['connection'] ?? null),
                $this->app['hash'], $config['table'], $key,
                ($config['expire'] ?? 60) * 60, $config['throttle'] ?? 0,
            ),
            $this->app['auth']->createUserProvider($config['provider']),
            $this->app['events'],
            timeboxDuration: $this->app['config']->get('auth.timebox_duration', 200000),
        );
    }
}
