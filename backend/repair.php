<?php

$jobs = \App\Models\ProcessingJob::all();
foreach ($jobs as $j) {
    if (is_string($j->getRawOriginal('document_id'))) {
        $j->document_id = new \MongoDB\BSON\ObjectId($j->getRawOriginal('document_id'));
        $j->save();
        echo "Repaired Job: {$j->_id}\n";
    }
}

$claims = \App\Models\Claim::all();
foreach ($claims as $c) {
    if (is_string($c->getRawOriginal('document_id'))) {
        $c->document_id = new \MongoDB\BSON\ObjectId($c->getRawOriginal('document_id'));
        $c->save();
        echo "Repaired Claim: {$c->_id}\n";
    }
}
