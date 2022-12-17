<?php
declare(strict_types=1);

namespace App\Entity;

use Kstrwbry\BinanceTrader\EntityBase\RVI as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'indicator_rvi_data', schema: 'trader')]
final class RVI extends BaseEntity {}
