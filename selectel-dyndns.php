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
$localHostName  = exec('hostname -s');
$localIp        = getHostByName($localHostName);

$console = new console(['description' => 'Dynamic DNS for Selectel', 'version' => 'application.version']);
$console->addOption('subdomain', [
    'short_name'    => '-s',
    'long_name'     => '--subdomain',
    'default'       => $localHostName,
    'description'   => 'Subdomain to control'
]);


$dnsApi         = new DnsApi($_ENV['SELECTEL_API_KEY']);
$domainManager  = new ManageDomain($dnsApi);
$recordManager  = new ManageRecord($dnsApi);
$domain         = $domainManager->get($_ENV['DOMAIN']);
$domainId       = $domain['id'];
$consoleManager = $console->parse();
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
