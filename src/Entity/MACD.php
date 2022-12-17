<?php
declare(strict_types=1);

namespace App\Entity;

use Kstrwbry\BinanceTrader\EntityBase\MACD as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'indicator_macd_data', schema: 'trader')]
final class MACD extends BaseEntity {}
