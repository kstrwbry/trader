<?php
declare(strict_types=1);

namespace App\Entity;

use Kstrwbry\BinanceTrader\EntityBase\Kline as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kline_data', schema: 'trader')]
final class Kline extends BaseEntity {}
