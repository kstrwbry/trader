<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Kline;
use App\Entity\KlineRaw;
use App\Entity\MACD;
use App\Entity\RSI;
use App\Entity\RVI;
use App\Entity\StdDev;
use Binance\API as BinanceAPI;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

# performance optimizations
use function array_diff;
use function array_keys;
use function array_map;
use function array_walk;
use function sprintf;

//TODO: CRON JOB
#[AsCommand(name: 'binance:data:fetch')]
class FetchBinanceDataCommand extends Command
{
    private null|Kline $lastKline = null;

    public function __construct(
        private readonly LoggerInterface        $logger,
        private readonly BinanceAPI             $binanceApiBlank,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    /** {@inheritDoc} */
    protected function configure(): void
    {
        $this->addOption(
            'symbols',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'like "ADAUSDT" or/and "BTCBNB"'
        );
    }

    /** {@inheritDoc} */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int {
        $symbols = $input->getOption('symbols');

        $this->throwUnlessAllSymbolsAreValid($symbols);

        array_walk(
            $symbols,
            $this->startKline(...),
            $this->logKline(...)
        );

        return Command::SUCCESS;
    }

    /** fix arguments because it's used as callback */
    private function startKline(
        string   $symbol,
        int      $symbolIndex,
        callable $loggingCallback
    ): void {
        var_dump('before!');
        $this->binanceApiBlank->kline(
            $symbol,
            '1m',
            $loggingCallback
        );
        var_dump('here!');
    }

    /** fix arguments because it's used as callback */
    private function logKline(
        BinanceAPI $api,
        string     $symbol,
        mixed      $chart
    ): void {
        $chart = array_map(
            static fn($x) => (string)$x,
            (array)$chart
        );

        $klineData = [
            'startTime' => $chart['t'],
            'closeTime' => $chart['T'],
            'symbol' => $chart['s'],
            'interval' => $chart['i'],
            'firstTradeID' => $chart['f'],
            'lastTradeID' => $chart['L'],
            'open' => $chart['o'],
            'close' => $chart['c'],
            'high' => $chart['h'],
            'low' => $chart['l'],
            'baseAssetVolume' => $chart['v'],
            'tradesCount' => $chart['n'],
            'isClosed' => $chart['x'],
            'quoteAssetVolume' => $chart['q'],
            'takerBuyBaseAssetVolume' => $chart['V'],
            'takerBuyQuoteAssetVolume' => $chart['Q'],
        ];

        $this->logger->notice(sprintf(
            "symbol: %s\ndata: %s",
            $symbol,
            print_r($klineData, true)
        ));

        // TODO: LOG TO DATABASE INSTEAD OF CLI
        $raw = new KlineRaw(...$klineData);

        if(false === $raw->isClosed()) {
            return;
        }

        $kline = new Kline($raw, $this->lastKline);
        $macd = new MACD($kline, 12, 26);
        $stdDev = new StdDev($kline, 14);
        $rsi = new RSI($kline, 14);
        $rvi = new RVI($kline, 14, 30, 70, $stdDev->getStdDev());

        $this->em->persist($kline);
        $this->em->persist($macd);
        $this->em->persist($stdDev);
        $this->em->persist($rvi);
        $this->em->persist($rvi);

        $this->lastKline = $kline;

        $this->em->flush();
    }

    private function throwUnlessAllSymbolsAreValid(array $mySymbols): void
    {
        if(0 === count($mySymbols)) {
            throw new InvalidOptionException('Console option "--symbols" must not be empty.');
        }

        $apiSymbols = $this->getAPISymbols();

        $invalidSymbols = array_diff(
            $mySymbols,
            $apiSymbols
        );

        if(0 === count($invalidSymbols)) {
            return;
        }

        $wrapSymbols = static fn($symbol) => sprintf('"%s"', $symbol);

        $invalidSymbolsWrapped  = array_map($wrapSymbols, $invalidSymbols);
        $validAPISymbolsWrapped = array_map($wrapSymbols, $apiSymbols);

        throw new InvalidOptionException(sprintf(
            "Invalid console option \"--symbols\" values: %s.\nValid symbols: %s",
            implode(', ', $invalidSymbolsWrapped),
            implode(', ', $validAPISymbolsWrapped)
        ));
    }

    private function getAPISymbols(): array
    {
        // TODO: CACHING
        return array_keys($this->binanceApiBlank->prices());
    }
}
