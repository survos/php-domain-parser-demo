<?php

namespace App\Command;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GuzzleHttp\Psr7\Request;
use Pdp\Storage\PsrStorageFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Psr16Cache;
use \PDO;
use \DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'parser:update',
    description: 'Add a short description for your command',
)]
class ParserUpdateCommand extends Command
{
    public function __construct(
        private CacheInterface $cache,
//                                private ClientInterface $guzzleClient,
                                private HttpClientInterface $httpClient,
                                string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        file_put_contents($fn = __DIR__ . '/../../suffix.dat', file_get_contents(PsrStorageFactory::PUBLIC_SUFFIX_LIST_URI));
        $io->note(sprintf('Fetching: %s %s', PsrStorageFactory::PUBLIC_SUFFIX_LIST_URI, $fn));

        if ($input->getOption('option1')) {
            // ...
        }

        $pdo = new PDO(
            'sqlite:domains.db',
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
//        $cache = new Psr16Cache(new PdoAdapter($pdo, 'pdp', 43200));
        $cache = $this->cache;

        // the PSR-6 cache object that you want to use
        $psr6Cache = new FilesystemAdapter();
// a PSR-16 cache that uses your cache internally!
        $psr16Cache = new Psr16Cache($psr6Cache);

//        $client = $this->guzzleClient;
        $client = new Client();

//        $client = new GuzzleHttp\Client();
        $requestFactory = new class implements RequestFactoryInterface {
            public function createRequest(string $method, $uri): RequestInterface
            {
                return new Request($method, $uri);
            }
        };

        $cachePrefix = 'pdp_';
        $cacheTtl = new DateInterval('P1D');
        $factory = new PsrStorageFactory($psr16Cache, $client, $requestFactory);

        $pslStorage = $factory->createPublicSuffixListStorage($cachePrefix, $cacheTtl);
        $rzdStorage = $factory->createTopLevelDomainListStorage($cachePrefix, $cacheTtl);

// if you need to force refreshing the rules
// before calling them (to use in a refresh script)
// uncomment this part or adapt it to you script logic
// $pslStorage->delete(PsrStorageFactory::PUBLIC_SUFFIX_LIST_URI);
        $publicSuffixList = $pslStorage->get(PsrStorageFactory::PUBLIC_SUFFIX_LIST_URI);

// if you need to force refreshing the rules
// before calling them (to use in a refresh script)
// uncomment this part or adapt it to you script logic
// $rzdStorage->delete(PsrStorageFactory::TOP_LEVEL_DOMAIN_LIST_URI);
        $topLevelDomains = $rzdStorage->get(PsrStorageFactory::TOP_LEVEL_DOMAIN_LIST_URI);
//        dump($topLevelDomains);

        $io->success("should be loaded.");
        return Command::SUCCESS;
    }
}
