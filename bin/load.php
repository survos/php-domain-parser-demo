#!/usr/bin/env php
<?php

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

//require_once dirname(__DIR__).'/vendor/autoload_runtime.php';


use GuzzleHttp\Psr7\Request;
use Pdp\Storage\PsrStorageFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Psr16Cache;

$pdo = new PDO(
    'sqlite:test.db',
    'user',
    'password',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$cache = new Psr16Cache(new PdoAdapter($pdo, 'pdp', 43200));
$client = new GuzzleHttp\Client();
$requestFactory = new class implements RequestFactoryInterface {
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
};

$cachePrefix = 'pdp_';
$cacheTtl = new DateInterval('P1D');
$factory = new PsrStorageFactory($cache, $client, $requestFactory);
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
