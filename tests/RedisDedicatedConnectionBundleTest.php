<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\RedisDedicatedConnectionBundle\RedisDedicatedConnectionBundle;

/**
 * @internal
 * @phpstan-ignore symplify.forbiddenExtendOfNonAbstractClass
 */
#[CoversClass(RedisDedicatedConnectionBundle::class)]
#[RunTestsInSeparateProcesses]
final class RedisDedicatedConnectionBundleTest extends AbstractBundleTestCase
{
}
