<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use AGluh\Bundle\OutboxBundle\Tests\Functional\AppKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Dotenv\Dotenv;

function bootstrap(): void
{
    (new Dotenv())->load(dirname(__DIR__).'/.env.test.local');

    $kernel = new AppKernel('test', true);
    $kernel->boot();

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:drop',
        '--if-exists' => '1',
        '--force' => '1',
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:database:create',
    ]));

    $application->run(new ArrayInput([
        'command' => 'doctrine:query:sql',
        'sql' => 'CREATE TABLE test (test VARCHAR(10))',
    ]));

    $kernel->shutdown();
}

bootstrap();