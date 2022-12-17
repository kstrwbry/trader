<?php
declare(strict_types=1);

namespace App\Entity;

use Kstrwbry\BinanceTrader\EntityBase\KlineRaw as BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kline_raw_data', schema: 'trader')]
final class KlineRaw extends BaseEntity {}
