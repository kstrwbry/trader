<?php
declare(strict_types=1);

namespace App\Entity;

use Kstrwbry\BinanceTrader\EntityBase\StdDev as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'indicator_stddev_data', schema: 'trader')]
final class StdDev extends BaseEntity {}
