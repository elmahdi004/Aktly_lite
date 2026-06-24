<?php

use App\Models\Demande;
use App\Services\PdfFieldsService;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$demandeId = $argv[1] ?? null;
if (!$demandeId) {
    fwrite(STDERR, "Usage: php scripts/check_formulaire1_fields.php <demande_id>\n");
    exit(1);
}

$demande = Demande::find($demandeId);
if (!$demande) {
    fwrite(STDERR, "Demande not found\n");
    exit(1);
}

$raw = json_decode($demande->bce_data, true);
$service = app(PdfFieldsService::class);
$data = $service->applyFormulaire1Overrides($raw, $demande);

$out = [
    'number' => $data['number'] ?? null,
    'denomination' => $data['denomination']['description']['value'] ?? null,
    'juridicalForm' => $data['juridicalForm']['description']['value'] ?? null,
    'address' => [
        'street' => $data['address']['street']['description']['value'] ?? null,
        'houseNumber' => $data['address']['houseNumber'] ?? null,
        'box' => $data['address']['box'] ?? null,
        'zipcode' => $data['address']['zipcode'] ?? null,
        'municipality' => $data['address']['municipality']['description']['value'] ?? null,
        'country' => $data['address']['country'] ?? null,
    ],
];

print_r($out);
