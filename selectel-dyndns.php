<?php

require_once 'vendor/autoload.php';

use Wumvi\DnsApi\DnsApi;
use Wumvi\DnsApi\ManageDomain;
use Wumvi\DnsApi\ManageRecord;
use Wumvi\DnsApi\Records\ARecordCommon as A;
use Console_CommandLine as console;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$localHostName = exec('hostname -s');

$console = new console(['description' => 'Dynamic DNS for Selectel', 'version' => 'application.version']);
$console->addOption('subdomain', [
    'short_name'    => '-s',
    'long_name'     => '--subdomain',
    'default'       => $localHostName,
    'description'   => 'subdomain to control'
]);

$console->addOption('localhost', [
    'short_name'    => '-l',
    'long_name'     => '--localhost',
    'default'       => false,
    'description'   => 'if localhost is true will be set 127.0.0.1'
]);

try {
    $consoleManager = $console->parse();
} catch (Exception $e) {
    die($e->getMessage());
}

function getIp(Console_CommandLine_Result $consoleManager): string {
    if($consoleManager->options['localhost'] == true) {
        return '127.0.0.1';
    }
    // Socket_connect will not cause any network traffic because it's an UDP socket.
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($sock, "8.8.8.8", 53);
    socket_getsockname($sock, $name); // $name passed by reference

    return $name;
}

$localIp        = getIp($consoleManager);
$dnsApi         = new DnsApi($_ENV['SELECTEL_API_KEY']);
$domainManager  = new ManageDomain($dnsApi);
$recordManager  = new ManageRecord($dnsApi);
$domain         = $domainManager->get($_ENV['DOMAIN']);
$domainId       = $domain['id'];

$host           = sprintf('%s.%s', $consoleManager->options['subdomain'], $_ENV['DOMAIN']);

try {
    $subdomainId = array_reduce($recordManager->list($domainId), function ($acc, $record) use ($host) {
        return ($record['name'] == $host) ? $record['id'] : $acc;
    });

    if (isset($subdomainId)) {
        $record      = new A($host, $localIp, $subdomainId, ['ttl' => $_ENV['TTL']]);
        $recordManager->update($domainId, $record);

    } else {
        $record = new A($host, $localIp, null, ['ttl' => $_ENV['TTL']]);
        $recordManager->set($domainId, $record);
    }
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
}
