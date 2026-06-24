<!DOCTYPE html>
<html lang="{{ $demande->bce_data['lang_entre'] ?? 'fr' }}">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('favicon_new.ico') }}">

    <title>Legakte</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 40px; }
        h1, h2, h3 { text-align: center; }
        .signature { margin-top: 60px; display: flex; justify-content: space-between; }
        .signature div { text-align: center; }
    </style>
</head>
<body>
    <p>
        {{ $form_juridique }}
        « {{ $denomination }} »<br>
        {{ $street ?? '' }}
        {{ $houseNumber ?? '' }}<br>
        {{ $zipcode ?? '' }}
        {{ $municipality ?? '' }}
    </p>

    <h3>
        @if(($demande->langue_entreprise ?? 'fr') === 'nl')
        @php \Carbon\Carbon::setLocale('nl'); @endphp
            Proces-verbaal van de buitengewone algemene vergadering van {{ \Carbon\Carbon::parse($demande->date_assemblee)->translatedFormat('d F Y') }}
        @else
            @php \Carbon\Carbon::setLocale('fr'); @endphp
            Procès-verbal de l’assemblée générale extraordinaire du {{ \Carbon\Carbon::parse($demande->date_assemblee)->translatedFormat('d F Y') }}
        @endif
    </h3>

    @php
    $lang = $demande->langue_entreprise ?? 'fr';


    // On construit dynamiquement la liste des services cochés
    $parts = [];

    if ($demande->cession_parts_service == 1) {
        $parts[] = ($lang === 'nl') ? 'Overdracht van aandelen' : 'Cession de parts';
    }

    if ($demande->address_service == 1) {
        $parts[] = ($lang === 'nl') ? 'Zetelverplaatsing' : 'Transfert de siège social';
    }

    if ($demande->dirigeants_service == 1) {
        $parts[] = ($lang === 'nl') ? 'Ontslag en benoeming van bestuurder' : 'Démission et nomination d’administrateur';
    }

        // On assemble le titre avec virgules
        $title = implode(', ', $parts);
    @endphp

    @if(!empty($title))
        <h3>{{ $title }}</h3>
    @endif

    <p>{!! nl2br(e($demande->pub_text)) !!}</p>

    

<div class="signature d-flex justify-content-between">
    @foreach($dirigeants as $dirigeant)
        <div class="text-center">
            <div>
                {{ $dirigeant['givenName'] ?? '' }} {{ $dirigeant['surname'] ?? '' }}<br>
                @if(($demande->bce_data['langue_entreprise'] ?? 'fr') === 'nl')
                    Bestuurder
                @else
                    Administrateur
                @endif
            </div>
            <div></div>
        </div>
    @endforeach
</div>
</body>
@php
if(isset($download)){
    echo "<script>window.print();</script>";
}
@endphp

</html>
