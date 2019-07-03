<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Logger;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurnupDataBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BurnupDataBuilder
     */
    private $burnup_data_builder;

    private $logger;
    private $burnup_cache_checker;
    private $chart_configuration_value_retriever;
    private $burnup_cache_dao;
    private $burnup_calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger                              = Mockery::mock(Logger::class);
        $this->burnup_cache_checker                = Mockery::mock(BurnupCacheChecker::class);
        $this->chart_configuration_value_retriever = Mockery::mock(ChartConfigurationValueRetriever::class);
        $this->burnup_cache_dao                    = Mockery::mock(BurnupCacheDao::class);
        $this->burnup_calculator                   = Mockery::mock(BurnupCalculator::class);

        $this->burnup_data_builder = new BurnupDataBuilder(
            $this->logger,
            $this->burnup_cache_checker,
            $this->chart_configuration_value_retriever,
            $this->burnup_cache_dao,
            $this->burnup_calculator
        );
    }

    public function testItBuildsBurnupData()
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $user     = Mockery::mock(PFUser::class);

        $artifact->shouldReceive('getId')->andReturn(101);

        $this->logger->shouldReceive('debug');
        $this->logger->shouldReceive('info');

        $time_period = new \TimePeriodWithoutWeekEnd(1560760543, 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnFalse();

        $this->burnup_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')
            ->with(101)
            ->andReturn([
                [
                    'team_effort'  => 0,
                    'total_effort' => 10,
                    'timestamp'    => 1560729600,
                ],
                [
                    'team_effort'  => 2,
                    'total_effort' => 10,
                    'timestamp'    => 1560816000,
                ],
                [
                    'team_effort'  => 6,
                    'total_effort' => 10,
                    'timestamp'    => 1560902400,
                ],
                [
                    'team_effort'  => 10,
                    'total_effort' => 10,
                    'timestamp'    => 1560988800,
                ]
            ]);

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $efforts = $burnup_data->getEfforts();

        $this->assertCount(4, $efforts);
    }

    public function testItReturnsEmptyEffortsIfUnderCalculation()
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $user     = Mockery::mock(PFUser::class);

        $artifact->shouldReceive('getId')->andReturn(101);

        $this->logger->shouldReceive('debug');
        $this->logger->shouldReceive('info');

        $time_period = new \TimePeriodWithoutWeekEnd(1560760543, 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnTrue();

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $this->assertEmpty($burnup_data->getEfforts());
    }

    public function testItBuildsBurnupDataWithCurrentDay()
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $user     = Mockery::mock(PFUser::class);

        $artifact->shouldReceive('getId')->andReturn(101);

        $this->logger->shouldReceive('debug');
        $this->logger->shouldReceive('info');

        $start_date = new \DateTime();
        $start_date->setTime(0, 0, 0);

        $time_period = new \TimePeriodWithoutWeekEnd($start_date->getTimestamp(), 3);

        $this->chart_configuration_value_retriever->shouldReceive('getTimePeriod')
            ->with($artifact, $user)
            ->once()
            ->andReturn($time_period);

        $this->burnup_cache_checker->shouldReceive('isBurnupUnderCalculation')
            ->with($artifact, Mockery::any(), $user)
            ->once()
            ->andReturnFalse();

        $this->burnup_cache_dao->shouldReceive('searchCachedDaysValuesByArtifactId')
            ->with(101)
            ->andReturn([]);

        $this->burnup_calculator->shouldReceive('getValue')
            ->with(101, Mockery::any())
            ->andReturn(new BurnupEffort(5, 10));

        $burnup_data = $this->burnup_data_builder->buildBurnupData($artifact, $user);

        $efforts = $burnup_data->getEfforts();
        $this->assertCount(1, $efforts);

        $first_effort = array_values($efforts)[0];

        assert($first_effort instanceof BurnupEffort);

        $this->assertSame(5, $first_effort->getTeamEffort());
        $this->assertSame(10, $first_effort->getTotalEffort());
    }
}