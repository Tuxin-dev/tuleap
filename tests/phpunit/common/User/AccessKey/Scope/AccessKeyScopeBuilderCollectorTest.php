<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\User\AccessKey\Scope;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class AccessKeyScopeBuilderCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNoBuildersAreCollectedByDefault(): void
    {
        $this->assertEmpty((new AccessKeyScopeBuilderCollector())->getAccessKeyScopeBuilders());
    }

    public function testBuildersAddedToTheCollectorCanBeRetrieved(): void
    {
        $builder_1 = $this->buildAccessKeyScopeBuilder();
        $builder_2 = $this->buildAccessKeyScopeBuilder();

        $collector = new AccessKeyScopeBuilderCollector();

        $collector->addAccessKeyScopeBuilder($builder_1);
        $collector->addAccessKeyScopeBuilder($builder_2);

        $this->assertSame([$builder_1, $builder_2], $collector->getAccessKeyScopeBuilders());
    }

    private function buildAccessKeyScopeBuilder(): AccessKeyScopeBuilder
    {
        return new class implements AccessKeyScopeBuilder
        {
            public function buildAccessKeyScopeFromScopeIdentifier(AccessKeyScopeIdentifier $scope_identifier) : ?AccessKeyScope
            {
                return null;
            }
        };
    }
}
