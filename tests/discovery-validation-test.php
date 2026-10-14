<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/discovery.php';
$servers = [
    ['hostname'=>'home','parent'=>null,'root'=>true,'backdoor'=>true,'ports'=>0,'level'=>1,'ram_gb'=>64],
    ['hostname'=>'n00dles','parent'=>'home','root'=>true,'backdoor'=>false,'ports'=>0,'level'=>1,'ram_gb'=>8],
    ['hostname'=>'leaf','parent'=>'n00dles','root'=>false,'backdoor'=>false,'ports'=>2,'level'=>30,'ram_gb'=>16.5],
];
$doc = ['format'=>DISCOVERY_FORMAT,'version'=>1,'root'=>'home','servers'=>$servers];
$valid=validateDiscovery($doc);
if (count($valid)!==3 || $valid['leaf']['parent']!=='n00dles') throw new RuntimeException('Valid discovery failed.');
// The confirm action must revalidate the ORIGINAL JSON, not SQL-formatted RAM strings.
$fromOriginal=validateDiscovery($doc);
if ($fromOriginal !== $valid) throw new RuntimeException('Confirmation revalidation changed the source data.');
$wrongDoc=$doc; $wrongDoc['servers']=array_values($valid);
try { validateDiscovery($wrongDoc); throw new RuntimeException('Regression test expected normalized RAM string to be rejected.'); }
catch (InvalidArgumentException $e) { /* SQL normalization must not be mistaken for original upload JSON. */ }
$existing=[
    ['hostname'=>'home','parent_hostname'=>null,'has_root_access'=>1,'has_backdoor'=>1,'ports_required'=>0,'hacking_level'=>1,'ram_gb'=>'64.000'],
    ['hostname'=>'n00dles','parent_hostname'=>'home','has_root_access'=>1,'has_backdoor'=>0,'ports_required'=>0,'hacking_level'=>1,'ram_gb'=>'8.000'],
];
$diff=discoveryDiff($valid,$existing);
if (array_column($diff,'status')!==['unchanged','unchanged','new']) throw new RuntimeException('Difference preview failed.');
$cases=[];
$x=$doc;$x['servers'][2]['parent']='missing';$cases[]=$x;
$x=$doc;$x['servers'][1]['parent']='leaf';$cases[]=$x;
$x=$doc;$x['servers'][2]['hostname']='HOME';$cases[]=$x;
$x=$doc;$x['servers'][1]['root']='yes';$cases[]=$x;
$x=$doc;$x['servers'][1]['ram_gb']=-1;$cases[]=$x;
$x=$doc;$x['servers'][0]['parent']='n00dles';$cases[]=$x;
$x=$doc;$x['version']=2;$cases[]=$x;
foreach ($cases as $index=>$case) {
    try { validateDiscovery($case); throw new RuntimeException('Bad input accepted (case '.$index.').'); }
    catch (InvalidArgumentException $e) { /* expected */ }
}
echo "Discovery validation tests passed.\n";
