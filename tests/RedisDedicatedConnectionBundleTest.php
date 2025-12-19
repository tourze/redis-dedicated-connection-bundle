<?php

declare(strict_types=1);

namespace Tourze\RedisDedicatedConnectionBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\RedisDedicatedConnectionBundle\RedisDedicatedConnectionBundle;


#[CoversClass(RedisDedicatedConnectionBundle::class)]
#[RunTestsInSeparateProcesses]
final class RedisDedicatedConnectionBundleTest extends AbstractBundleTestCase
{
}
