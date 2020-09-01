<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\Configuration\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $parameters->set(Option::SETS, [
        SetList::CLEAN_CODE,
        SetList::PHP_71,
        SetList::STRICT,
        SetList::PSR_12,
        SetList::SYMFONY,
    ]);

    $parameters->set(Option::EXCLUDE_PATHS, [
        __DIR__ . '/src/Kernel.php',      // Created during the Travis build!
        __DIR__ . '/tests/bootstrap.php', // Created during the Travis build!
    ]);
};