@php
    if (!is_array($requestUser)) {
        $requestUser = [];
    }
    if (!isset($requestUser['person']) || !is_array($requestUser['person'])) {
        $requestUser['person'] = [];
    }
    if (!isset($requestUser['person']['addresses']) || !is_array($requestUser['person']['addresses'])) {
        $requestUser['person']['addresses'] = [['fullAddress' => '']];
    }
    $pdfFields = $demande?->pdf_fields ?? [];
    if (is_string($pdfFields)) {
        $pdfFields = json_decode($pdfFields, true);
    }
    if (!is_array($pdfFields)) {
        $pdfFields = [];
    }
    $person = $requestUser['person'] ?? [];
    if (!is_array($person)) {
        $person = [];
    }
    $gender = $person['gender'] ?? '';
    $depositaire = $depositaire ?? null;
    $fallbackName = trim((string) ($demande?->user?->name ?? ''));
    $nameParts = preg_split('/\s+/', $fallbackName, -1, PREG_SPLIT_NO_EMPTY);
    $fallbackFirstName = $nameParts[0] ?? '';
    $fallbackLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

    $firstName = $pdfFields['attestation_person_first_name']
        ?? ($depositaire?->prenom ?? ($person['firstName'] ?? $fallbackFirstName));
    $lastName = $pdfFields['attestation_person_last_name']
        ?? ($depositaire?->nom ?? ($person['lastName'] ?? $fallbackLastName));
    $dateOfBirth = $pdfFields['attestation_person_birth_date']
        ?? ($depositaire?->date_naissance ?? ($person['dateOfBirth'] ?? ''));
    $placeOfBirth = $pdfFields['attestation_person_birth_place']
        ?? ($depositaire?->lieu_naissance ?? ($person['placeOfBirth'] ?? '............'));
    $idNumber = $pdfFields['attestation_person_id_number']
        ?? ($depositaire?->numero_registre_national ?? ($person['idNumber'] ?? ''));

    $address = '';
    if (!empty($pdfFields['attestation_person_address'])) {
        $address = $pdfFields['attestation_person_address'];
    } elseif (!empty($depositaire?->domicile)) {
        $address = $depositaire->domicile;
    } elseif (!empty($person['addresses'][0]['fullAddress'])) {
        $address = $person['addresses'][0]['fullAddress'];
    } else {
        $address = '.............';
    }

    $phone = $pdfFields['attestation_person_phone']
        ?? $depositaire?->gsm
        ?? $demande?->verification?->GSM
        ?? $demande?->GSM
        ?? '';
    $email = $pdfFields['attestation_person_email'] ?? ($demande?->user?->email ?? '');

    $requestUser['person']['firstName'] = $firstName;
    $requestUser['person']['lastName'] = $lastName;
    $requestUser['person']['dateOfBirth'] = $dateOfBirth;
    $requestUser['person']['placeOfBirth'] = $placeOfBirth;
    $requestUser['person']['idNumber'] = $idNumber;
    $requestUser['person']['addresses'][0]['fullAddress'] = $address;
    if ($demande) {
        $demande->setRelation('verification', (object) ['GSM' => $phone]);
        $demande->setRelation('user', (object) ['email' => $email]);
    }
@endphp
<!DOCTYPE html>
<!-- Created by pdf2htmlEX (https://github.com/pdf2htmlEX/pdf2htmlEX) -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8"/>
<meta name="generator" content="pdf2htmlEX"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
<link rel="stylesheet" href="/style5nl/style.css"/>
<style>

@media print{
    .xmark{
        top: 4px !important;
        left:-90px !important;
    }
}    
</style>
<title></title>
</head>
<body>
<div id="sidebar">
<div id="outline">
</div>
</div>
<div id="page-container">
<div id="pf1" class="pf w0 h0" data-page-no="1"><div class="pc pc1 w0 h0"><img class="bi x0 y0 w1 h1" alt="" src="/style5nl/f1nl.png"/><div class="c x0 y0 w2 h0"><div class="t m0 x1 h2 y1 ff1 fs0 fc0 sc0 ls0 ws0"> </div><div class="t m0 x1 h3 y2 ff1 fs1 fc0 sc0 ls0 ws0"> </div><div class="t m0 x2 h4 y3 ff1 fs2 fc0 sc0 ls0 ws0"> </div></div><div class="c x1 y4 w3 h5"><div class="t m0 x0 h6 y5 ff2 fs0 fc0 sc0 ls0 ws0">GREFFE<span class="_ _0"></span> DES PERSON<span class="_ _0"></span>NES MORALES  <span class="_ _0"></span>                   <span class="_ _0"></span>                           <span class="_ _0"></span>                      <span class="_ _0"></span>                 <span class="_ _0"></span>          <span class="_ _1"></span>            <span class="_ _0"></span>          <span class="_ _2"></span><span class="fc1 sc0"> </span></div><div class="t m0 x0 h2 y6 ff1 fs0 fc0 sc0 ls0 ws0"> </div><div class="t m0 x0 h2 y7 ff1 fs0 fc0 sc0 ls0 ws0"> </div><div class="t m0 x0 h2 y8 ff1 fs0 fc0 sc0 ls0 ws0">   <span class="_ _0"></span>                      <span class="_ _0"></span>                 <span class="_ _0"></span>                      <span class="_ _0"></span>                      <span class="_ _0"></span>               <span class="_ _0"></span>                      <span class="_ _0"></span>  <span class="_ _1"></span> <span class="_ _2"></span> </div></div><div class="c x1 y9 w4 h7"><div class="t m0 x0 h8 ya ff1 fs3 fc0 sc0 ls0 ws0"> </div></div><div class="c x3 y9 w5 h7"><div class="t m0 x0 h8 ya ff1 fs3 fc0 sc0 ls0 ws0"> </div></div><div class="c x1 yb w4 h7"><div class="t m0 x0 h8 ya ff1 fs3 fc0 sc0 ls0 ws0"> </div></div><div class="c x3 yb w5 h7"><div class="t m0 x0 h8 ya ff1 fs3 fc0 sc0 ls0 ws0"> </div><div class="t m0 x0 h8 yc ff1 fs3 fc0 sc0 ls0 ws0"><span class="fc1 sc0"> </span></div><div class="t m0 x0 h8 yd ff1 fs3 fc0 sc0 ls0 ws0"><span class="fc1 sc0"> </span></div><div class="t m0 x0 h8 ye ff1 fs3 fc0 sc0 ls0 ws0"><span class="fc1 sc0"> </span></div></div><div class="c x0 y0 w2 h0"><div class="t m0 x4 h9 yf ff1 fs0 fc0 sc0 ls0 ws0">Identiteitsatt<span class="_ _0"></span>est voor <span class="_ _0"></span>de neerleggi<span class="_ _2"></span>n<span class="_ _0"></span>g van een <span class="_ _0"></span>akte van <span class="_ _0"></span>een recht<span class="_ _0"></span>spersoon - model 1*<span class="ff2 fs4"> </span></div><div class="t m0 x5 h2 y10 ff1 fs0 fc0 sc0 ls0 ws0">*Model 1 <span class="_ _0"></span>is bestemd voor p<span class="_ _0"></span>articulieren en niet-erkende p<span class="_ _0"></span>rofessionele mandatariss<span class="_ _0"></span>en<span class="ff2 fs3"> </span></div><div class="t m0 x6 h2 y11 ff1 fs0 fc0 sc0 ls0 ws0">(gelieve een rect<span class="_ _0"></span>o-verso kopie van uw <span class="_ _0"></span>identiteitskaart <span class="_ _0"></span>to<span class="_ _0"></span>e te voegen)<span class="ff2 fs3"> </span></div><div class="t m0 x7 ha y12 ff2 fs3 fc0 sc0 ls0 ws0"> </div><div class="t m0 x7 ha y13 ff2 fs3 fc0 sc0 ls0 ws0"> </div><div class="t m0 x1 hb y14 ff1 fs4 fc0 sc0 ls0 ws0">Je soussigné(e)<span class="ff3">@if(($requestUser['person']['gender']??null) == 'M') Monsieur @elseif(($requestUser['person']['gender']??null) == 'F') Madame @else Madame/Monsieur @endif<span class="_ _0"></span><span class="_ _2"></span> {!! $requestUser['person']['firstName'] ?? '' !!} {!! $requestUser['person']['lastName'] ?? '' !!}<span class="ff1"> </span></span></div><div class="t m0 x1 hb y15 ff3 fs0 fc0 sc0 ls0 ws0">geboren op <span class="_ _0"></span>{!! $requestUser['person']['dateOfBirth'] ?? '' !!} te <span class="_ _0"></span>{!! $requestUser['person']['placeOfBirth'] ?? "..............." !!} .<span class="ff1 fs4"> </span></div><div class="t m0 x1 hb y16 ff1 fs0 fc0 sc0 ls0 ws0">rijksregisternum<span class="_ _0"></span>mer <span class="ff3">: {!! $requestUser['person']['idNumber'] ?? '' !!}</span><span class="ff3"></span><span class="_ _0"></span><span class="ff3"><span class="ff1 fs4"> </span></span></div><div class="t m0 x1 hb y17 ff3 fs0 fc0 sc0 ls0 ws0">domicilié(e) te <span class="_ _0"></span>{!! $requestUser['person']['addresses'][0]["fullAddress"] ?? "" !!}<span class="_ _0"></span><span class="_ _0"></span>…….<span class="ff1 fs4"> </span></div><div class="t m0 x1 hb y18 ff1 fs0 fc0 sc0 ls0 ws0">telefoonnummer <span class="ff3">: {!! $demande?->verification?->GSM !!}<span class="_ _0"></span><span class="_ _0"></span><span class="ff1 fs4"> </span></span></div><div class="t m0 x1 hb y19 ff1 fs0 fc0 sc0 ls0 ws0">e-mailadres <span class="ff3">: {!! $demande->user->email !!}<span class="_ _0"></span><span class="_ _0"></span><span class="_ _0"></span><span class="ff1 fs4"> </span></span></div><div class="t m0 x1 hb y1a ff3 fs0 fc0 sc0 ls0 ws0">handelend voor <span class="_ _0"></span>de vennoot<span class="_ _0"></span>schap {!! $data['denomination']['description']['value'] ?? '' !!}<span class="_ _0"></span><span class="ff1 fs4"> </span></div><div class="t m0 x1 hb y1b ff1 fs0 fc0 sc0 ls0 ws0">handelend voor <span class="_ _0"></span>de vennoot<span class="_ _0"></span>schap <span class="ff3">ou l’ <span class="_ _0"></span>asbl {!! $data['denomination']['description']['value'] ?? '' !!}<span class="_ _2"></span><span class="_ _0"></span> .<span class="ff1 fs4"> </span></span></div><div class="t m0 x1 hb y1c ff3 fs0 fc0 sc0 ls0 ws0">in de hoedan<span class="_ _0"></span>ighei<span class="_ _2"></span>d <span class="_ _0"></span>van directeur<span class="_ _0"></span><span class="_ _0"></span>.<span class="ff1 fs4"> </span></div><div class="t m0 x1 hb y1d ff1 fs0 fc0 sc0 ls0 ws0">attest<span class="_ _0"></span>e déposer les ac<span class="_ _0"></span>tes visant te un<span class="_ _0"></span>(e) ou plusieurs<span class="_ _2"></span> :<span class="_ _0"></span><span class="fs4"> </span></div><div class="t m0 x8 hc y1e ff4 fs4 fc0 sc0 ls0 ws0">□<span class="ff5"> <span class="_ _3"> </span><span class="ff1 fs0">Opricht<span class="_ _0"></span>ing<span class="fs4"> </span></span></span></div><div class="t m0 x8 hc y1f ff4 fs4 fc0 sc0 ls0 ws0">@if($demande->dirigeants_service == 1)☒@else□@endif<span class="ff5"> <span class="_ _3"> </span><span class="ff1 fs0"> Benoeming(en)<span class="_ _0"></span><span class="fs4"> </span></span></span></div><div class="t m0 x8 hc y20 ff4 fs4 fc0 sc0 ls0 ws0">@if($demande->cession_parts_service == 1)☒@else□@endif<span class="ff5"> <span class="_ _3"> </span><span class="ff1 fs0"> Ontslag(en)<span class="_ _0"></span><span class="fs4"> </span></span></span></div><div class="t m0 x8 hc y21 ff4 fs4 fc0 sc0 ls0 ws0">@if($demande->address_service == 1)☒@else□@endif<span class="ff5"> <span class="_ _3"> </span><span class="ff1 fs0"> Zetelverplaatsin<span class="_ _0"></span>g<span class="fs4"> </span></span></span></div><div class="t m0 x8 hc y22 ff4 fs4 fc0 sc0 ls0 ws0">□<span class="ff5"> <span class="_ _3"> </span><span class="ff1 fs0">Andere wijzigingen <span class="ff3">:<span class="_ _0"></span> ………………………………<span class="_ _0"></span>…………<span class="ff1 fs4"> </span></span></span></span></div><div class="t m0 x1 hb y23 ff1 fs4 fc0 sc0 ls0 ws0"> </div><div class="t m0 x1 hb y24 ff1 fs0 fc0 sc0 ls0 ws0">op dat<span class="_ _0"></span>um van <span class="ff3">{!! \Carbon\Carbon::parse($demande->date_assemblee)->format('d-m-Y') !!}   <span class="_ _0"></span>             <span class="_ _0"></span>                 <span class="_ _0"></span>Handtekeni<span class="_ _2"></span>ng<span class="_ _2"></span><span class="ff1"> </span>:……<span class="_ _0"></span>……<span class="ff1 fs4"> </span></span></div></div></div><div class="pi" data-data='{"ctm":[1.500000,0.000000,0.000000,1.500000,0.000000,0.000000]}'></div></div>
</div>

</body>
@php
if(isset($download)){
    echo "<script>window.print();</script>";
}
@endphp
</html>
